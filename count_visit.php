<?php
header("Content-Type: text/plain; charset=UTF-8");

// 訪問回数を記録するCSVファイルの保存名
define('COUNTER_FILE', 'visit_count.csv');

/**
 * カウント数をCSVから読み込む関数（絶対に安全に読み込む仕様に強化）
 */
function get_current_counts() {
    if (!file_exists(COUNTER_FILE) || filesize(COUNTER_FILE) == 0) {
        // ★もしファイルが空っぽ、または無い場合の【初期値】を設定できます！
        // 元の数字が分かれば、ここの「0」をご希望の数字（例: 150 や 42）に書き換えてください。
        return ['japanese' => 0, 'foreign' => 0];
    }
    
    $file = fopen(COUNTER_FILE, 'r');
    if ($file) {
        $data = fgetcsv($file);
        fclose($file);
        
        return [
            'japanese' => (isset($data[0]) && is_numeric($data[0])) ? (int)$data[0] : 0,
            'foreign'  => (isset($data[1]) && is_numeric($data[1])) ? (int)$data[1] : 0
        ];
    }
    return ['japanese' => 0, 'foreign' => 0];
}

/**
 * カウント数を安全に「上書き保存」する関数（ロックをかけて破損を防ぎます）
 */
function save_counts($japanese, $foreign) {
    // 一瞬だけファイルを開いて上書きする（排他的ロック付きで安全性を最大に）
    $file = fopen(COUNTER_FILE, 'c+'); 
    if ($file) {
        if (flock($file, LOCK_EX)) { // 他のアクセスと重なっても壊れないようにロック
            ftruncate($file, 0);     // 既存の古い文字を綺麗にクリア
            fputcsv($file, [$japanese, $foreign]);
            fflush($file);
            flock($file, LOCK_UN);   // ロック解除
        }
        fclose($file);
    }
}

// ========================================================
// 離脱時にHTML（JavaScript）から届いた合図を判定
// ========================================================
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 届いたアクションに応じて、現在の数字に「＋1」だけを確実に行う
if ($action === 'final_ja') {
    $counts = get_current_counts();
    $counts['japanese'] += 1; // 日本語を確実にプラス1
    save_counts($counts['japanese'], $counts['foreign']);
    echo "Success: Japanese incremented.";
} 
elseif ($action === 'final_foreign') {
    $counts = get_current_counts();
    $counts['foreign'] += 1;  // 外国語を確実にプラス1
    save_counts($counts['japanese'], $counts['foreign']);
    echo "Success: Foreign incremented.";
}
else {
    echo "Error: Invalid action.";
}
?>