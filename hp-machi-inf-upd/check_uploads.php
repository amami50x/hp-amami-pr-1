<?php
$uploadDir = 'uploads/';
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    echo "<h1>uploads フォルダの中身</h1><ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<li>" . htmlspecialchars($file) . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "uploads フォルダが見つかりません。";
}
?>
