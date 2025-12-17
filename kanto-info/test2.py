import pandas as pd

df = pd.read_excel("shops.xlsx", dtype=str)

# 明示的に保存先パスを指定（適宜修正）
output_path = r"C:\Users\User\OneDrive\デスクトップ\kanto-info\column_names.txt"

with open(output_path, "w", encoding="utf-8") as f:
    for col in df.columns:
        f.write(f"[{col}]\n")

print(f"列名を {output_path} に書き出しました。")
