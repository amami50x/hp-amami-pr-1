<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>観光情報管理</title>
  <style>
    body { font-family: sans-serif; margin: 20px; }
    .item { border-bottom: 1px solid #ccc; padding: 10px 0; }
    img { max-width: 150px; display: block; margin-top: 5px; }
    input, textarea { width: 100%; margin-top: 4px; }
    .hidden { display: none; }
  </style>
</head>
<body>
  <h1>観光情報データ管理</h1>
  <input type="text" id="search" placeholder="名称・説明・住所などで検索">
  <div id="list">読み込み中...</div>

  <script>
    let rawData = [];

    // 表示更新
    function render(data) {
      const list = document.getElementById('list');
      list.innerHTML = '';
      data.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = 'item';

        div.innerHTML = `
          <strong contenteditable="true" onblur="updateField(${index}, 'name', this.textContent)">${item.name}</strong><br>
          <textarea onblur="updateField(${index}, 'description', this.value)">${item.description}</textarea><br>
          <input type="text" value="${item.url}" onblur="updateField(${index}, 'url', this.value)"><br>
          <input type="text" value="${item.address}" onblur="updateField(${index}, 'address', this.value)"><br>
          電話: <input type="text" value="${item.phone}" onblur="updateField(${index}, 'phone', this.value)"><br>
          担当者: <input type="text" value="${item.contact_person}" onblur="updateField(${index}, 'contact_person', this.value)"><br>
          登録日時: ${item.timestamp}<br>
          ${item.image_path ? `<img src="${item.image_path}">` : ''}
          <button onclick="deleteItem(${index})">削除</button>
        `;

        list.appendChild(div);
      });
    }

    // フィールド更新
    function updateField(index, field, value) {
      rawData[index][field] = value;
      saveToFile();
    }

    // 削除
    function deleteItem(index) {
      if (confirm('本当に削除しますか？')) {
        rawData.splice(index, 1);
        saveToFile();
      }
    }

    // JSONファイルに保存（仮処理: 通常はサーバーに送信）
    function saveToFile() {
      fetch('save-json.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(rawData)
      })
      .then(res => res.json())
      .then(res => {
        if (res.success) render(rawData);
        else alert('保存に失敗しました');
      });
    }

    // 検索
    document.getElementById('search').addEventListener('input', e => {
      const q = e.target.value.toLowerCase();
      const filtered = rawData.filter(item =>
        Object.values(item).some(v => typeof v === 'string' && v.toLowerCase().includes(q))
      );
      render(filtered);
    });

    // 初期読み込み
    fetch('data/data.json')
      .then(res => res.json())
      .then(data => {
        rawData = data;
        render(data);
      })
      .catch(err => {
        document.getElementById('list').textContent = '読み込みに失敗しました';
      });
  </script>
</body>
</html>