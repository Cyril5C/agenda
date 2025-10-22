<?php
// Charger la configuration
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

$jsonFile = 'infos.json';

// Lire les informations
function getInfos() {
    global $jsonFile;
    if (!file_exists($jsonFile)) {
        return ['texte' => ''];
    }
    $content = file_get_contents($jsonFile);
    return json_decode($content, true) ?: ['texte' => ''];
}

// Écrire les informations
function saveInfos($data) {
    global $jsonFile;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($jsonFile, $json) !== false;
}

// Gérer les requêtes
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Récupérer les informations
    $infos = getInfos();
    echo json_encode([
        'success' => true,
        'texte' => $infos['texte']
    ]);

} elseif ($method === 'POST' || $method === 'PUT') {
    // Modifier les informations
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['texte'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Texte requis']);
        exit;
    }

    $data = ['texte' => $input['texte']];

    if (saveInfos($data)) {
        echo json_encode(['success' => true, 'message' => 'Informations enregistrées']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'enregistrement']);
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}
?>
