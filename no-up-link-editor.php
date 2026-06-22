<?php
/* ============================================================
   リンク一覧.txt をブラウザから編集するページ（さくらサーバー用）
   - アクセス: https://（あなたのドメイン）/link-editor.php
   - パスワードでログインしてから編集・保存できます。
   - 保存するたびに tbl-url-bk/ に自動バックアップを残します。
   ============================================================ */

session_start();
mb_internal_encoding('UTF-8');

/* ===== 設定（ここだけ変更してください）===== */
$PASSWORD = 'amami50x';   // ★ ログイン用パスワード（必ず変更してください）
$TARGET   = 'リンク一覧.txt';       // 編集するファイル名（変更不要）
/* =========================================== */

$dir  = __DIR__;
$path = $dir . '/' . $TARGET;

if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(16));
}
$token = $_SESSION['token'];

$message = '';
$error   = '';

/* ---- ログアウト ---- */
if (isset($_GET['logout'])) {
    $_SESSION = array();
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

/* ---- ログイン処理 ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && !isset($_POST['content'])) {
    if (hash_equals($PASSWORD, (string)$_POST['password'])) {
        $_SESSION['auth'] = true;
    } else {
        $error = 'パスワードが違います。';
    }
}

$authed = !empty($_SESSION['auth']);

/* ---- 保存処理 ---- */
if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    if (!isset($_POST['token']) || !hash_equals($token, (string)$_POST['token'])) {
        $error = '送信内容が確認できませんでした。お手数ですが、もう一度保存してください。';
    } else {
        $content = (string)$_POST['content'];
        $content = str_replace(array("\r\n", "\r"), "\n", $content); // 改行を統一
        // 保存前に自動バックアップ
        if (file_exists($path)) {
            $bakdir = $dir . '/tbl-url-bk';
            if (!is_dir($bakdir)) { @mkdir($bakdir, 0755); }
            @copy($path, $bakdir . '/リンク一覧_' . date('Ymd_His') . '.txt');
        }
        $ok = @file_put_contents($path, $content, LOCK_EX);
        if ($ok === false) {
            $error = '保存できませんでした。ファイルの書き込み権限（パーミッション）を確認してください。';
        } else {
            $message = '保存しました。（' . date('Y-m-d H:i:s') . '）ホームページにすぐ反映されます。';
        }
    }
}

/* ---- 現在の内容を読み込み ---- */
$current = '';
if (file_exists($path)) {
    $current = (string)file_get_contents($path);
} elseif ($authed) {
    $error = $TARGET . ' が見つかりません。同じフォルダに置いてください。';
}
$lastModified = file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : '';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>リンク一覧 編集ページ</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: "Hiragino Kaku Gothic ProN", "Meiryo", system-ui, sans-serif;
    margin: 0; background: #f3f5f8; color: #1f2937; line-height: 1.6;
  }
  header {
    background: #002277; color: #fff; padding: 14px 20px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;
  }
  header h1 { font-size: 18px; margin: 0; }
  header .logout { color: #cfe0ff; font-size: 14px; text-decoration: none; }
  header .logout:hover { color: #fff; text-decoration: underline; }
  main { max-width: 960px; margin: 0 auto; padding: 20px; }
  .msg { padding: 12px 16px; border-radius: 8px; margin: 0 0 16px; font-size: 15px; }
  .msg.ok  { background: #e7f6ec; border: 1px solid #57b97e; color: #1f7a44; }
  .msg.err { background: #fdeaea; border: 1px solid #e08585; color: #a02828; }
  .login-card, .editor-card {
    background: #fff; border: 1px solid #dde2ea; border-radius: 12px;
    padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.05);
  }
  .login-card { max-width: 420px; margin: 60px auto; }
  label { display: block; font-weight: 600; margin-bottom: 6px; }
  input[type=password] {
    width: 100%; padding: 12px; font-size: 16px; border: 1px solid #c3cad6;
    border-radius: 8px;
  }
  textarea {
    width: 100%; height: 60vh; min-height: 360px; padding: 14px;
    font-family: "SFMono-Regular", "Consolas", "Menlo", "MS Gothic", monospace;
    font-size: 14px; line-height: 1.7; border: 1px solid #c3cad6; border-radius: 8px;
    resize: vertical; white-space: pre; overflow: auto;
  }
  .btn {
    display: inline-block; background: #002277; color: #fff; border: none;
    padding: 12px 28px; font-size: 16px; border-radius: 8px; cursor: pointer;
  }
  .btn:hover { background: #0033aa; }
  .toolbar { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-top: 14px; }
  .meta { color: #6b7280; font-size: 13px; }
  .help {
    background: #fafbfc; border: 1px dashed #c3cad6; border-radius: 8px;
    padding: 12px 16px; margin-bottom: 16px; font-size: 14px;
  }
  .help summary { cursor: pointer; font-weight: 600; }
  .help code { background: #eef1f5; padding: 1px 5px; border-radius: 4px; }
  .help pre { background: #1f2937; color: #e5e7eb; padding: 12px; border-radius: 8px; overflow:auto; }
</style>
</head>
<body>
<header>
  <h1>リンク一覧 編集ページ</h1>
  <?php if ($authed): ?>
    <a class="logout" href="?logout=1">ログアウト</a>
  <?php endif; ?>
</header>

<main>
<?php if ($message): ?><div class="msg ok"><?php echo h($message); ?></div><?php endif; ?>
<?php if ($error):   ?><div class="msg err"><?php echo h($error);   ?></div><?php endif; ?>

<?php if (!$authed): ?>
  <form class="login-card" method="post">
    <label for="pw">パスワードを入力してください</label>
    <input type="password" id="pw" name="password" autofocus required>
    <div class="toolbar">
      <button class="btn" type="submit">ログイン</button>
    </div>
  </form>
<?php else: ?>
  <details class="help">
    <summary>かんたんな書き方（クリックで開く）</summary>
    <p>「どこに出すか」を <code>[ ]</code> で書き、その下に <code>名前 = URL</code> を1行ずつ書くだけです。記号 <code>{ } " ,</code> は不要です。</p>
<pre>[cell-13-03]
ねりやかなや = https://www.neriyakanaya.jp/
奄美群島広域事務組合（公式） = https://www.amami.or.jp/

[島:喜界島]
概要 = https://kikaijimanavi.com/tourism/

[SNS]
X（旧Twitter）：東京奄美会 = https://x.com/PiiZmi</pre>
    <p>・<code>#</code> で始まる行と空行は無視されます（メモに使えます）。<br>
       ・上に書いた順に表示されます。<br>
       ・くわしくは <code>追加リンクの編集ガイド.txt</code> を参照してください。</p>
  </details>

  <form class="editor-card" method="post" onsubmit="return confirm('この内容で保存します。よろしいですか？');">
    <input type="hidden" name="token" value="<?php echo h($token); ?>">
    <textarea name="content" spellcheck="false"><?php echo h($current); ?></textarea>
    <div class="toolbar">
      <button class="btn" type="submit">保存する</button>
      <span class="meta">対象ファイル: <?php echo h($TARGET); ?><?php if ($lastModified): ?> ／ 最終更新: <?php echo h($lastModified); ?><?php endif; ?></span>
    </div>
  </form>
<?php endif; ?>
</main>
</body>
</html>
