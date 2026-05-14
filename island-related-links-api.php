<?php
/**
 * 5島「関連サイト」データの公開API（amami.html から fetch）
 */
require_once __DIR__ . '/island-related-links-storage.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

echo json_encode(island_related_links_load(), JSON_UNESCAPED_UNICODE);
