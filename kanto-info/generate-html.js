const xlsx = require('xlsx');
const fs = require('fs');

// Excelファイルを読み込む
const workbook = xlsx.readFile('shops.xlsx');
const sheetName = workbook.SheetNames[0];
const sheet = workbook.Sheets[sheetName];
const data = xlsx.utils.sheet_to_json(sheet);

// HTMLを作成
let htmlContent = `
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>奄美関係のお店情報</title>
    <style>
        body { font-family: Arial, sans-serif; background: #e0f7fa; margin: 20px; }
        .shop { border: 1px solid #ccc; border-radius: 10px; padding: 15px; margin-bottom: 20px; background: white; }
        .shop img { max-width: 100%; height: auto; border-radius: 10px; }
        h2 { color: #00796b; }
    </style>
</head>
<body>
    <h1>奄美関係のお店情報</h1>
`;

// 各お店データを追加
data.forEach(shop => {
    htmlContent += `
    <div class="shop">
        <h2>${shop['店名']}</h2>
        <p><strong>住所:</strong> ${shop['住所']}</p>
        <p><strong>電話番号:</strong> ${shop['電話番号']}</p>
        <p><strong>責任者:</strong> ${shop['責任者名']}</p>
        <p>${shop['PR文']}</p>
        ${shop['写真URL'] ? `<img src="${shop['写真URL']}" alt="${shop['店名']}">` : ''}
    </div>
    `;
});

htmlContent += `
</body>
</html>
`;

// index.htmlとして保存
fs.writeFileSync('index.html', htmlContent, 'utf-8');

console.log('✅ HTMLファイルを作成しました！');
