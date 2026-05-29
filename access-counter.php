<?php
/**
 * 訪問回数カウンタ（旅行情報ページ用）
 *
 *  GET
 *      現在のカウントを JSON で返す。
 *
 *  POST action=visit_login&target=ja|foreign
 *      ログイン時 +1。仕様上 target=ja 固定の想定だが、将来の外国語LOGIN
 *      も視野に入れて target を取れるようにしておく。
 *
 *  POST action=switch_to_foreign
 *      ログイン後（日本語で +1 済み）に外国語へ切替が確認された時に呼ぶ。
 *      日本語 -1、外国語 +1（一度きり）。日本語が 0 のときは減らさない。
 *
 *  保存先: access-count.json
 *      形式: {"japaneseCount": N, "foreignCount": M}
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

$filename      = __DIR__ . '/access-count.json';
$defaultCounts = array('japaneseCount' => 0, 'foreignCount' => 0);

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

function ac_load_counts($filename, $defaults) {
	$fp = fopen($filename, 'c+');
	if (!$fp) {
		return $defaults;
	}
	$counts = $defaults;
	if (flock($fp, LOCK_SH)) {
		rewind($fp);
		$raw = stream_get_contents($fp);
		$counts = ac_normalize_counts($raw, $defaults);
		flock($fp, LOCK_UN);
	}
	fclose($fp);
	return $counts;
}

function ac_update_counts($filename, $defaults, $updater) {
	$fp = fopen($filename, 'c+');
	if (!$fp) {
		return $defaults;
	}
	$counts = $defaults;
	if (flock($fp, LOCK_EX)) {
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
	}
	fclose($fp);
	return $counts;
}

function ac_send_json($counts) {
	header('Content-Type: application/json; charset=UTF-8');
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
