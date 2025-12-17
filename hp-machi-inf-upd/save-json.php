<?php
// エラー表示
ini_set('display_errors', 1);
error_reporting(E_ALL);

// POST以外は拒否
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'POSTメソッドのみ対応']);
    exit;
}

// JSONとして読み込み
$data = file_get_contents("php://input");
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'データが空です']);
    exit;
}

// JSONとして保存
$filePath = __DIR__ . '/data/data.json';
if (!file_put_contents($filePath, $data)) {
    http_response_code(500);
    echo json_encode(['error' => 'ファイル保存に失敗しました']);
    exit;
}

// 成功応答
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => '保存に成功しました']);
?>
