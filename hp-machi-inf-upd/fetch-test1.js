async function extractTableData() {
    try {
        console.log("fetch() を開始...");
        const response = await fetch("amami.html");

        console.log("Fetch ステータス:", response.status);
        if (!response.ok) {
            throw new Error(`HTTPエラー: ${response.status}`);
        }

        const data = await response.text();
        console.log("取得データ:", data.slice(0, 500)); // 最初の500文字のみ表示

        // ✅ HTMLを解析
        const parser = new DOMParser();
        const doc = parser.parseFromString(data, "text/html");

        console.log("解析したHTML:", doc);

        // ✅ id="cell-" で始まる <td> 要素を取得
        const tableCells = doc.querySelectorAll("td[id^='cell-']");
        console.log("抽出したセル:", tableCells);

        let dropdown = document.getElementById("key_code");
        dropdown.innerHTML = '<option value="">選択してください</option>';

        tableCells.forEach(cell => {
            let value = cell.id.replace("cell-", "").replace("-", " - ");
            let optionText = `${value} : ${cell.textContent.trim()}`;
            let option = document.createElement("option");
            option.value = value;
            option.textContent = optionText;
            dropdown.appendChild(option);
        });

        hideLoadingMessage();
    } catch (error) {
        console.error("Fetch失敗:", error);
        document.getElementById("key_code").innerHTML = '<option value="">データの取得に失敗しました</option>';
        showLoadingMessage('データの取得に失敗しました');
    }
}

function showLoadingMessage(message = 'データ取得中...') {
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-message';
    loadingDiv.textContent = message;
    document.querySelector('.container').appendChild(loadingDiv);
}

function hideLoadingMessage() {
    const loadingDiv = document.querySelector('.loading-message');
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// ✅ ページロード時に `fetch()` を実行
window.onload = async () => {
    showLoadingMessage();
    await extractTableData();
};