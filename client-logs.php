<?php
// Endpoint pour logger les événements côté client
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$logFile = __DIR__ . '/logs/client.log';

// Créer le dossier logs s'il n'existe pas
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Recevoir et enregistrer un log
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Message requis']);
        exit;
    }

    $timestamp = date('Y-m-d H:i:s');
    $level = $input['level'] ?? 'INFO';
    $message = $input['message'];
    $data = isset($input['data']) ? json_encode($input['data']) : '';

    $logLine = "[{$timestamp}] [{$level}] {$message}";
    if ($data) {
        $logLine .= " | Data: {$data}";
    }
    $logLine .= "\n";

    file_put_contents($logFile, $logLine, FILE_APPEND);

    echo json_encode(['success' => true]);

} elseif ($method === 'GET') {
    // Lire les logs (dernières 100 lignes)
    if (!file_exists($logFile)) {
        echo json_encode(['success' => true, 'logs' => []]);
        exit;
    }

    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lastLines = array_slice($lines, -100); // 100 dernières lignes

    echo json_encode([
        'success' => true,
        'logs' => $lastLines,
        'count' => count($lastLines),
        'total' => count($lines)
    ]);

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}
?>
