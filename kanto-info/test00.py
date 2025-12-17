import pandas as pd

# Excelファイルの読み込み（B〜J列、2行目からデータ）
df = pd.read_excel("shops.xlsx", sheet_name="Sheet1", skiprows=1, header=0, usecols="B:J")

# カラム名の前後の空白を削除
df.columns = df.columns.str.strip()

# カラム名を表示
print("📋 実際のカラム名一覧:")
for col in df.columns:
    print(f"- '{col}'")
