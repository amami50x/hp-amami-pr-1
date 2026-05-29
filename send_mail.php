<?php
// send_mail.php
// 入力値の取得
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// バリデーション
if ($name === '' || $email === '' || $message === '') {
    echo '全ての項目を入力してください。';
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo 'メールアドレスの形式が正しくありません。';
    exit;
}

// メール送信先
$to = 'tokyo.amamikai5550@gmail.com';
$subject = 'ホームページからのご意見・ご要望';
$body = "お名前: $name\nメール: $email\n\n内容:\n$message";
$headers = "From: $email\r\nReply-To: $email\r\n";

if (mb_send_mail($to, $subject, $body, $headers)) {
    echo '送信が完了しました。ご意見ありがとうございました。';
} else {
    echo '送信に失敗しました。時間をおいて再度お試しください。';
}
?>
