import json
from flask import Flask, request, jsonify

app = Flask(__name__)

# JSONデータの読み込み
def load_data():
    with open("data.json", "r", encoding="utf-8") as file:
        return json.load(file)

# JSONデータの更新
def save_data(data):
    with open("data.json", "w", encoding="utf-8") as file:
        json.dump(data, file, ensure_ascii=False, indent=4)

# 選択した市町村・ジャンルのデータを取得
@app.route("/get_data", methods=["GET"])
def get_data():
    city = request.args.get("city")
    category = request.args.get("category")
    
    data = load_data()
    filtered_data = [entry for entry in data if entry["市町村名"] == city and entry["ジャンル"] == category]
    
    return jsonify(filtered_data)

# データのアップロード（更新）
@app.route("/upload_data", methods=["POST"])
def upload_data():
    new_data = request.json
    save_data(new_data)
    return jsonify({"status": "success", "message": "データを更新しました"})

if __name__ == "__main__":
    app.run(debug=True)