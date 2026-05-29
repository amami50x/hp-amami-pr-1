<?php
/* cell-click-counter.php
 * 各セル（id="cell-..."）のクリック数を集計するカウンター。
 *   - POST cell=..&lang=ja|foreign  → +1（誰でも可）
 *   - GET  action=get&pw=PASSWORD   → 全集計を JSON（管理者のみ）
 *   - GET  action=report&pw=...     → 月別 CSV ダウンロード（管理者のみ）
 * 一般ユーザーには pw が無いので空オブジェクト {"cells":{}} のみ返す。
 * したがって index.html 側でバッジ表示が不可能（数字が無いため）。
 *
 * パスワードを変更するときは下の ADMIN_PW を編集してください。
 */

define('ADMIN_PW', 'amami-admin-2026');  // ★ 管理者パスワード（必要に応じて変更）

ini_set('display_errors', 0);
error_reporting(E_ALL);
$filename = __DIR__ . "/cell-clicks.json";

function is_valid_cell_id($id) {
    return is_string($id)
        && strlen($id) > 5 && strlen($id) <= 60
        && preg_match('/^cell-[A-Za-z0-9_\-]+$/', $id) === 1;
}
function normalize_lang($lang) {
    return ($lang === "foreign" || $lang === "en" || $lang === "1") ? "foreign" : "ja";
}
function load_store($file) {
    if (!file_exists($file)) return ["cells" => []];
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data["cells"]) || !is_array($data["cells"])) {
        return ["cells" => []];
    }
    return $data;
}
function save_store($file, $data) {
    $fp = fopen($file, "c+");
    if (!$fp) return false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0); rewind($fp);
        fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE));
        fflush($fp); flock($fp, LOCK_UN);
    }
    fclose($fp);
    return true;
}
function is_admin() {
    $pw = isset($_GET['pw']) ? $_GET['pw'] : (isset($_POST['pw']) ? $_POST['pw'] : '');
    return is_string($pw) && hash_equals(ADMIN_PW, $pw);
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $action = isset($_GET["action"]) ? $_GET["action"] : "get";

    if ($action === "report") {
        if (!is_admin()) { http_response_code(403); echo "forbidden"; exit; }
        $store = load_store($filename);
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=cell-clicks-report.csv");
        echo "cell_id,lang,month,count\r\n";
        foreach ($store["cells"] as $cellId => $rec) {
            foreach (["ja", "foreign"] as $lng) {
                if (!isset($rec[$lng]["month"])) continue;
                foreach ($rec[$lng]["month"] as $ym => $cnt) {
                    echo "$cellId,$lng,$ym,$cnt\r\n";
                }
            }
        }
        exit;
    }

    // action=get （既定）
    header("Content-Type: application/json; charset=UTF-8");
    if (!is_admin()) {
        // 一般ユーザーには空データのみ返す → フロントはバッジを描画しない
        echo json_encode(["cells" => new stdClass()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $store = load_store($filename);
    $out = [];
    foreach ($store["cells"] as $cellId => $rec) {
        $ja  = isset($rec["ja"]["total"])      ? (int)$rec["ja"]["total"]      : 0;
        $fo  = isset($rec["foreign"]["total"]) ? (int)$rec["foreign"]["total"] : 0;
        $tot = isset($rec["total"])            ? (int)$rec["total"]            : ($ja + $fo);
        $out[$cellId] = ["ja" => $ja, "foreign" => $fo, "total" => $tot];
    }
    echo json_encode(["cells" => $out], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === "POST") {
    // POST はパスワード不要（一般ユーザーのクリックを集めるため）
    $cellId = isset($_POST["cell"]) ? trim($_POST["cell"]) : "";
    $lang   = normalize_lang(isset($_POST["lang"]) ? $_POST["lang"] : "ja");
    if (!is_valid_cell_id($cellId)) {
        http_response_code(400);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["ok" => false, "error" => "invalid cell id"]);
        exit;
    }
    $store = load_store($filename);
    if (!isset($store["cells"][$cellId]) || !is_array($store["cells"][$cellId])) {
        $store["cells"][$cellId] = ["ja" => ["total"=>0,"month"=>[]], "foreign" => ["total"=>0,"month"=>[]], "total" => 0];
    }
    $rec = $store["cells"][$cellId];
    foreach (["ja", "foreign"] as $lng) {
        if (!isset($rec[$lng]) || !is_array($rec[$lng])) $rec[$lng] = ["total"=>0,"month"=>[]];
        if (!isset($rec[$lng]["month"]) || !is_array($rec[$lng]["month"])) $rec[$lng]["month"] = [];
    }
    $ym = date("Y-m");
    $rec[$lang]["total"]      = (isset($rec[$lang]["total"]) ? (int)$rec[$lang]["total"] : 0) + 1;
    $rec[$lang]["month"][$ym] = (isset($rec[$lang]["month"][$ym]) ? (int)$rec[$lang]["month"][$ym] : 0) + 1;
    $rec["total"]             = (isset($rec["total"]) ? (int)$rec["total"] : 0) + 1;
    $store["cells"][$cellId]  = $rec;
    save_store($filename, $store);

    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["ok" => true], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo "method not allowed";
