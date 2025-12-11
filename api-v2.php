<?php
/**
 * API V2 avec support CalDAV
 * Compatible avec l'ancienne API mais utilise des UID au lieu d'index
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// Charger le client approprié selon la configuration
if (config('use_caldav')) {
    require_once __DIR__ . '/simple-caldav-client.php';
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

// Authentification pour POST, PUT, DELETE
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    if (!isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['error' => 'Non authentifié']);
        exit;
    }
    requireCsrfToken();
}

/**
 * Obtenir le client de données (CalDAV ou Gist/fichier)
 */
function getDataClient() {
    if (config('use_caldav')) {
        return new SimpleCalDAVClient();
    }
    return null; // Utiliser les fonctions legacy
}

/**
 * Lire tous les événements (filtre les événements passés)
 */
function getAllEvents() {
    $client = getDataClient();

    if ($client) {
        // V2: CalDAV
        try {
            $events = $client->getEvents();
        } catch (Exception $e) {
            logError('Erreur CalDAV getEvents: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur serveur CalDAV']);
            exit;
        }
    } else {
        // V1: Gist/fichier
        $events = getEventsLegacy();
    }

    // Filtrer les événements passés (garder seulement aujourd'hui et futur)
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    return array_values(array_filter($events, function($event) use ($today) {
        // Garder tous les événements récurrents
        if (isset($event['recurrent'])) {
            return true;
        }

        // Pour les événements à date fixe, vérifier qu'ils ne sont pas passés
        if (isset($event['date'])) {
            $eventDate = new DateTime($event['date']);
            return $eventDate >= $today;
        }

        return true;
    }));
}

/**
 * Créer un événement
 */
function createEvent($eventData) {
    $client = getDataClient();

    if ($client) {
        // V2: CalDAV
        try {
            $uid = $client->createEvent($eventData);
            if ($uid) {
                return ['success' => true, 'uid' => $uid, 'message' => 'Événement créé'];
            } else {
                http_response_code(500);
                return ['error' => 'Échec de la création'];
            }
        } catch (Exception $e) {
            logError('Erreur CalDAV createEvent: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Erreur serveur CalDAV'];
        }
    }

    // V1: Ajouter à la liste
    $events = getEventsLegacy();
    $events[] = $eventData;

    if (saveEventsLegacy($events)) {
        return ['success' => true, 'message' => 'Événement ajouté'];
    } else {
        http_response_code(500);
        return ['error' => 'Erreur lors de l\'enregistrement'];
    }
}

/**
 * Modifier un événement
 */
function updateEvent($input) {
    $client = getDataClient();

    if ($client) {
        // V2: CalDAV - utilise UID
        if (empty($input['uid'])) {
            http_response_code(400);
            return ['error' => 'UID requis'];
        }

        try {
            if ($client->updateEvent($input['uid'], $input)) {
                return ['success' => true, 'message' => 'Événement modifié'];
            } else {
                http_response_code(500);
                return ['error' => 'Échec de la modification'];
            }
        } catch (Exception $e) {
            logError('Erreur CalDAV updateEvent: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Erreur serveur CalDAV'];
        }
    }

    // V1: Utilise index
    if (!isset($input['index']) || !is_numeric($input['index'])) {
        http_response_code(400);
        return ['error' => 'Index invalide'];
    }

    $events = getEventsLegacy();
    $index = intval($input['index']);

    if ($index < 0 || $index >= count($events)) {
        http_response_code(404);
        return ['error' => 'Événement non trouvé'];
    }

    // Créer l'événement modifié (sans l'index)
    $updatedEvent = [
        'titre' => $input['titre'],
        'couleur' => $input['couleur'] ?? '#feff9c'
    ];

    if (isset($input['heure']) && trim($input['heure']) !== '') {
        $updatedEvent['heure'] = $input['heure'];
    }

    if (isset($input['date'])) {
        $updatedEvent['date'] = $input['date'];
    } elseif (isset($input['recurrent'])) {
        $updatedEvent['recurrent'] = $input['recurrent'];
    }

    $events[$index] = $updatedEvent;

    if (saveEventsLegacy($events)) {
        return ['success' => true, 'message' => 'Événement modifié'];
    } else {
        http_response_code(500);
        return ['error' => 'Erreur lors de la modification'];
    }
}

/**
 * Supprimer un événement
 */
function deleteEvent($input) {
    $client = getDataClient();

    if ($client) {
        // V2: CalDAV - utilise UID
        if (empty($input['uid'])) {
            http_response_code(400);
            return ['error' => 'UID requis'];
        }

        try {
            if ($client->deleteEvent($input['uid'])) {
                return ['success' => true, 'message' => 'Événement supprimé'];
            } else {
                http_response_code(500);
                return ['error' => 'Échec de la suppression'];
            }
        } catch (Exception $e) {
            logError('Erreur CalDAV deleteEvent: ' . $e->getMessage());
            http_response_code(500);
            return ['error' => 'Erreur serveur CalDAV'];
        }
    }

    // V1: Utilise index
    if (!isset($input['index']) || !is_numeric($input['index'])) {
        http_response_code(400);
        return ['error' => 'Index invalide'];
    }

    $events = getEventsLegacy();
    $index = intval($input['index']);

    if ($index < 0 || $index >= count($events)) {
        http_response_code(404);
        return ['error' => 'Événement non trouvé'];
    }

    array_splice($events, $index, 1);

    if (saveEventsLegacy($events)) {
        return ['success' => true, 'message' => 'Événement supprimé'];
    } else {
        http_response_code(500);
        return ['error' => 'Erreur lors de la suppression'];
    }
}

// ========= Fonctions Legacy (Gist/fichier) =========

function getFromGist() {
    $gistId = config('gist_id');
    $token = config('gist_token');

    if (empty($gistId) || empty($token)) {
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

    return $httpCode === 200;
}

function getEventsLegacy() {
    if (config('use_gist')) {
        $events = getFromGist();
        if ($events !== null) {
            return $events;
        }
    }

    $jsonFile = config('db_file');
    if (!file_exists($jsonFile)) {
        return [];
    }
    $content = file_get_contents($jsonFile);
    return json_decode($content, true) ?: [];
}

function saveEventsLegacy($events) {
    if (config('use_gist')) {
        $success = saveToGist($events);
        if ($success) {
            return true;
        }
    }

    $jsonFile = config('db_file');
    $json = json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($jsonFile, $json) !== false;
}

// ========= Routes HTTP =========

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Récupérer tous les événements
    $events = getAllEvents();
    echo json_encode($events);

} elseif ($method === 'POST') {
    // Ajouter un nouvel événement
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['titre']) || trim($input['titre']) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Titre requis']);
        exit;
    }

    if (!isset($input['date']) && !isset($input['recurrent'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Date ou récurrence requise']);
        exit;
    }

    $result = createEvent($input);
    echo json_encode($result);

} elseif ($method === 'PUT') {
    // Modifier un événement
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['titre']) || trim($input['titre']) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Titre requis']);
        exit;
    }

    if (!isset($input['date']) && !isset($input['recurrent'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Date ou récurrence requise']);
        exit;
    }

    $result = updateEvent($input);
    echo json_encode($result);

} elseif ($method === 'DELETE') {
    // Supprimer un événement
    $input = json_decode(file_get_contents('php://input'), true);

    $result = deleteEvent($input);
    echo json_encode($result);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
}
?>
