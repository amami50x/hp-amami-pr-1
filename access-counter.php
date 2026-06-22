<?php
/**
 * 訪問回数カウンタ（旅行情報ページ用）
 *
 *  GET  現在のカウントを JSON で返す。
 *  POST action=visit_login&target=ja|foreign  ログイン時 +1
 *  POST action=switch_to_foreign               日本語 -1 / 外国語 +1
 *
 *  保存先: access-count.json
 *  @updated 2026-06-20  flock 待ちで初回表示が遅くならないようタイムアウト付き
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

$filename      = __DIR__ . '/access-count.json';
$defaultCounts = array('japaneseCount' => 0, 'foreignCount' => 0);
/** flock 最大待ち秒（初回・同時アクセス時の長時間ブロック防止） */
define('AC_FLOCK_TIMEOUT_SEC', 2.0);

if (!file_exists($filename)) {
	file_put_contents($filename, json_encode($defaultCounts));
}

function ac_normalize_counts($raw, $defaults) {
	$data = json_decode((string) $raw, true);
	if (!is_array($data)) {
		$data = $defaults;
	}
	$data['japaneseCount'] = isset($data['japaneseCount']) && is_numeric($data['japaneseCount'])
		? (int) $data['japaneseCount'] : 0;
	$data['foreignCount']  = isset($data['foreignCount']) && is_numeric($data['foreignCount'])
		? (int) $data['foreignCount']  : 0;
	return $data;
}

/**
 * flock with timeout (LOCK_NB retry). Returns false if lock not acquired.
 *
 * @param resource $fp
 * @param int      $operation LOCK_SH or LOCK_EX
 * @return bool
 */
function ac_try_flock($fp, $operation) {
	$deadline = microtime(true) + AC_FLOCK_TIMEOUT_SEC;
	while (microtime(true) < $deadline) {
		if (flock($fp, $operation | LOCK_NB)) {
			return true;
		}
		usleep(50000);
	}
	return false;
}

/** ロックなしの読み取り（表示用フォールバック） */
function ac_read_counts_unlocked($filename, $defaults) {
	$raw = @file_get_contents($filename);
	if ($raw === false || $raw === '') {
		return $defaults;
	}
	return ac_normalize_counts($raw, $defaults);
}

function ac_load_counts($filename, $defaults) {
	$fp = @fopen($filename, 'c+');
	if (!$fp) {
		return ac_read_counts_unlocked($filename, $defaults);
	}
	$counts = $defaults;
	if (ac_try_flock($fp, LOCK_SH)) {
		rewind($fp);
		$raw = stream_get_contents($fp);
		$counts = ac_normalize_counts($raw, $defaults);
		flock($fp, LOCK_UN);
	} else {
		$counts = ac_read_counts_unlocked($filename, $defaults);
	}
	fclose($fp);
	return $counts;
}

function ac_update_counts($filename, $defaults, $updater) {
	$fp = @fopen($filename, 'c+');
	if (!$fp) {
		return $defaults;
	}
	$counts = $defaults;
	if (ac_try_flock($fp, LOCK_EX)) {
		rewind($fp);
		$raw = stream_get_contents($fp);
		$counts = ac_normalize_counts($raw, $defaults);
		$counts = $updater($counts);
		$counts = ac_normalize_counts(json_encode($counts), $defaults);
		ftruncate($fp, 0);
		rewind($fp);
		fwrite($fp, json_encode(array(
			'japaneseCount' => (int) $counts['japaneseCount'],
			'foreignCount'  => (int) $counts['foreignCount'],
		)));
		fflush($fp);
		flock($fp, LOCK_UN);
	} else {
		// ロック取得できなければ加算せず現在値を返す（表示を遅らせない）
		$counts = ac_read_counts_unlocked($filename, $defaults);
	}
	fclose($fp);
	return $counts;
}

function ac_send_json($counts) {
	header('Content-Type: application/json; charset=UTF-8');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	echo json_encode($counts);
}

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

if ($method === 'GET') {
	$counts = ac_load_counts($filename, $defaultCounts);
	ac_send_json($counts);
	exit;
}

if ($method === 'POST') {
	$action = isset($_POST['action']) ? (string) $_POST['action'] : '';
	$target = isset($_POST['target']) ? (string) $_POST['target'] : '';

	if ($action === 'visit_login') {
		$counts = ac_update_counts($filename, $defaultCounts, function ($counts) use ($target) {
			if ($target === 'foreign') {
				$counts['foreignCount']++;
			} else {
				$counts['japaneseCount']++;
			}
			return $counts;
		});
		ac_send_json($counts);
		exit;
	}

	if ($action === 'switch_to_foreign') {
		$counts = ac_update_counts($filename, $defaultCounts, function ($counts) {
			if ($counts['japaneseCount'] > 0) {
				$counts['japaneseCount']--;
			}
			$counts['foreignCount']++;
			return $counts;
		});
		ac_send_json($counts);
		exit;
	}

	http_response_code(400);
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode(array('error' => 'invalid_action'));
	exit;
}

http_response_code(405);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(array('error' => 'method_not_allowed'));
exit;
