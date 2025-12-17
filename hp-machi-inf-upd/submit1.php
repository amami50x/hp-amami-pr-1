
<?php
// エラー表示（開発中のみ）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// POSTメソッド以外は拒否（405エラー）
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'このAPIはPOSTメソッドのみ対応しています']);
    exit;
}

// レスポンス形式
header('Content-Type: application/json; charset=utf-8');

// 入力取得とサニタイズ関数
function get_post_data($key) {
    return isset($_POST[$key]) ? trim(htmlspecialchars($_POST[$key], ENT_QUOTES, 'UTF-8')) : '';
}

// 入力項目の取得
$key_code        = get_post_data('key_code');
$sub_category    = get_post_data('sub_category');
$name            = get_post_data('name');
$description     = get_post_data('description');
$url             = get_post_data('url');
$postal_code     = get_post_data('postal_code');
$address         = get_post_data('address');
$phone           = get_post_data('phone');
$contact_person  = get_post_data('contact_person');

// 画像アップロード関数
function handle_image_upload() {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        return ['error' => '画像が正しくアップロードされていません。'];
    }

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename   = time() . "_" . basename($_FILES['image']['name']);
    $targetPath = $uploadDir . $filename;

    // 拡張子チェック
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_ext)) {
        http_response_code(400);
        return ['error' => '許可されていないファイル形式です（jpg/jpeg/png/gif）。'];
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        return ['image_path' => $targetPath];
    } else {
        http_response_code(500);
        return ['error' => '画像のアップロードに失敗しました。'];
    }
}

// アップロード処理実行
$image_result = handle_image_upload();
if (isset($image_result['error'])) {
    echo json_encode($image_result);
    exit;
}
$image_path = $image_result['image_path'];

// 入力チェック
$missing_fields = [];
foreach ([
    'key_code' => $key_code,
    'sub_category' => $sub_category,
    'name' => $name,
    'description' => $description,
    'url' => $url,
    'postal_code' => $postal_code,
    'address' => $address,
    'phone' => $phone,
    'contact_person' => $contact_person
] as $label => $value) {
    if (empty($value)) {
        $missing_fields[] = $label;
    }
}

if (!empty($missing_fields)) {
    http_response_code(400);
    echo json_encode([
        'error' => '必須項目が不足しています。',
        'missing' => $missing_fields
    ]);
    exit;
}

// 成功レスポンス（本番ではDB登録処理に変更可能）
echo json_encode([
    'message' => 'データを受け取りました。（データベース処理は未実装）',
    'received' => [
        'key_code'       => $key_code,
        'sub_category'   => $sub_category,
        'name'           => $name,
        'description'    => $description,
        'url'            => $url,
        'postal_code'    => $postal_code,
        'address'        => $address,
        'phone'          => $phone,
        'contact_person' => $contact_person,
        'image_path'     => $image_path
    ]
]);

?>
