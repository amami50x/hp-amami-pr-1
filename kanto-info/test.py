import pandas as pd

# データ読み込み
df = pd.read_excel("shops.xlsx", sheet_name="Sheet1", skiprows=1, header=0, usecols="B:J")
df.columns = df.columns.str.strip()

# カラムの前処理（文字列化＋空白・改行・全角スペース除去）
for col in ["カテゴリ", "郵便", "店名"]:
    df[col] = df[col].astype(str).str.replace("\u3000", "", regex=False).str.replace("\n", "", regex=False).str.strip()

# ソート
df_sorted = df.sort_values(by=["カテゴリ", "郵便", "店名"])

# 保存（writerを使わないシンプルな形式）
df_sorted.to_excel("sorted_check.xlsx", index=False)

print("✅ ソート結果を 'sorted_check.xlsx' に保存しました。")
