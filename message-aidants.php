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

$jsonFile = __DIR__ . '/message-aidants.json';

// Fonctions Gist pour message aidants
function getMessageFromGist() {
    $gistId = config('gist_id');
    $token = config('gist_token');
    $filename = 'message-aidants.json';

    if (empty($gistId) || empty($token)) {
        logError('Configuration Gist manquante pour message aidants');
        return null;
    }

    $url = "https://api.github.com/gists/{$gistId}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: PHP-Agenda-App',
        'Accept: application/vnd.github.v3+json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        logError('Erreur lors de la lecture du Gist message aidants', ['http_code' => $httpCode]);
        return null;
    }

    $gist = json_decode($response, true);

    if (isset($gist['files'][$filename]['content'])) {
        return json_decode($gist['files'][$filename]['content'], true) ?: ['texte' => ''];
    }

    return null;
}

function saveMessageToGist($data) {
    $gistId = config('gist_id');
    $token = config('gist_token');
    $filename = 'message-aidants.json';

    if (empty($gistId) || empty($token)) {
        logError('Configuration Gist manquante pour message aidants');
        return false;
    }

    $url = "https://api.github.com/gists/{$gistId}";

    $gistData = [
        'files' => [
            $filename => [
                'content' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gistData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: PHP-Agenda-App',
        'Accept: application/vnd.github.v3+json',
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        logError('Erreur lors de l\'écriture du Gist message aidants', ['http_code' => $httpCode]);
        return false;
    }

    return true;
}

// Lire le message
function getMessage() {
    if (config('use_gist')) {
        $message = getMessageFromGist();
        if ($message !== null) {
            return $message;
        }
        logError('Fallback sur le fichier local après échec Gist message aidants');
    }

    global $jsonFile;
    if (!file_exists($jsonFile)) {
        return ['texte' => ''];
    }
    $content = file_get_contents($jsonFile);
    return json_decode($content, true) ?: ['texte' => ''];
}

// Écrire le message
function saveMessage($data) {
    if (config('use_gist')) {
        $success = saveMessageToGist($data);
        if ($success) {
            return true;
        }
        logError('Fallback sur le fichier local après échec écriture Gist message aidants');
    }

    global $jsonFile;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($jsonFile, $json) !== false;
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
