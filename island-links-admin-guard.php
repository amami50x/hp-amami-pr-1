<?php
/**
 * 関連サイト管理画面のアクセス制御（設定による IP 許可リスト）
 * island-links-admin-config.php の allowed_admin_ips が空でないときのみ検査する。
 */

function island_links_admin_guard(array $config): void
{
    $allowed = $config['allowed_admin_ips'] ?? null;
    if (!is_array($allowed)) {
        return;
    }
    $allowed = array_values(array_filter(array_map('trim', $allowed), static function ($s) {
        return $s !== '';
    }));
    if ($allowed === []) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    foreach ($allowed as $rule) {
        if ($rule !== '' && hash_equals((string) $rule, $ip)) {
            return;
        }
    }

    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>アクセス不可</title></head><body>';
    echo '<p>この管理画面は、許可された接続元からのみ利用できます。</p>';
    echo '<p><code>allowed_admin_ips</code> に現在の接続元（' . htmlspecialchars($ip, ENT_QUOTES, 'UTF-8') . '）を追加するか、FTP で Basic 認証（<code>island-links-manage.htaccess.example</code> 参照）を併用してください。</p>';
    echo '</body></html>';
    exit;
}
