from flask import Flask, request, jsonify
import shutil
import os
from bs4 import BeautifulSoup
import requests
import time

app = Flask(__name__)

# **クリック回数を管理する辞書**
cell_click_count = {}

@app.route('/log_cell_click', methods=['POST'])
def record_cell_click():
    data = request.json
    cell_identifier = data.get("cell_id")

    if not cell_identifier:
        return jsonify({"error": "セルIDがありません"}), 400

    cell_click_count[cell_identifier] = cell_click_count.get(cell_identifier, 0) + 1
    print(f"{cell_identifier} のアクセス回数: {cell_click_count[cell_identifier]}")

    return jsonify({
        "message": "アクセス回数を記録しました",
        "count": cell_click_count[cell_identifier]
    }), 200

# **HTMLのコピー処理**
def copy_base_html(source_file, processed_file):
    try:
        shutil.copy(source_file, processed_file)
        print(f"✅ {source_file} を {processed_file} にコピーしました")
    except Exception as e:
        print(f"❌ HTMLコピーエラー: {e}")

# **リンクのステータス判定**
def verify_link_status(url):
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36"
    }

    try:
        if url.startswith("javascript:void(0)") or url == "#":
            return True

        response = requests.get(url, headers=headers, allow_redirects=True, timeout=5)
        return response.status_code in [200, 301, 302]
    except requests.exceptions.RequestException:
        return False

# **セルの背景色を更新**
def update_cell_status(original_html, processed_html):
    copy_base_html(original_html, processed_html)

    if not os.path.exists(processed_html):
        print(f"❌ {processed_html} の作成に失敗しました")
        return

    with open(processed_html, encoding="utf-8") as f:
        soup = BeautifulSoup(f, "lxml")

    for cell in soup.find_all("td"):
        link = cell.find("a")

        if link and link.get("href"):
            url = link["href"].strip()

            if url.startswith("http"):
                valid = verify_link_status(url)
                bg_color = "#ccffcc" if valid else "#ffdddd"
            elif url.startswith("javascript:void(0)") or url == "#":
                bg_color = "#ccffcc"
            else:
                bg_color = "#ffe4b5"

        else:
            bg_color = "#ccccff"

        current_style = cell.get("style", "")
        cell["style"] = f"background-color: {bg_color} !important;"

        time.sleep(0.1)

    with open(processed_html, "w", encoding="utf-8") as f:
        f.write(str(soup))

    print(f"✅ {processed_html} の作成が完了しました")

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)

# **処理開始（`amami_click.html` を作成）**
update_cell_status("amami.html", "amami_click.html")