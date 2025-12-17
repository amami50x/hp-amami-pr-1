import shutil
import os
from bs4 import BeautifulSoup
import requests
import time

# **元のHTMLをコピー**
def copy_original_html(source_file, destination_file):
    try:
        shutil.copy(source_file, destination_file)
        print(f"✅ {source_file} を {destination_file} にコピーしました")
    except Exception as e:
        print(f"❌ ファイルコピーエラー: {e}")

# **リンクの有効性を判定**
def check_link(url):
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36"
    }

    try:
        # **JavaScriptアクション (BGM再生など) は正常扱い**
        if url.startswith("javascript:void(0)") or url == "#":
            return True  # JavaScript動作はエラー判定しない

        response = requests.get(url, headers=headers, allow_redirects=True, timeout=5)
        return response.status_code in [200, 301, 302]  # 200, 301, 302 は正常判定

    except requests.exceptions.RequestException:
        return False  # アクセス不可の場合はエラー

# **HTMLを処理し、セルの背景色を更新**
def process_html_with_highlight(input_file, output_file):
    # **元のHTMLをコピー**
    copy_original_html(input_file, output_file)

    # **コピーしたHTMLが存在するか確認**
    if not os.path.exists(output_file):
        print(f"❌ {output_file} の生成に失敗しました")
        return

    # **コピーしたHTMLを処理**
    with open(output_file, encoding="utf-8") as f:
        soup = BeautifulSoup(f, "lxml")

    for td in soup.find_all("td"):
        link = td.find("a")

        if link and link.get("href"):
            url = link["href"].strip()

            # **URLの種類で背景色を設定**
            if url.startswith("http"):
                ok = check_link(url)
                color = "#ccffcc" if ok else "#ffdddd"
            elif url.startswith("javascript:void(0)") or url == "#":
                color = "#ccffcc"  # JavaScriptアクションは正常扱い
            else:
                color = "#ffe4b5"  # 外部リンクでない

        else:
            color = "#ccccff"  # リンクなし

        # **既存のスタイルを考慮して背景色を追加**
        existing_style = td.get("style", "")
        td["style"] = f"background-color: {color} !important;"

        time.sleep(0.1)

    # **結果を保存**
    with open(output_file, "w", encoding="utf-8") as f:
        f.write(str(soup))

    print(f"✅ {output_file} の作成が完了しました")

# **実行**
process_html_with_highlight("amami.html", "amami_checked.html")