"""
index.html 内の全テーブルに含まれるリンクの HTTP 到達性を 2 段階で確認し、
結果を link-status.json に書き出す。

【2 段階チェック】
  Level 1：セルの <a href> 自体（テーブルの直リンク先）
    → NG なら "broken" に分類（JS では赤枠表示）

  Level 2：Level 1 が OK だったページの中にある <a href>（飛んだ先の内部リンク）
    → 1 つでも NG が含まれていれば "innerBroken" に分類（JS では青枠表示）

【優先順位】broken（赤） > innerBroken（青）
  　　　　 Level 1 で切れているセルは Level 2 を実施しない。

実行方法:
    python link-status-check.py
依存:
    pip install beautifulsoup4 lxml requests
"""

import json
import sys
import time
from datetime import datetime
from urllib.parse import urlparse, urljoin, urldefrag
from concurrent.futures import ThreadPoolExecutor, as_completed

from bs4 import BeautifulSoup
import requests


HTML_PATH = "index.html"
OUTPUT_PATH = "link-status.json"

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
# Level 2 で 1 ページから抽出して検査する内部リンクの最大数（暴走防止）
# NOTE: 120ページ × 20リンクでは時間がかかりすぎるため、まずは主要な先頭リンクだけを確認する。
MAX_INNER_LINKS_PER_PAGE = 5
# Level 2 取得時のレスポンスサイズ上限（HTML 解析用に十分な量）
LEVEL2_FETCH_BYTES = 512 * 1024

# NOTE:
# 赤枠は「そもそも遷移先サーバーへ到達できない」場合だけにする。
# 404/410 はブラウザ上では遷移先サイトの「ページが見つかりません」画面に到達するため、
# ユーザー視点では「最初の遷移はできたが、その先でページ不備がある」＝青枠として扱う。
LANDING_PAGE_PROBLEM_STATUSES = {404, 410}


def is_landing_page_problem(status: int, reason: str | None) -> bool:
    """青枠にする「遷移先ページ側の不備」を判定する。"""
    if status in LANDING_PAGE_PROBLEM_STATUSES:
        return True
    return False


def is_unreachable_for_browser(status: int, reason: str | None) -> bool:
    """赤枠にする「ブラウザでも到達できない可能性が高い」状態だけを判定する。"""
    # DNS失敗・接続不可・SSL失敗など。timeout は一時的な遅延の可能性があるため赤枠にしない。
    return status == 0 and bool(reason) and not reason.startswith("timeout")


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


def fetch_html(url: str):
    """Level 2 用：HTML を取得して文字列を返す。失敗時は None。"""
    try:
        r = requests.get(
            url,
            headers=HEADERS,
            allow_redirects=True,
            timeout=TIMEOUT_SECONDS,
            stream=True,
        )
        if r.status_code >= 400:
            r.close()
            return None, None
        ctype = (r.headers.get("Content-Type") or "").lower()
        if "html" not in ctype and "xml" not in ctype and "text/" not in ctype:
            r.close()
            return None, None
        body = r.raw.read(LEVEL2_FETCH_BYTES, decode_content=True)
        r.close()
        try:
            text = body.decode(r.encoding or "utf-8", errors="replace")
        except Exception:
            text = body.decode("utf-8", errors="replace")
        # リダイレクト後の最終 URL（相対URL解決の基準）
        final_url = r.url or url
        return text, final_url
    except Exception:
        return None, None


def extract_inner_links(html: str, base_url: str):
    """ページから <a href> を抽出し、絶対 URL の重複なしリストで返す。
    フラグメント除去・自分自身を除外。
    NOTE: 過剰検出を避けるため、リンク先ページと同じドメインのリンクだけを検査する。
          広告・SNS・外部ウィジェット等の切れリンクは、元のセルの責任範囲から外す。
    """
    soup = BeautifulSoup(html, "lxml")
    seen = set()
    result = []
    base_clean = urldefrag(base_url).url
    base_host = urlparse(base_url).netloc.lower()

    for a in soup.find_all("a", href=True):
        raw = (a.get("href") or "").strip()
        if not raw or raw.startswith("#"):
            continue
        if raw.lower().startswith(("javascript:", "mailto:", "tel:", "data:")):
            continue
        absolute = urljoin(base_url, raw)
        absolute = urldefrag(absolute).url
        if absolute == base_clean:
            continue
        parsed = urlparse(absolute)
        if parsed.scheme not in ("http", "https") or not parsed.netloc:
            continue
        if parsed.netloc.lower() != base_host:
            continue
        if absolute in seen:
            continue
        seen.add(absolute)
        result.append(absolute)
        if len(result) >= MAX_INNER_LINKS_PER_PAGE:
            break
    return result


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
                }
    return href_to_meta


# ----- メイン処理 -------------------------------------------------------
def main():
    html_path = HTML_PATH
    out_path = OUTPUT_PATH

    print(f"[1/4] HTML 解析: {html_path}")
    try:
        href_to_meta = extract_table_urls(html_path)
    except FileNotFoundError:
        print(f"  HTML が見つかりません: {html_path}", file=sys.stderr)
        sys.exit(1)

    print(f"      対象 URL 件数: {len(href_to_meta)}")
    if not href_to_meta:
        print("      テーブル内に外部URLが見つかりませんでした。")
        save_empty(out_path)
        return

    # ユニーク URL 単位で 1 回だけチェックする
    url_to_hrefs = {}
    for href, meta in href_to_meta.items():
        url_to_hrefs.setdefault(meta["url"], []).append(href)
    unique_urls = list(url_to_hrefs.keys())

    # ============================================================
    # Level 1: セル直リンクの到達性チェック
    # ============================================================
    print(
        f"[2/4] Level 1 チェック開始 "
        f"(並列={MAX_WORKERS}, timeout={TIMEOUT_SECONDS}s, 件数={len(unique_urls)})"
    )
    l1_started = time.time()
    l1_results = {}  # url -> {status, reason, ok}

    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as ex:
        futures = {ex.submit(head_or_get, u): u for u in unique_urls}
        total = len(futures)
        done = 0
        for fut in as_completed(futures):
            done += 1
            u = futures[fut]
            status, reason = fut.result()
            ok = not is_unreachable_for_browser(status, reason)
            l1_results[u] = {"status": status, "reason": reason, "ok": ok}
            mark = "OK" if ok else "NG"
            print(f"  [L1 {done:>3}/{total}] {mark} status={status:<3} {u}")

    print(f"      所要時間: {time.time() - l1_started:.1f}s")

    # ============================================================
    # Level 2: Level 1 が OK だったページの内部リンクをチェック
    # ============================================================
    l1_ok_urls = [u for u in unique_urls if l1_results[u]["ok"]]
    print(
        f"[3/4] Level 2 チェック開始 "
        f"(対象ページ={len(l1_ok_urls)}, 1ページあたり最大={MAX_INNER_LINKS_PER_PAGE}リンク)"
    )
    l2_started = time.time()
    # ページ毎の内部リンク検査結果： page_url -> list[{url, status, reason, ok}]
    page_inner_results = {}
    # 内部URLのチェック結果キャッシュ（同じURLは1回だけ叩く）
    inner_cache = {}

    def check_inner(u: str):
        if u in inner_cache:
            return inner_cache[u]
        status, reason = head_or_get(u)
        ok = not is_landing_page_problem(status, reason)
        rec = {"status": status, "reason": reason, "ok": ok}
        inner_cache[u] = rec
        return rec

    for idx, page_url in enumerate(l1_ok_urls, start=1):
        prefix = f"  [L2 {idx:>3}/{len(l1_ok_urls)}]"
        html, final_url = fetch_html(page_url)
        if html is None:
            print(f"{prefix} skip (no HTML) {page_url}")
            continue
        inner_links = extract_inner_links(html, final_url or page_url)
        if not inner_links:
            page_inner_results[page_url] = []
            print(f"{prefix} inner=0  {page_url}")
            continue

        # この 1 ページの内部リンクを並列で叩く
        inner_records = []
        with ThreadPoolExecutor(max_workers=MAX_WORKERS) as ex:
            futures = {ex.submit(check_inner, u): u for u in inner_links}
            for fut in as_completed(futures):
                u = futures[fut]
                rec = fut.result()
                inner_records.append({"url": u, **rec})
        page_inner_results[page_url] = inner_records

        ng = [r for r in inner_records if not r["ok"]]
        print(
            f"{prefix} inner={len(inner_records):>2} (NG={len(ng):>2}) {page_url}"
        )

    print(f"      所要時間: {time.time() - l2_started:.1f}s")

    # ============================================================
    # 結果の集約
    # ============================================================
    print(f"[4/4] 結果書き出し: {out_path}")

    broken = {}        # Level 1 切れ
    inner_broken = {}  # Level 1 OK / Level 2 切れあり
    checked_total = 0

    for href, meta in href_to_meta.items():
        url = meta["url"]
        l1 = l1_results.get(url)
        if not l1:
            continue
        checked_total += 1
        if not l1["ok"]:
            broken[href] = {
                "status": l1["status"],
                "reason": l1["reason"] or f"HTTP {l1['status']}",
                "label": meta["label"],
                "cell": meta["cell"],
            }
            continue

        # Level 1 は遷移できたが、遷移先ページ自体が 404/410 を返す場合。
        # ブラウザではサイト内の「ページが見つかりません」画面へ到達するため、赤ではなく青枠にする。
        if is_landing_page_problem(l1["status"], l1["reason"]):
            inner_broken[href] = {
                "label": meta["label"],
                "cell": meta["cell"],
                "innerCheckedCount": 1,
                "innerBrokenCount": 1,
                "innerBrokenLinks": [
                    {
                        "url": url,
                        "status": l1["status"],
                        "reason": l1["reason"] or f"HTTP {l1['status']}",
                    }
                ],
            }
            continue

        # Level 1 OK のセル → Level 2 を確認
        inner_records = page_inner_results.get(url, [])
        ng_records = [r for r in inner_records if not r["ok"]]
        if ng_records:
            inner_broken[href] = {
                "label": meta["label"],
                "cell": meta["cell"],
                "innerCheckedCount": len(inner_records),
                "innerBrokenCount": len(ng_records),
                "innerBrokenLinks": [
                    {
                        "url": r["url"],
                        "status": r["status"],
                        "reason": r["reason"] or f"HTTP {r['status']}",
                    }
                    for r in ng_records[:10]  # JSON が大きくなりすぎないよう先頭10件のみ
                ],
            }

    result = {
        "generatedAt": datetime.now().isoformat(timespec="seconds"),
        "source": html_path,
        "checked": checked_total,
        "brokenCount": len(broken),
        "broken": broken,
        "innerBrokenCount": len(inner_broken),
        "innerBroken": inner_broken,
    }

    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)

    print()
    print(
        f"  チェック完了: {checked_total} 件 "
        f"/ Level1切れ {len(broken)} 件 "
        f"/ Level2切れあり {len(inner_broken)} 件"
    )
    if broken:
        print("  ── Level 1 切れ（赤枠）一覧 ──")
        for href, info in broken.items():
            print(f"    [{info['status']}] {info['label'] or '(no text)'} -> {href}")
            if info.get("cell"):
                print(f"        cell={info['cell']}")
    if inner_broken:
        print("  ── Level 2 切れあり（青枠）一覧 ──")
        for href, info in inner_broken.items():
            print(
                f"    [{info['cell'] or '-'}] {info['label'] or '(no text)'} "
                f"-> {href}"
            )
            print(
                f"        innerCheckedCount={info['innerCheckedCount']}, "
                f"innerBrokenCount={info['innerBrokenCount']}"
            )
            for li in info["innerBrokenLinks"]:
                print(f"          - [{li['status']}] {li['url']}")
    print(f"\n出力: {out_path}")


def save_empty(out_path: str):
    result = {
        "generatedAt": datetime.now().isoformat(timespec="seconds"),
        "source": HTML_PATH,
        "checked": 0,
        "brokenCount": 0,
        "broken": {},
        "innerBrokenCount": 0,
        "innerBroken": {},
    }
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)


if __name__ == "__main__":
    main()
