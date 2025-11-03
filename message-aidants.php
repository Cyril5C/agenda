<?php
// Charger la configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

// Toutes les requêtes nécessitent authentification (visible uniquement dans admin)
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Les requêtes POST et PUT nécessitent aussi le token CSRF
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    requireCsrfToken();
}

// Déterminer le fichier de stockage selon l'environnement
$messageFile = APP_ENV === 'dev'
    ? __DIR__ . '/message-aidants.json'
    : __DIR__ . '/message-aidants.json'; // En prod, on utilise aussi le fichier local (pas Gist car privé)

// Lire le message
function getMessage() {
    global $messageFile;
    if (!file_exists($messageFile)) {
        return ['texte' => ''];
    }
    $content = file_get_contents($messageFile);
    return json_decode($content, true) ?: ['texte' => ''];
}

// Écrire le message
function saveMessage($data) {
    global $messageFile;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($messageFile, $json) !== false;
}

// Gérer les requêtes
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Récupérer le message
    $message = getMessage();
    echo json_encode([
        'success' => true,
        'texte' => $message['texte']
    ]);

} elseif ($method === 'POST' || $method === 'PUT') {
    // Modifier le message
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['texte'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Texte requis']);
        exit;
    }

    $data = ['texte' => $input['texte']];

    if (saveMessage($data)) {
        echo json_encode(['success' => true, 'message' => 'Message enregistré']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'enregistrement']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}
?>
