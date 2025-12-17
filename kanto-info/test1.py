import pandas as pd

df = pd.read_excel("shops.xlsx", dtype=str)

with open("column_names.txt", "w", encoding="utf-8") as f:
    for col in df.columns:
        f.write(f"[{col}]\n")
