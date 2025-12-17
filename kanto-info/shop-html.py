import openpyxl

# Excelファイルを開く
wb = openpyxl.load_workbook('shops.xlsx')
sheet = wb.active

# HTMLのスタート
html = '''
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>関東地域の奄美関連店舗一覧</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>関東地域の奄美関連店舗一覧</h1>
<table border="1">
    <thead>
        <tr>
'''

# 1行目（タイトル行）をテーブルのヘッダーにする
for cell in sheet[1]:
    html += f'<th>{cell.value}</th>'

html += '''
        </tr>
    </thead>
    <tbody>
'''

# 2行目以降（データ）をテーブルにする
for row in sheet.iter_rows(min_row=2, values_only=True):
    html += '<tr>'
    for cell in row:
        html += f'<td>{cell if cell is not None else ""}</td>'
    html += '</tr>'

# HTMLの終わり
html += '''
    </tbody>
</table>
</body>
</html>
'''

# HTMLファイルに書き出し
with open('index.html', 'w', encoding='utf-8') as f:
    f.write(html)

print('index.htmlを作成しました！')
