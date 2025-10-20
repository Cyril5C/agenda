<?php
// Charger la configuration
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$jsonFile = config('db_file');

// Lire les événements
function getEvents() {
    global $jsonFile;
    if (!file_exists($jsonFile)) {
        return [];
    }
    $content = file_get_contents($jsonFile);
    return json_decode($content, true) ?: [];
}

// Écrire les événements
function saveEvents($events) {
    global $jsonFile;
    $json = json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($jsonFile, $json) !== false;
}

// Gérer les requêtes
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Récupérer tous les événements
    echo json_encode(getEvents());

} elseif ($method === 'POST') {
    // Ajouter un nouvel événement
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['heure']) || !isset($input['titre'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Données invalides']);
        logError('Tentative d\'ajout d\'événement avec données invalides', $input);
        exit;
    }

    // Valider que c'est soit une date fixe, soit récurrent
    if (!isset($input['date']) && !isset($input['recurrent'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Date ou récurrence requise']);
        exit;
    }

    $events = getEvents();
    $events[] = $input;

    if (saveEvents($events)) {
        echo json_encode(['success' => true, 'message' => 'Événement ajouté']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de l\'enregistrement']);
        logError('Erreur lors de l\'enregistrement d\'un événement', $input);
    }

} elseif ($method === 'DELETE') {
    // Supprimer un événement par index
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['index']) || !is_numeric($input['index'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Index invalide']);
        exit;
    }

    $events = getEvents();
    $index = intval($input['index']);

    if ($index < 0 || $index >= count($events)) {
        http_response_code(404);
        echo json_encode(['error' => 'Événement non trouvé']);
        exit;
    }

    array_splice($events, $index, 1);

    if (saveEvents($events)) {
        echo json_encode(['success' => true, 'message' => 'Événement supprimé']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la suppression']);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
}
?>
