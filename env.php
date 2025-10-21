<?php
// Charger la configuration
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));

echo json_encode([
    'env' => APP_ENV,
    'debug' => APP_DEBUG
]);
?>
