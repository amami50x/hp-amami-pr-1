import json

# JSONファイルを読み込む
with open("data.json", "r", encoding="utf-8") as file:
    data = json.load(file)

# データの確認
print(data)  