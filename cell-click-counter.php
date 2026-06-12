<?php
/* cell-click-counter.php
 * 「実際にユーザーがどのURL（サイト）へ遷移したか」を集計するカウンター。
 *
 * リクエスト:
 *   - POST cell=..&lang=ja|foreign&url=遷移先URL → +1（誰でも可・パスワード不要）
 *   - GET  action=get&pw=PASSWORD    → セル集計＋URL内訳を JSON（管理者のみ）
 *   - GET  action=urls&pw=...        → URL別 CSV ダウンロード（管理者のみ）
 *   - GET  action=report&pw=...      → URL×言語×月別 CSV（管理者のみ）
 *
 * 集計方針:
 *   - 保存は「URL をキー」にした 1 つのマップ（urls）。これが一番実態に近く単純。
 *   - 各 URL は { cell, label, ja, foreign, total, month } を持つ。
 *   - 「ポップアップを開くだけ」の操作は数えない（フロント側で除外）。
 *     数えるのは実リンク（http/https/mailto/tel）への遷移クリックのみ。
 *   - 画面のセル件数は、そのセルに属する URL 件数の合計（サーバーで集計して返す）。
 *
 * 一般ユーザーには pw が無いので空オブジェクト {"cells":{}} のみ返す
 * （＝フロントはバッジを描画しない）。
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
// 集計対象にする実 URL / 内部アクションだけ通す（不正・長すぎ・プレースホルダは弾く）
function sanitize_url($u) {
    if (!is_string($u)) return "";
    $u = trim($u);
    if ($u === "" || strlen($u) > 500) return "";
    if (!preg_match('#^(https?:|mailto:|tel:|action:)#i', $u)) return "";
    return $u;
}
// 表示用ラベル（ドメイン名。mailto/tel はそのまま）
function url_label($u) {
    if ($u === "action:bgm-window") return "BGM再生";
    if (stripos($u, "mailto:") === 0) return substr($u, 7);
    if (stripos($u, "tel:") === 0)    return substr($u, 4);
    $host = parse_url($u, PHP_URL_HOST);
    if (!$host) return $u;
    return preg_replace('/^www\./i', '', $host);
}
function load_store($file) {
    if (!file_exists($file)) return ["urls" => []];
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    if (!is_array($data)) return ["urls" => []];
    if (!isset($data["urls"]) || !is_array($data["urls"])) $data["urls"] = [];
    return $data;
}
function save_store($file, $data) {
    $fp = @fopen($file, "c+");
    if (!$fp) return false;
    $ok = false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0); rewind($fp);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $written = fwrite($fp, $json);
        fflush($fp); flock($fp, LOCK_UN);
        $ok = ($written !== false && $written >= strlen($json));
    }
    fclose($fp);
    return $ok;
}
function is_admin() {
    $pw = isset($_GET['pw']) ? $_GET['pw'] : (isset($_POST['pw']) ? $_POST['pw'] : '');
    return is_string($pw) && hash_equals(ADMIN_PW, $pw);
}
// URL マップ → セル単位に集計（バッジ表示用）。URL 内訳も入れ子で返す。
function aggregate_by_cell($store) {
    $cells = [];
    foreach ($store["urls"] as $url => $rec) {
        $cellId = isset($rec["cell"]) ? $rec["cell"] : "";
        if (!is_valid_cell_id($cellId)) continue;
        $ja  = isset($rec["ja"])      ? (int)$rec["ja"]      : 0;
        $fo  = isset($rec["foreign"]) ? (int)$rec["foreign"] : 0;
        $tot = isset($rec["total"])   ? (int)$rec["total"]   : ($ja + $fo);
        if (!isset($cells[$cellId])) {
            $cells[$cellId] = ["ja" => 0, "foreign" => 0, "total" => 0, "urls" => []];
        }
        $cells[$cellId]["ja"]      += $ja;
        $cells[$cellId]["foreign"] += $fo;
        $cells[$cellId]["total"]   += $tot;
        $cells[$cellId]["urls"][$url] = [
            "ja" => $ja, "foreign" => $fo, "total" => $tot,
            "label" => isset($rec["label"]) ? $rec["label"] : url_label($url),
        ];
    }
    return $cells;
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $action = isset($_GET["action"]) ? $_GET["action"] : "get";

    if ($action === "report") {
        if (!is_admin()) { http_response_code(403); echo "forbidden"; exit; }
        $store = load_store($filename);
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=cell-clicks-report.csv");
        echo "cell_id,url,lang,month,count\r\n";
        foreach ($store["urls"] as $url => $rec) {
            $cellId = isset($rec["cell"]) ? $rec["cell"] : "";
            if (!isset($rec["month"]) || !is_array($rec["month"])) continue;
            $u = '"' . str_replace('"', '""', $url) . '"';
            foreach ($rec["month"] as $ym => $byLang) {
                if (!is_array($byLang)) continue;
                foreach (["ja", "foreign"] as $lng) {
                    if (!isset($byLang[$lng])) continue;
                    echo "$cellId,$u,$lng,$ym," . (int)$byLang[$lng] . "\r\n";
                }
            }
        }
        exit;
    }

    if ($action === "urls") {
        if (!is_admin()) { http_response_code(403); echo "forbidden"; exit; }
        $store = load_store($filename);
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=cell-clicks-urls.csv");
        echo "cell_id,url,label,ja,foreign,total\r\n";
        foreach ($store["urls"] as $url => $rec) {
            $cellId = isset($rec["cell"]) ? $rec["cell"] : "";
            $label  = isset($rec["label"]) ? $rec["label"] : url_label($url);
            $u = '"' . str_replace('"', '""', $url) . '"';
            $l = '"' . str_replace('"', '""', $label) . '"';
            $ja  = isset($rec["ja"])      ? (int)$rec["ja"]      : 0;
            $fo  = isset($rec["foreign"]) ? (int)$rec["foreign"] : 0;
            $tot = isset($rec["total"])   ? (int)$rec["total"]   : ($ja + $fo);
            echo "$cellId,$u,$l,$ja,$fo,$tot\r\n";
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
    $cells = aggregate_by_cell($store);
    if (empty($cells)) {
        echo json_encode(["cells" => new stdClass()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(["cells" => $cells], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method === "POST") {
    // POST はパスワード不要（一般ユーザーのクリックを集めるため）
    $cellId = isset($_POST["cell"]) ? trim($_POST["cell"]) : "";
    $lang   = normalize_lang(isset($_POST["lang"]) ? $_POST["lang"] : "ja");
    $url    = sanitize_url(isset($_POST["url"]) ? $_POST["url"] : "");
    $label  = isset($_POST["label"]) ? trim((string)$_POST["label"]) : "";

    if (!is_valid_cell_id($cellId)) {
        http_response_code(400);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["ok" => false, "error" => "invalid cell id"]);
        exit;
    }
    if ($url === "") {
        // URL 遷移を伴わないクリックは集計しない（ポップアップを開くだけ等）
        http_response_code(400);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["ok" => false, "error" => "missing url"]);
        exit;
    }

    $store = load_store($filename);
    if (!isset($store["urls"][$url]) || !is_array($store["urls"][$url])) {
        $store["urls"][$url] = [
            "cell"    => $cellId,
            "label"   => url_label($url),
            "ja"      => 0,
            "foreign" => 0,
            "total"   => 0,
            "month"   => [],
        ];
    }
    $rec = $store["urls"][$url];
    $rec["cell"]  = $cellId;                 // 最新のセル所属で更新
    if ($label !== "") $rec["label"] = substr($label, 0, 120);
    if (!isset($rec["label"]) || $rec["label"] === "") $rec["label"] = url_label($url);
    if (!isset($rec["month"]) || !is_array($rec["month"])) $rec["month"] = [];

    $ym = date("Y-m");
    if (!isset($rec["month"][$ym]) || !is_array($rec["month"][$ym])) {
        $rec["month"][$ym] = ["ja" => 0, "foreign" => 0];
    }
    $rec[$lang]            = (isset($rec[$lang]) ? (int)$rec[$lang] : 0) + 1;
    $rec["total"]          = (isset($rec["total"]) ? (int)$rec["total"] : 0) + 1;
    $rec["month"][$ym][$lang] = (isset($rec["month"][$ym][$lang]) ? (int)$rec["month"][$ym][$lang] : 0) + 1;

    $store["urls"][$url] = $rec;
    $saved = save_store($filename, $store);

    header("Content-Type: application/json; charset=UTF-8");
    if (!$saved) {
        // ファイルに書き込めなかった（パーミッション不足など）→ 正直に失敗を返す
        http_response_code(500);
        echo json_encode(["ok" => false, "error" => "could not save (check file permission)"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(["ok" => true, "total" => (int)$rec["total"]], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(405);
echo "method not allowed";
