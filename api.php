<?php
// Charger la configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// V2: Charger le client CalDAV si configuré
if (config('use_caldav')) {
    require_once __DIR__ . '/simple-caldav-client.php';
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

// Les requêtes GET sont publiques (lecture seule)
// Les requêtes POST, PUT, DELETE nécessitent authentification
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    // Vérifier l'authentification
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['error' => 'Non authentifié']);
        exit;
    }

    // Vérifier le token CSRF
    requireCsrfToken();
}

$jsonFile = config('db_file');

// Fonctions Gist
function getFromGist() {
    $gistId = config('gist_id');
    $token = config('gist_token');

    if (empty($gistId) || empty($token)) {
        logError('Configuration Gist manquante');
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
        logError('Erreur lors de la lecture du Gist', ['http_code' => $httpCode]);
        return null;
    }

    $gist = json_decode($response, true);
    $filename = config('gist_filename');

    if (isset($gist['files'][$filename]['content'])) {
        return json_decode($gist['files'][$filename]['content'], true) ?: [];
    }

    return null;
}

function saveToGist($events) {
    $gistId = config('gist_id');
    $token = config('gist_token');
    $filename = config('gist_filename');

    if (empty($gistId) || empty($token)) {
        logError('Configuration Gist manquante');
        return false;
    }

    $url = "https://api.github.com/gists/{$gistId}";

    $data = [
        'files' => [
            $filename => [
                'content' => json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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
        logError('Erreur lors de l\'écriture du Gist', ['http_code' => $httpCode]);
        return false;
    }

    return true;
}

// Lire les événements
function getEvents() {
    // V2: Priorité à CalDAV
    if (config('use_caldav')) {
        try {
            $client = new SimpleCalDAVClient();
            $events = $client->getEvents();
            return $events;
        } catch (Exception $e) {
            logError('Erreur CalDAV getEvents: ' . $e->getMessage());
            // Pas de fallback pour CalDAV, on retourne une erreur
            return [];
        }
    }

    // V1: Gist (obsolète)
    if (config('use_gist')) {
        $events = getFromGist();
        if ($events !== null) {
            return $events;
        }
        // Fallback sur le fichier local si Gist échoue
        logError('Fallback sur le fichier local après échec Gist');
    }

    // Fallback fichier local
    global $jsonFile;
    if (!file_exists($jsonFile)) {
        return [];
    }
    $content = file_get_contents($jsonFile);
    return json_decode($content, true) ?: [];
}

// Écrire les événements
function saveEvents($events) {
    if (config('use_gist')) {
        $success = saveToGist($events);
        if ($success) {
            return true;
        }
        // Fallback sur le fichier local si Gist échoue
        logError('Fallback sur le fichier local après échec écriture Gist');
    }

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

    // L'heure est maintenant optionnelle, seul le titre est obligatoire
    if (!$input || !isset($input['titre']) || trim($input['titre']) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Titre requis']);
        logError('Tentative d\'ajout d\'événement sans titre', $input);
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

} elseif ($method === 'PUT') {
    // Modifier un événement existant
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['index']) || !is_numeric($input['index'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Index invalide']);
        exit;
    }

    // L'heure est optionnelle, seul le titre est obligatoire
    if (!isset($input['titre']) || trim($input['titre']) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Titre requis']);
        exit;
    }

    // Valider que c'est soit une date fixe, soit récurrent
    if (!isset($input['date']) && !isset($input['recurrent'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Date ou récurrence requise']);
        exit;
    }

    $events = getEvents();
    $index = intval($input['index']);

    if ($index < 0 || $index >= count($events)) {
        http_response_code(404);
        echo json_encode(['error' => 'Événement non trouvé']);
        exit;
    }

    // Créer l'événement modifié (sans l'index)
    $updatedEvent = [
        'titre' => $input['titre'],
        'couleur' => $input['couleur'] ?? '#feff9c'
    ];

    // Ajouter l'heure si elle est fournie
    if (isset($input['heure']) && trim($input['heure']) !== '') {
        $updatedEvent['heure'] = $input['heure'];
    }

    if (isset($input['date'])) {
        $updatedEvent['date'] = $input['date'];
    } elseif (isset($input['recurrent'])) {
        $updatedEvent['recurrent'] = $input['recurrent'];
    }

    // Remplacer l'événement
    $events[$index] = $updatedEvent;

    if (saveEvents($events)) {
        echo json_encode(['success' => true, 'message' => 'Événement modifié']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la modification']);
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
