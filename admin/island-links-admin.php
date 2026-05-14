<?php
/**
 * 5島「関連サイト」リンクの管理画面（admin パス用）
 *
 * 認証（合言葉・パスワード）は行わない。
 * 任意：admin/island-links-config.php の allowed_admin_ips で接続元IPを制限できる。
 */
session_start();

$configFile = __DIR__ . '/island-links-config.php';
$config = file_exists($configFile) ? require $configFile : [];

require_once dirname(__DIR__) . '/island-links-admin-guard.php';
island_links_admin_guard($config);

require_once dirname(__DIR__) . '/island-related-links-storage.php';

$keys = island_related_links_island_keys();
$labels = island_related_links_island_labels_ja();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['island_links_csrf'] ?? '', (string) $_POST['csrf'])) {
        $error = 'セッションが無効です。ページを再読み込みしてから保存してください。';
    } else {
        $post = $_POST;
        $post['titles'] = [];
        foreach ($keys as $k) {
            $preset = isset($post['titles_preset'][$k]) ? trim((string) $post['titles_preset'][$k]) : '';
            if ($preset === '__custom__') {
                $post['titles'][$k] = isset($post['titles_custom'][$k]) ? trim((string) $post['titles_custom'][$k]) : '';
            } elseif ($preset !== '') {
                $post['titles'][$k] = $preset;
            } else {
                $post['titles'][$k] = '';
            }
        }
        unset($post['titles_preset'], $post['titles_custom']);
        $built = island_related_links_build_from_post($post);
        if (isset($built['error'])) {
            $error = $built['error'];
        } else {
            if (island_related_links_save($built['data'])) {
                $success = '保存しました。';
            } else {
                $error = 'ファイルの保存に失敗しました。data ディレクトリの書き込み権限を確認してください。';
            }
        }
    }
}

$data = island_related_links_load();
$csrf = bin2hex(random_bytes(16));
$_SESSION['island_links_csrf'] = $csrf;

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>関連サイト管理（5島）</title>
  <style>
    body { font-family: system-ui, sans-serif; margin: 0; padding: 16px; background: #e8f4fc; color: #123760; }
    h1 { font-size: 1.25rem; margin: 0 0 12px; }
    .box { max-width: 960px; margin: 0 auto; background: #fff; border: 1px solid #86a9d9; border-radius: 10px; padding: 16px 18px; }
    .admin-note { font-size: 0.88rem; color: #345; margin: 0 0 14px; max-width: 720px; line-height: 1.5; }
    .err { color: #b00020; margin: 8px 0; }
    .ok { color: #0d6b2e; margin: 8px 0; }
    label { display: block; font-weight: 700; margin: 12px 0 6px; }
    input[type="password"], input[type="text"] { width: 100%; max-width: 520px; padding: 8px; box-sizing: border-box; }
    select.title-preset-select { width: 100%; max-width: 520px; padding: 8px; box-sizing: border-box; font: inherit; }
    .title-custom-wrap { margin-top: 8px; max-width: 520px; }
    .island-block { border: 1px solid #cce0f5; border-radius: 8px; padding: 12px; margin-bottom: 16px; background: #fafdff; }
    .island-block h2 { margin: 0 0 10px; font-size: 1.05rem; color: #0a3f86; }
    table.links-edit { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    table.links-edit th, table.links-edit td { border: 1px solid #bcd; padding: 6px; vertical-align: top; }
    table.links-edit th { background: #e3f0fb; text-align: left; }
    table.links-edit input { width: 100%; box-sizing: border-box; padding: 6px; }
    .btn { display: inline-block; padding: 8px 16px; margin: 4px 8px 4px 0; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; text-decoration: none; }
    .btn-primary { background: #0b4fb5; color: #fff; }
    .btn-secondary { background: #6c757d; color: #fff; }
    .btn-danger { background: #c62828; color: #fff; font-size: 0.85rem; padding: 4px 10px; }
    .btn-add { background: #2e7d32; color: #fff; font-size: 0.85rem; margin-top: 8px; }
    .toolbar { margin-top: 20px; }
  </style>
</head>
<body>
  <div class="box">
    <h1>奄美PRページ — 5島「関連サイト」管理</h1>
    <p class="admin-note">この画面に<strong>合言葉・パスワードによるロックはありません</strong>。URLは運用MENUなどで共有し、ブロックが必要なときは <code>admin/island-links-config.php</code> の <code>allowed_admin_ips</code> またはサーバの Basic 認証を利用してください。</p>

    <?php if ($error): ?><p class="err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
    <?php if ($success): ?><p class="ok"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>

    <p>各島の「関連サイト」ポップアップに表示する <strong>表示名</strong> と <strong>URL</strong> を編集できます。空行は保存時に無視されます。ポップアップの見出しは定型から選ぶか、「その他（手入力）」で自由に入力できます。</p>

    <form method="post" action="">
      <input type="hidden" name="save" value="1">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">

      <?php foreach ($keys as $key): ?>
      <div class="island-block" data-island="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
        <h2><?php echo htmlspecialchars($labels[$key] ?? $key, ENT_QUOTES, 'UTF-8'); ?>（<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>）</h2>
        <label>ポップアップの見出し</label>
        <?php
        $presets = island_related_links_title_presets($key);
        $currentTitle = (string) ($data['titles'][$key] ?? '');
        $isTitleCustom = true;
        foreach ($presets as $p) {
            if ($currentTitle === $p) {
                $isTitleCustom = false;
                break;
            }
        }
        ?>
        <select name="titles_preset[<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>]" class="title-preset-select" data-key="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
          <?php foreach ($presets as $p): ?>
          <option value="<?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (!$isTitleCustom && $currentTitle === $p) ? ' selected' : ''; ?>><?php echo htmlspecialchars($p, ENT_QUOTES, 'UTF-8'); ?></option>
          <?php endforeach; ?>
          <option value="__custom__"<?php echo $isTitleCustom ? ' selected' : ''; ?>>その他（手入力）</option>
        </select>
        <div class="title-custom-wrap" id="title-custom-wrap-<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" style="<?php echo $isTitleCustom ? '' : 'display:none;'; ?>">
          <label for="title-custom-<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">手入力の見出し</label>
          <input type="text" id="title-custom-<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" name="titles_custom[<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>]" maxlength="120" value="<?php echo htmlspecialchars($isTitleCustom ? $currentTitle : '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <label>リンク一覧</label>
        <table class="links-edit">
          <thead><tr><th style="width:28%">表示名</th><th>URL</th><th style="width:72px">削除</th></tr></thead>
          <tbody id="rows-<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
            <?php
            $rows = $data['links'][$key] ?? [];
            if (!count($rows)) {
                $rows = [['label' => '', 'url' => '']];
            }
            foreach ($rows as $i => $row):
            ?>
            <tr>
              <td><input type="text" name="links[<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>][<?php echo (int) $i; ?>][label]" value="<?php echo htmlspecialchars($row['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></td>
              <td><input type="text" name="links[<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>][<?php echo (int) $i; ?>][url]" value="<?php echo htmlspecialchars($row['url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></td>
              <td><button type="button" class="btn btn-danger row-remove">削除</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <button type="button" class="btn btn-add row-add" data-key="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">行を追加</button>
      </div>
      <?php endforeach; ?>

      <div class="toolbar">
        <button type="submit" class="btn btn-primary">すべて保存</button>
        <a class="btn btn-secondary" href="../island-related-links-api.php" target="_blank" rel="noopener">JSONを確認</a>
        <a class="btn btn-secondary" href="../amami.html">amami.html を開く</a>
      </div>
    </form>

    <script>
    (function () {
      document.querySelectorAll('.title-preset-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
          var k = sel.getAttribute('data-key');
          var wrap = document.getElementById('title-custom-wrap-' + k);
          if (!wrap) return;
          wrap.style.display = sel.value === '__custom__' ? 'block' : 'none';
        });
      });
      document.querySelectorAll('.row-add').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var key = btn.getAttribute('data-key');
          var tbody = document.getElementById('rows-' + key);
          var n = tbody.querySelectorAll('tr').length;
          var tr = document.createElement('tr');
          tr.innerHTML = '<td><input type="text" name="links[' + key + '][' + n + '][label]" value=""></td>' +
            '<td><input type="text" name="links[' + key + '][' + n + '][url]" value=""></td>' +
            '<td><button type="button" class="btn btn-danger row-remove">削除</button></td>';
          tbody.appendChild(tr);
          tr.querySelector('.row-remove').addEventListener('click', removeRow);
        });
      });
      function removeRow(ev) {
        var tr = ev.target.closest('tr');
        if (tr && tr.parentNode.rows.length > 1) tr.remove();
        else if (tr) {
          tr.querySelectorAll('input').forEach(function (inp) { inp.value = ''; });
        }
      }
      document.querySelectorAll('.row-remove').forEach(function (b) {
        b.addEventListener('click', removeRow);
      });
    })();
    </script>
  </div>
</body>
</html>
