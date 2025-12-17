import pandas as pd

# Excelファイルを読み込む
df_raw = pd.read_excel("shops.xlsx", sheet_name="Sheet1", header=0)

# ✅ 取得した列名を確認
print("取得した列名:", df_raw.columns.tolist())