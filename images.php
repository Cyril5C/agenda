<?php
// Charger la configuration
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));

$response = ['success' => false, 'images' => []];

// Si on utilise Cloudinary, retourner les images depuis Cloudinary
if (config('use_cloudinary')) {
    $cloudinary = config('cloudinary');

    if ($cloudinary['cloud_name'] && $cloudinary['api_key'] && $cloudinary['api_secret']) {
        // Pour Cloudinary, on ne liste pas les images ici
        // L'application utilisera directement les URLs stockées dans evenements.json
        $response['success'] = true;
        $response['cloudinary'] = true;
        $response['message'] = 'Mode Cloudinary activé - URLs stockées dans les événements';
    } else {
        $response['error'] = 'Cloudinary activé mais non configuré';
    }
}
// Sinon, lister les images locales
else {
    $imagesDir = config('upload_dir');
    $images = [];

    if (is_dir($imagesDir)) {
        $files = scandir($imagesDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
                $images[] = $file;
            }
        }
    }

    $response['success'] = true;
    $response['cloudinary'] = false;
    $response['images'] = $images;
}

echo json_encode($response);
?>
