<?php
header('Content-Type: application/json');

$filename = 'access_counts.json';

if (!file_exists($filename)) {
    // ファイルが存在しない場合は初期化
    $data = array('japanese' => 0, 'foreign' => 0);
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode($data);
    exit;
}

$json = file_get_contents($filename);
$data = json_decode($json, true);

// データが正しく読み込まれたかチェック
if (!isset($data['japanese'])) {
    $data['japanese'] = 0;
}
if (!isset($data['foreign'])) {
    $data['foreign'] = 0;
}

// JSONで出力
echo json_encode($data);
?>
