<?php
$filename = "access-count.json";

// エラー表示ON（テスト時のみ推奨）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 初期値
$defaultCounts = ["japaneseCount" => 0, "foreignCount" => 0];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $isForeign = isset($_POST["isForeign"]) && $_POST["isForeign"] == "1";

    // カウントファイルが存在しなければ初期化（上書きはしない）
    if (!file_exists($filename)) {
        file_put_contents($filename, json_encode($defaultCounts));
    }

    // 既存ファイル読み込み → JSONが壊れてたら初期化
    $counts = json_decode(file_get_contents($filename), true);
    if (!is_array($counts)) {
        $counts = $defaultCounts;
    }

    // カウント更新
    if ($isForeign) {
        $counts["foreignCount"]++;
    } else {
        $counts["japaneseCount"]++;
    }

    // 保存
    file_put_contents($filename, json_encode($counts));

    // 応答
    header("Content-Type: application/json");
    echo json_encode($counts);
    exit;
}
?>
