"""
テーブルに登録された全URLの HTTP 到達性を確認し、結果を link-status.json に書き出す。

【チェック対象（テーブルに登録したURL）】
  (1) index.html の <table> 内 <a href>（直リンク）
  (2) リンク一覧.txt（ポップアップ用。「名前 = URL」形式）
  (3) extra-links.json（ポップアップ用の予備データ）
    → NG なら "broken" に分類（JS では赤枠表示）

【チェック範囲の方針】
  登録したURL自体の生死だけを確認する。遷移先ページの中にある内部リンク
  （その先）はチェックしない。市町村から受領したURLまでが管理範囲のため、
  範囲を広げすぎない。

実行方法:
    python link-status-check.py
依存:
    pip install beautifulsoup4 lxml requests
"""

import json
import os
import re
import sys
import time
from datetime import datetime
from urllib.parse import urlparse
from concurrent.futures import ThreadPoolExecutor, as_completed

from bs4 import BeautifulSoup
import requests


HTML_PATH = "index.html"
OUTPUT_PATH = "link-status.json"
# ポップアップ用に登録された URL データ（テーブル登録URLの一部として扱う）
LIST_TXT_PATH = "リンク一覧.txt"
EXTRA_JSON_PATH = "extra-links.json"

USER_AGENT = (
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
    "AppleWebKit/537.36 (KHTML, like Gecko) "
    "Chrome/123.0.0.0 Safari/537.36"
)
HEADERS = {
    "User-Agent": USER_AGENT,
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
    "Accept-Language": "ja,en;q=0.8",
}

# HEAD が拒否される/誤動作するサーバー向けに GET でフォールバックする
HEAD_FAIL_STATUSES = {400, 401, 403, 404, 405, 406, 409, 410, 429, 500, 501, 503}

TIMEOUT_SECONDS = 12
MAX_WORKERS = 8
RETRY_ON_TIMEOUT = 1

# 自動アクセスを拒否しやすいサイト。機械チェックだけで「リンク切れ確定」にしない。
MANUAL_CONFIRM_HOSTS = {
    "tripadvisor.jp",
    "www.tripadvisor.jp",
    "instagram.com",
    "www.instagram.com",
    "x.com",
    "twitter.com",
    "www.twitter.com",
    "facebook.com",
    "www.facebook.com",
    "m.facebook.com",
}


def is_ok_status(status: int, reason: str | None) -> bool:
    """登録URL自体が「生きている」とみなせるか。200〜399 のみ OK。
    4xx/5xx・接続不可・SSL失敗・timeout（status=0）はすべて NG（赤枠）扱い。
    """
    return 200 <= status < 400


def is_manual_confirm_result(url: str, status: int, reason: str | None) -> bool:
    host = urlparse(url).netloc.lower()
    if status in (403, 429):
        return True
    if host in MANUAL_CONFIRM_HOSTS and status >= 400:
        return True
    if status == 0 and reason and "timeout" in reason.lower():
        return True
    return False


def is_skip_host(url: str) -> bool:
    """SNS・TripAdvisor 等は機械チェックでBOT拒否され誤検知になるため、自動チェック対象外。"""
    host = urlparse(url).netloc.lower()
    return host in MANUAL_CONFIRM_HOSTS


# ----- HTTP チェック ----------------------------------------------------
def is_external_http(url: str) -> bool:
    if not url:
        return False
    u = url.strip()
    if not u or u.startswith("#"):
        return False
    if u.lower().startswith(("javascript:", "mailto:", "tel:", "data:")):
        return False
    parsed = urlparse(u)
    return parsed.scheme in ("http", "https") and bool(parsed.netloc)


def head_or_get(url: str):
    """HEAD で確認、拒否されたら GET にフォールバック。
    戻り値: (status, reason_or_None)
    """
    last_exc_reason = None
    for _ in range(RETRY_ON_TIMEOUT + 1):
        try:
            try:
                r = requests.head(
                    url,
                    headers=HEADERS,
                    allow_redirects=True,
                    timeout=TIMEOUT_SECONDS,
                )
                if r.status_code in HEAD_FAIL_STATUSES:
                    raise requests.exceptions.RequestException("retry-with-get")
            except requests.exceptions.RequestException:
                r = requests.get(
                    url,
                    headers=HEADERS,
                    allow_redirects=True,
                    timeout=TIMEOUT_SECONDS,
                    stream=True,
                )
                try:
                    r.close()
                except Exception:
                    pass
            return r.status_code, None
        except requests.exceptions.Timeout:
            last_exc_reason = "timeout"
        except requests.exceptions.SSLError as e:
            return 0, f"ssl: {str(e)[:120]}"
        except requests.exceptions.ConnectionError as e:
            return 0, f"connection: {str(e)[:120]}"
        except requests.exceptions.RequestException as e:
            return 0, f"error: {str(e)[:120]}"
        except Exception as e:
            return 0, f"unknown: {str(e)[:120]}"
    return 0, last_exc_reason or "timeout"


# ----- HTML 解析 --------------------------------------------------------
def extract_table_urls(html_path: str):
    """HTML 内の全 <table> から href を抽出して dict を返す。
    キー: 生の href 文字列（JS 側と突き合わせるため）
    """
    with open(html_path, encoding="utf-8") as f:
        soup = BeautifulSoup(f, "lxml")

    href_to_meta = {}
    for table in soup.find_all("table"):
        for a in table.find_all("a", href=True):
            raw = a["href"]
            stripped = (raw or "").strip()
            if not is_external_http(stripped):
                continue
            label_text = " ".join((a.get_text() or "").split())[:60]
            td = a.find_parent("td")
            td_id = td.get("id") if td else None
            if raw not in href_to_meta:
                href_to_meta[raw] = {
                    "url": stripped,
                    "label": label_text,
                    "cell": td_id,
                    "source": "table",
                }
    return href_to_meta


_CELL_RE = re.compile(r"^cell-\d\d-\d\d$")


def extract_listtxt_urls(base_dir: str):
    """リンク一覧.txt（ポップアップ用データ）から URL を抽出する。
    形式: 「名前 = URL」行。見出し [cell-XX-YY] / [SNS] / [島:名前] をラベル/セルに使う。
    戻り値: { url: {url, label, cell} }
    """
    path = os.path.join(base_dir, LIST_TXT_PATH)
    if not os.path.exists(path):
        return {}
    try:
        with open(path, encoding="utf-8") as f:
            text = f.read()
    except Exception:
        return {}

    result = {}
    current = ""
    for raw_line in text.splitlines():
        line = raw_line.replace("\u3000", " ").strip()
        if not line:
            continue
        if line[0] in ("#", "＃"):
            continue
        m = re.match(r"^[\[\uFF3B]\s*(.+?)\s*[\]\uFF3D]\s*$", line)
        if m:
            current = m.group(1).strip()
            continue
        norm = line.replace("＝", "=")
        if "=" in norm:
            name, url = norm.split("=", 1)
            name = name.strip()
            url = url.strip()
        else:
            name = ""
            url = line.strip()
        if not url or not is_external_http(url):
            continue
        cell = current if _CELL_RE.match(current) else None
        if url not in result:
            result[url] = {"url": url, "label": name or current, "cell": cell, "source": "list"}
    return result


def extract_json_urls(base_dir: str):
    """extra-links.json（ポップアップ用の予備データ）から URL を抽出する。
    形式: { "links": {cellId:[{名前,URL}...]}, "islands": {...}, "sns": [...] }
    戻り値: { url: {url, label, cell} }
    """
    path = os.path.join(base_dir, EXTRA_JSON_PATH)
    if not os.path.exists(path):
        return {}
    try:
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
    except Exception:
        return {}
    if not isinstance(data, dict):
        return {}

    result = {}

    def collect(items, cell):
        if not isinstance(items, list):
            return
        for it in items:
            if not isinstance(it, dict):
                continue
            url = ""
            for k in ("URL", "url", "Url"):
                if it.get(k):
                    url = str(it[k])
                    break
            name = ""
            for k in ("名前", "name", "表示名", "title"):
                if it.get(k):
                    name = str(it[k])
                    break
            url = url.strip()
            if not url or not is_external_http(url):
                continue
            if url not in result:
                result[url] = {"url": url, "label": name or (cell or ""), "cell": cell, "source": "json"}

    links = data.get("links")
    if isinstance(links, dict):
        for cell_key, items in links.items():
            cell = cell_key if _CELL_RE.match(str(cell_key)) else None
            collect(items, cell)
    islands = data.get("islands")
    if isinstance(islands, dict):
        for island_key, items in islands.items():
            collect(items, f"cell-island-{island_key}")
    sns = data.get("sns")
    if isinstance(sns, list):
        collect(sns, None)
    return result


def extract_island_links(base_dir: str):
    """data/island_related_links.json（5島「関連サイト」の実データ）から URL を抽出する。
    形式: { "links": { islandKey: [ {label,url}... ] } }
    戻り値: { url: {url, label, cell, source} }
    """
    path = os.path.join(base_dir, "data", "island_related_links.json")
    if not os.path.exists(path):
        return {}
    try:
        with open(path, encoding="utf-8") as f:
            data = json.load(f)
    except Exception:
        return {}
    links = data.get("links") if isinstance(data, dict) else None
    if not isinstance(links, dict):
        return {}

    result = {}
    for island_key, items in links.items():
        if not isinstance(items, list):
            continue
        for it in items:
            if not isinstance(it, dict):
                continue
            url = str(it.get("url") or "").strip()
            if not url or not is_external_http(url):
                continue
            name = str(it.get("label") or "").strip()
            if url not in result:
                result[url] = {
                    "url": url,
                    "label": name or str(island_key),
                    "cell": f"cell-island-{island_key}",
                    "source": "island",
                }
    return result


# index.html がある「Webフォルダー」を自動的に探す。
# このスクリプトを別フォルダー（_ローカル専用 など）に置いても動くようにするため、
#   1) 実行時のカレントフォルダー → 2) スクリプトのあるフォルダー → 3) その親フォルダー
# の順に index.html を探す。
def find_web_dir() -> str:
    candidates = [
        os.getcwd(),
        os.path.dirname(os.path.abspath(__file__)),
        os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    ]
    for d in candidates:
        if os.path.exists(os.path.join(d, HTML_PATH)):
            return d
    return os.getcwd()


# ----- メイン処理 -------------------------------------------------------
def main():
    base_dir = find_web_dir()
    html_path = os.path.join(base_dir, HTML_PATH)
    out_path = os.path.join(base_dir, OUTPUT_PATH)

    print(f"[1/3] 登録URL 解析: {html_path} + {LIST_TXT_PATH} + {EXTRA_JSON_PATH} + 島の関連サイト")
    try:
        href_to_meta = extract_table_urls(html_path)
    except FileNotFoundError:
        print(f"  HTML が見つかりません: {html_path}", file=sys.stderr)
        sys.exit(1)
    table_count = len(href_to_meta)

    # ポップアップ用に登録された URL も対象に加える（その先＝遷移先内部はチェックしない）
    list_added = 0
    for url, meta in extract_listtxt_urls(base_dir).items():
        if url not in href_to_meta:
            href_to_meta[url] = meta
            list_added += 1
    json_added = 0
    for url, meta in extract_json_urls(base_dir).items():
        if url not in href_to_meta:
            href_to_meta[url] = meta
            json_added += 1
    island_added = 0
    for url, meta in extract_island_links(base_dir).items():
        if url not in href_to_meta:
            href_to_meta[url] = meta
            island_added += 1

    print(
        f"      表内リンク: {table_count} 件 / "
        f"{LIST_TXT_PATH} 追加: {list_added} 件 / "
        f"{EXTRA_JSON_PATH} 追加: {json_added} 件 / "
        f"島の関連サイト 追加: {island_added} 件"
    )
    print(f"      対象 URL 件数（重複除外後）: {len(href_to_meta)}")
    if not href_to_meta:
        print("      登録された外部URLが見つかりませんでした。")
        save_empty(out_path)
        return

    # ユニーク URL 単位で 1 回だけチェックする
    url_to_hrefs = {}
    for href, meta in href_to_meta.items():
        url_to_hrefs.setdefault(meta["url"], []).append(href)
    # SNS・TripAdvisor 等は機械チェックに不向きなので HTTP チェック対象から除外
    unique_urls = [u for u in url_to_hrefs.keys() if not is_skip_host(u)]
    skipped_count = len(url_to_hrefs) - len(unique_urls)

    # ============================================================
    # 登録URL自体の到達性チェック（その先＝内部リンクは見ない）
    # ============================================================
    print(
        f"[2/3] HTTP チェック開始 "
        f"(並列={MAX_WORKERS}, timeout={TIMEOUT_SECONDS}s, 件数={len(unique_urls)}"
        f" / SNS等の対象外={skipped_count}件)"
    )
    started = time.time()
    results_by_url = {}  # url -> {status, reason, ok}

    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as ex:
        futures = {ex.submit(head_or_get, u): u for u in unique_urls}
        total = len(futures)
        done = 0
        for fut in as_completed(futures):
            done += 1
            u = futures[fut]
            if re.search(r"\s", u):
                status, reason = 0, "URLに空白があります（登録文字を修正してください）"
            else:
                status, reason = fut.result()
            ok = is_ok_status(status, reason)
            results_by_url[u] = {"status": status, "reason": reason, "ok": ok}
            mark = "OK" if ok else "NG"
            print(f"  [{done:>3}/{total}] {mark} status={status:<3} {u}")

    print(f"      所要時間: {time.time() - started:.1f}s")

    # ============================================================
    # 結果の集約（broken のみ）
    # ============================================================
    print(f"[3/3] 結果書き出し: {out_path}")

    broken = {}
    warnings = {}
    skipped = {}
    checked_total = 0

    for href, meta in href_to_meta.items():
        url = meta["url"]
        # SNS・TripAdvisor 等は「参考（自動チェック対象外）」。数が実行環境でぶれないよう常に固定表示。
        if is_skip_host(url):
            skipped[href] = {
                "status": "-",
                "reason": "SNS等",
                "label": meta["label"],
                "cell": meta["cell"],
                "source": meta.get("source", ""),
            }
            continue
        r = results_by_url.get(url)
        if not r:
            continue
        checked_total += 1
        if not r["ok"]:
            record = {
                "status": r["status"],
                "reason": r["reason"] or f"HTTP {r['status']}",
                "label": meta["label"],
                "cell": meta["cell"],
                "source": meta.get("source", ""),
            }
            if is_manual_confirm_result(url, int(r["status"]), r.get("reason")):
                warnings[href] = record
            else:
                broken[href] = record

    result = {
        "generatedAt": datetime.now().isoformat(timespec="seconds"),
        "source": HTML_PATH,
        "checked": checked_total,
        "brokenCount": len(broken),
        "broken": broken,
        "warningCount": len(warnings),
        "warnings": warnings,
        "skippedCount": len(skipped),
        "skipped": skipped,
    }

    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)

    print()
    print(
        f"  チェック完了: {checked_total} 件 / 要対応 {len(broken)} 件 / "
        f"ブラウザ確認 {len(warnings)} 件 / 参考(SNS等) {len(skipped)} 件"
    )
    if broken:
        print("  ── 要対応（登録URLの修正/削除候補）一覧 ──")
        for href, info in broken.items():
            print(f"    [{info['status']}] {info['label'] or '(no text)'} -> {href}")
            if info.get("cell"):
                print(f"        cell={info['cell']}")
    if warnings:
        print("  ── ブラウザ確認（機械判定ではNG・自動削除しない）一覧 ──")
        for href, info in warnings.items():
            print(f"    [{info['status']}] {info['label'] or '(no text)'} -> {href}")
            if info.get("cell"):
                print(f"        cell={info['cell']}")
    print(f"\n出力: {out_path}")


def save_empty(out_path: str):
    result = {
        "generatedAt": datetime.now().isoformat(timespec="seconds"),
        "source": HTML_PATH,
        "checked": 0,
        "brokenCount": 0,
        "broken": {},
        "warningCount": 0,
        "warnings": {},
        "skippedCount": 0,
        "skipped": {},
    }
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)


if __name__ == "__main__":
    main()
