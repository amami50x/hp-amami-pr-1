<?php
$filename = "count.txt";

// 初期化（なければ）
if (!file_exists($filename)) {
    file_put_contents($filename, "jp=0\nen=0");
}

// カウントデータ読み込み
$counts = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$jpCount = 0;
$enCount = 0;

foreach ($counts as $line) {
    list($key, $value) = explode('=', $line);
    if ($key === 'jp') $jpCount = (int)$value;
    if ($key === 'en') $enCount = (int)$value;
}

// POSTで言語が送られてきたらカウント増加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lang'])) {
    $lang = $_POST['lang'];
    if ($lang === 'jp') $jpCount++;
    if ($lang === 'en') $enCount++;

    // 保存
    file_put_contents($filename, "jp=$jpCount\nen=$enCount");
}

// JSONで現在のカウントを返す
header('Content-Type: application/json');
echo json_encode(['jp' => $jpCount, 'en' => $enCount]);
