<?php
/**
 * link-status-check.php
 *
 * さくらレンタルサーバー上で動作する、テーブル内リンクの到達性チェッカー。
 * index.html 内の全 <table> から外部URLを抽出し、HTTPアクセスで生死を判定して
 * link-status.json に書き出す。ブラウザで本ファイルを開けば実行・進捗表示される。
 *
 * 使い方:
 *   1) ブラウザでアクセス
 *      https://violetfoal2.sakura.ne.jp/hp-amami-pr-1/link-status-check.php
 *   2) cron 定期実行（さくらのコントロールパネル → 「CRON設定」）
 *      curl -s https://violetfoal2.sakura.ne.jp/hp-amami-pr-1/link-status-check.php?cron=1 > /dev/null
 *
 * 出力:
 *   link-status.json （同じディレクトリに上書き）
 *
 * 仕様:
 *   - <table> 内の <a href="..."> から http(s) URL のみ抽出
 *   - HEAD で確認、HEAD が拒否される場合は GET にフォールバック
 *   - 並列8本で HTTP リクエスト（curl_multi）
 *   - 200〜399 を OK、それ以外（4xx/5xx/timeout/DNS失敗）を NG
 *   - JSON のキーは HTML 上の生の href 文字列（JS と突き合わせるため）
 *
 * NOTE: 装飾セル（.municipal-name / .amami-org-name / .island-title-row）も
 *       URL は抽出してチェックする。実際の着色対象から除外する制御は JS 側で行う。
 */

declare(strict_types=1);

// ---- 環境設定 ----------------------------------------------------------
@set_time_limit(0);
@ini_set('memory_limit', '256M');
mb_internal_encoding('UTF-8');

$IS_CRON = isset($_GET['cron']) && $_GET['cron'] === '1';

// 進捗を即時flushするための準備
if (!$IS_CRON) {
    while (ob_get_level() > 0) { @ob_end_flush(); }
    @ob_implicit_flush(true);
}

// ---- 定数 -------------------------------------------------------------
const HTML_FILE   = 'index.html';
const OUTPUT_FILE = 'link-status.json';
const USER_AGENT  =
    'Mozilla/5.0 (compatible; LinkStatusBot/1.0; '
  . '+https://violetfoal2.sakura.ne.jp/hp-amami-pr-1/) Chrome/124.0';
const HTTP_TIMEOUT     = 12;   // 各リクエストのタイムアウト秒
const PARALLEL         = 8;    // 並列数
const RETRY_ON_TIMEOUT = 1;    // タイムアウト時の追加リトライ回数

// ---- 出力ユーティリティ -------------------------------------------------
function out(string $msg, bool $cron): void {
    if ($cron) {
        // cron 実行時はコンソール向けのプレーン出力（HTMLタグなし）
        echo strip_tags($msg) . "\n";
    } else {
        echo $msg . "\n";
        @flush();
    }
}

function html_header(bool $cron): void {
    if ($cron) {
        header('Content-Type: text/plain; charset=UTF-8');
        return;
    }
    header('Content-Type: text/html; charset=UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>テーブル内リンクチェック</title>
<style>
  body { font-family: -apple-system, "Segoe UI", sans-serif; padding: 16px; line-height: 1.55; color: #123760; }
  h1 { font-size: 1.2em; }
  pre { background: #f4f7fb; border: 1px solid #cfd8e3; border-radius: 6px;
        padding: 8px 12px; font-size: 12px; white-space: pre-wrap; word-break: break-all; }
  .ok { color: #1b6f3e; }
  .ng { color: #b6303a; font-weight: 700; }
  .summary { margin-top: 14px; padding: 10px 14px; border-radius: 8px;
             background: #fff9e6; border: 1px solid #e6c84a; }
  table.broken { border-collapse: collapse; margin-top: 10px; }
  table.broken th, table.broken td { border: 1px solid #cfd8e3; padding: 4px 8px; font-size: 13px; vertical-align: top; }
  table.broken th { background: #f0f5fa; }
</style>
</head>
<body>
<h1>テーブル内リンク 到達性チェック</h1>
<p>対象: index.html 内の全 <code>&lt;table&gt;</code> 配下の URL</p>
<pre>
HTML;
}

function html_footer(string $jsonFile, int $checked, array $broken, bool $cron): void {
    if ($cron) {
        echo "\nchecked={$checked}, broken=" . count($broken) . ", out={$jsonFile}\n";
        return;
    }
    echo "</pre>\n";
    $brokenCount = count($broken);
    $cls = $brokenCount === 0 ? 'ok' : 'ng';
    echo "<div class=\"summary\">";
    echo "<strong>結果サマリー：</strong>"
       . "チェック {$checked} 件 ／ <span class=\"{$cls}\">リンク切れ {$brokenCount} 件</span>"
       . "／ 出力: <code>" . htmlspecialchars($jsonFile, ENT_QUOTES, 'UTF-8') . "</code>";
    echo "</div>\n";

    if ($brokenCount > 0) {
        echo "<h2 style=\"font-size:1.05em;margin-top:18px;\">リンク切れ一覧</h2>";
        echo "<table class=\"broken\"><thead><tr>"
           . "<th>#</th><th>status</th><th>セルID</th><th>テキスト</th><th>URL</th><th>原因</th>"
           . "</tr></thead><tbody>";
        $i = 0;
        foreach ($broken as $href => $info) {
            $i++;
            $status = (int)($info['status'] ?? 0);
            $cell   = $info['cell']   ?? '';
            $label  = $info['label']  ?? '';
            $reason = $info['reason'] ?? '';
            echo '<tr>'
               . "<td>{$i}</td>"
               . '<td>' . htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') . '</td>'
               . '<td>' . htmlspecialchars($cell,   ENT_QUOTES, 'UTF-8') . '</td>'
               . '<td>' . htmlspecialchars($label,  ENT_QUOTES, 'UTF-8') . '</td>'
               . '<td><a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">'
                 . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '</a></td>'
               . '<td>' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</td>'
               . '</tr>';
        }
        echo "</tbody></table>";
    }
    echo "<p style=\"margin-top:14px;\"><a href=\"index.html\">→ index.html を表示</a></p>";
    echo "</body></html>";
}

// ---- HTML から URL を抽出 ----------------------------------------------
/**
 * @return array<string, array{url:string,label:string,cell:?string}>
 *         キー: 生の href（HTML上の表記）／値: 表示用メタ情報
 */
function extract_table_urls(string $htmlPath): array {
    $html = @file_get_contents($htmlPath);
    if ($html === false) {
        throw new RuntimeException("HTML を読み込めません: {$htmlPath}");
    }

    // DOMDocument で HTML5 を解析（warnings を抑制）
    $dom = new DOMDocument();
    $loadOk = @$dom->loadHTML(
        '<?xml encoding="UTF-8">' . $html,
        LIBXML_NOERROR | LIBXML_NOWARNING
    );
    if (!$loadOk) {
        throw new RuntimeException('HTML 解析に失敗しました');
    }

    $xpath = new DOMXPath($dom);
    // 全 <table> の中の <a href>
    $nodes = $xpath->query('//table//a[@href]');
    $map = [];
    foreach ($nodes as $a) {
        /** @var DOMElement $a */
        $hrefRaw = $a->getAttribute('href');
        $url = trim($hrefRaw);
        if ($url === '' || $url[0] === '#') continue;
        $lower = strtolower($url);
        foreach (['javascript:', 'mailto:', 'tel:', 'data:'] as $bad) {
            if (str_starts_with($lower, $bad)) continue 2;
        }
        // スキーム必須（外部URLのみ対象）
        if (!preg_match('#^https?://#i', $url)) continue;

        // セルIDの取得（最近接の祖先<td>の id 属性）
        $cellId = null;
        for ($p = $a->parentNode; $p && $p->nodeType === XML_ELEMENT_NODE; $p = $p->parentNode) {
            if ($p->nodeName === 'td') {
                $cellId = $p->getAttribute('id') ?: null;
                break;
            }
        }

        $label = trim(preg_replace('/\s+/u', ' ', $a->textContent ?? ''));
        if (mb_strlen($label) > 60) {
            $label = mb_substr($label, 0, 60) . '…';
        }

        if (!isset($map[$hrefRaw])) {
            $map[$hrefRaw] = [
                'url'   => $url,
                'label' => $label,
                'cell'  => $cellId,
            ];
        }
    }
    return $map;
}

// ---- 1つの URL をチェック（curl ハンドル作成） ---------------------------
function build_curl(string $url, bool $useHead): \CurlHandle {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_NOBODY         => $useHead,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 8,
        CURLOPT_TIMEOUT        => HTTP_TIMEOUT,
        CURLOPT_CONNECTTIMEOUT => HTTP_TIMEOUT,
        CURLOPT_USERAGENT      => USER_AGENT,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: ja,en;q=0.8',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        // GET 時はボディを全部受信せず途中で打ち切る
        CURLOPT_RANGE          => $useHead ? null : '0-2047',
    ]);
    return $ch;
}

/**
 * curl_multi で URL をまとめてチェック
 * @param string[] $urls
 * @return array<string, array{status:int,reason:?string,ok:bool}>
 */
function http_check_parallel(array $urls, bool $cron, callable $progress): array {
    $results = [];

    // 第1段：HEAD で確認
    $first = run_multi($urls, true);
    // HEAD で 4xx/5xx/0 が返ったものは GET で再試行
    $retryGet = [];
    foreach ($first as $url => $r) {
        $s = $r['status'];
        $needsRetry =
            $r['errno'] !== 0
            || $s === 0
            || $s === 400 || $s === 401 || $s === 403 || $s === 404
            || $s === 405 || $s === 406 || $s === 409 || $s === 410
            || $s === 429 || $s === 500 || $s === 501 || $s === 503;
        if ($needsRetry) {
            $retryGet[] = $url;
        }
    }
    $second = $retryGet ? run_multi($retryGet, false) : [];

    // タイムアウトで失敗したものはもう1度（RETRY_ON_TIMEOUT 回）
    $third = [];
    if (RETRY_ON_TIMEOUT > 0) {
        $retryTimeout = [];
        foreach ($urls as $u) {
            $r = $second[$u] ?? $first[$u] ?? null;
            if ($r && $r['errno'] !== 0 && stripos($r['error'] ?? '', 'time') !== false) {
                $retryTimeout[] = $u;
            }
        }
        if ($retryTimeout) {
            $third = run_multi($retryTimeout, false);
        }
    }

    // 結果のマージ
    $i = 0;
    foreach ($urls as $url) {
        $i++;
        $r = $third[$url] ?? $second[$url] ?? $first[$url] ?? null;
        if (!$r) {
            $results[$url] = ['status' => 0, 'reason' => 'no response', 'ok' => false];
        } else {
            $status = $r['status'];
            $reason = null;
            if ($r['errno'] !== 0) {
                $reason = $r['error'] ?: ('curl errno=' . $r['errno']);
            } elseif ($status >= 400) {
                $reason = 'HTTP ' . $status;
            }
            $ok = ($r['errno'] === 0 && $status >= 200 && $status < 400);
            $results[$url] = ['status' => $status, 'reason' => $reason, 'ok' => $ok];
        }
        $progress($i, count($urls), $url, $results[$url]);
    }
    return $results;
}

/**
 * @param string[] $urls
 * @return array<string, array{status:int,errno:int,error:string}>
 */
function run_multi(array $urls, bool $useHead): array {
    $results = [];
    $mh = curl_multi_init();
    $handles = []; // url => ch

    // 並列上限ずつ投入
    $chunks = array_chunk($urls, PARALLEL);
    foreach ($chunks as $chunk) {
        foreach ($chunk as $u) {
            $ch = build_curl($u, $useHead);
            curl_multi_add_handle($mh, $ch);
            $handles[$u] = $ch;
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 0.5);
            }
        } while ($running && $status === CURLM_OK);

        foreach ($chunk as $u) {
            $ch = $handles[$u];
            $code  = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $errno = (int) curl_errno($ch);
            $err   = (string) curl_error($ch);
            $results[$u] = ['status' => $code, 'errno' => $errno, 'error' => $err];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($handles[$u]);
        }
    }
    curl_multi_close($mh);
    return $results;
}

// ---- メイン ------------------------------------------------------------
$baseDir = __DIR__;
$htmlPath = $baseDir . DIRECTORY_SEPARATOR . HTML_FILE;
$jsonPath = $baseDir . DIRECTORY_SEPARATOR . OUTPUT_FILE;

html_header($IS_CRON);

try {
    out("[1/3] HTML 解析: " . HTML_FILE, $IS_CRON);
    $hrefToMeta = extract_table_urls($htmlPath);
    $total = count($hrefToMeta);
    out("      対象 URL 件数: {$total}", $IS_CRON);

    if ($total === 0) {
        $payload = [
            'generatedAt' => date('c'),
            'source'      => HTML_FILE,
            'checked'     => 0,
            'brokenCount' => 0,
            'broken'      => (object)[],
        ];
        @file_put_contents($jsonPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        html_footer(OUTPUT_FILE, 0, [], $IS_CRON);
        exit;
    }

    // 重複URLは1回しか叩かないようにユニーク化
    $urlsUnique = [];
    foreach ($hrefToMeta as $href => $meta) {
        $urlsUnique[$meta['url']] = true;
    }
    $urlsUnique = array_keys($urlsUnique);

    out('[2/3] HTTP チェック開始 (parallel=' . PARALLEL . ', timeout=' . HTTP_TIMEOUT . 's)', $IS_CRON);
    $started = microtime(true);

    $resultsByUrl = http_check_parallel(
        $urlsUnique,
        $IS_CRON,
        function(int $i, int $n, string $url, array $res) use ($IS_CRON) {
            $status = (int)$res['status'];
            $ok = !empty($res['ok']);
            $mark = $ok ? '<span class="ok">OK</span>' : '<span class="ng">NG</span>';
            $line = sprintf(
                "  [%3d/%d] %s status=%-3d %s",
                $i, $n, $mark, $status, htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
            out($line, $IS_CRON);
        }
    );

    $elapsed = microtime(true) - $started;
    out(sprintf('      所要時間: %.1fs', $elapsed), $IS_CRON);

    // 結果を href ベースで再編成
    $broken = [];
    foreach ($hrefToMeta as $href => $meta) {
        $r = $resultsByUrl[$meta['url']] ?? null;
        if (!$r) continue;
        if (!$r['ok']) {
            $broken[$href] = [
                'status' => (int) $r['status'],
                'reason' => $r['reason'] ?: ('HTTP ' . (int)$r['status']),
                'label'  => $meta['label'] ?? '',
                'cell'   => $meta['cell']  ?? null,
            ];
        }
    }

    out('[3/3] 結果書き出し: ' . OUTPUT_FILE, $IS_CRON);
    $payload = [
        'generatedAt' => date('c'),
        'source'      => HTML_FILE,
        'checked'     => $total,
        'brokenCount' => count($broken),
        'broken'      => $broken ?: (object)[],
    ];
    $ok = @file_put_contents(
        $jsonPath,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
    if ($ok === false) {
        out('  ! 書き込み失敗: ' . OUTPUT_FILE . ' （パーミッションを確認してください）', $IS_CRON);
    } else {
        out('  完了。', $IS_CRON);
    }

    html_footer(OUTPUT_FILE, $total, $broken, $IS_CRON);
} catch (Throwable $e) {
    out('ERROR: ' . $e->getMessage(), $IS_CRON);
    if (!$IS_CRON) {
        echo "</pre><p style=\"color:#b6303a;\">処理が中断されました。</p></body></html>";
    }
}
