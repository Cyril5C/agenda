<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$imagesDir = 'images/';
$images = [];

if (is_dir($imagesDir)) {
    $files = scandir($imagesDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
            $images[] = $file;
        }
    }
}

echo json_encode($images);
?>
