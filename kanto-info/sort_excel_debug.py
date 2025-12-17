import pandas as pd

# 読み込み時にすべて文字列として読み込む（型によるトラブル防止）
df = pd.read_excel("shops.xlsx", dtype=str)

# 列名のリスト表示（空白や見た目と異なる可能性あり）
print("列名一覧：")
print(df.columns.tolist())

# データ型表示
print("\nデータ型一覧：")
print(df.dtypes)

# 最初の数行を表示
print("\nデータプレビュー：")
print(df.head())
