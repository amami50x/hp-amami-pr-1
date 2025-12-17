async function extractTableData() {
    try {
        console.log("fetch() を開始...");
        const response = await fetch("amami.html");

        if (!response.ok) {
            throw new Error(`HTTPエラー: ${response.status}`);
        }

        const data = await response.text();
        console.log("取得データ:", data.slice(0, 500));

        // HTMLを解析
        const parser = new DOMParser();
        const doc = parser.parseFromString(data, "text/html");

        const tableCells = doc.querySelectorAll("td[id^='cell-']");
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
        console.error("データ抽出エラー:", error);
        document.getElementById("key_code").innerHTML = '<option value="">データの取得に失敗しました</option>';
        showLoadingMessage('データの取得に失敗しました');
    }
}

async function submitForm(event) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);

    try {
        const response = await fetch("submit.php", {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! status: ${response.status}, message: ${errorText || 'Unknown error'}`);
        }

        const data = await response.json();
        alert(data.message);
        form.reset();
    } catch (error) {
        console.error("送信エラー:", error);
        alert("データの送信に失敗しました: " + error.message);
    }
}

window.onload = () => {
    showLoadingMessage();
    extractTableData();
};