import os
import pandas as pd

print("📂 現在の作業フォルダ:", os.getcwd())
print("✅ Pythonスクリプトが実行されました")

# Excelファイル読み込み
df_raw = pd.read_excel("shops.xlsx", sheet_name="Sheet1")

# カラム名の空白除去
df_raw.columns = df_raw.columns.str.strip()
print("認識されたカラム名:", df_raw.columns.tolist())

# セル内の文字列の空白除去
df_cleaned = df_raw.applymap(lambda x: x.strip() if isinstance(x, str) else x)

# ソート処理（カテゴリ → 郵便 → 店名）
sort_columns = ["郵便","カテゴリ","店名"]
missing_columns = [col for col in sort_columns if col not in df_cleaned.columns]

if missing_columns:
    raise KeyError(f"❌ ソート対象のカラムが見つかりません: {missing_columns}")

df_sorted = df_cleaned.sort_values(by=sort_columns)

# 保存処理
try:
    df_sorted.to_excel("shops.xlsx", index=False)
    print("✅ 全カラム保持＋ソートして 'shops.xlsx' に上書き保存されました！")
except PermissionError:
    print("❌ Excelファイルが開かれているため、保存できません。ファイルを閉じて再実行してください。")