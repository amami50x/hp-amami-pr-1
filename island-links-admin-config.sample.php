<?php
/**
 * 任意：このファイルを island-links-admin-config.php にコピーし、必要なら allowed_admin_ips を設定してください。
 * island-links-admin-config.php は公開リポジトリに含めないことを推奨します。
 *
 * admin_password は使用しません（パスワード認証は island-links-manage.php では行いません）。
 */
return [
    /* 任意：許可する管理操作元の IPv4/IPv6（1件ずつ）。空配列なら制限なし。
       例：自宅・事務所の固定 IP のみに限定すると、一般利用者のブラウザからは開けません。 */
    'allowed_admin_ips' => [
        // '203.0.113.45',
    ],
];
