<?php
/**
 * Script de nettoyage des événements passés
 * Supprime les événements avec une date passée, SAUF les événements récurrents
 *
 * À exécuter via cron chaque jour à minuit :
 * 0 0 * * * cd /path/to/project && php cleanup-old-events.php
 */

require_once __DIR__ . '/config.php';

$logFile = __DIR__ . '/logs/cleanup.log';

// Créer le dossier logs s'il n'existe pas
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

function logCleanup($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logLine, FILE_APPEND);
    echo $logLine;
}

// Fonctions Gist
function getEventsFromGist() {
    $gistId = config('gist_id');
    $token = config('gist_token');
    $filename = config('gist_events_filename');

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

    if (isset($gist['files'][$filename]['content'])) {
        return json_decode($gist['files'][$filename]['content'], true) ?: [];
    }

    return null;
}

function saveEventsToGist($events) {
    $gistId = config('gist_id');
    $token = config('gist_token');
    $filename = config('gist_events_filename');

    if (empty($gistId) || empty($token)) {
        return false;
    }

    $url = "https://api.github.com/gists/{$gistId}";

    $gistData = [
        'files' => [
            $filename => [
                'content' => json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
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

    return $httpCode === 200;
}

// Fonction principale de nettoyage
function cleanupOldEvents() {
    logCleanup('=== Début du nettoyage des événements ===');

    // Charger les événements
    $events = [];
    if (config('use_gist')) {
        logCleanup('Chargement depuis Gist...');
        $events = getEventsFromGist();
        if ($events === null) {
            logCleanup('ERREUR: Impossible de charger depuis Gist, tentative fichier local');
            $jsonFile = __DIR__ . '/evenements.json';
            if (file_exists($jsonFile)) {
                $events = json_decode(file_get_contents($jsonFile), true) ?: [];
            }
        }
    } else {
        logCleanup('Chargement depuis fichier local...');
        $jsonFile = __DIR__ . '/evenements.json';
        if (file_exists($jsonFile)) {
            $events = json_decode(file_get_contents($jsonFile), true) ?: [];
        }
    }

    if (empty($events)) {
        logCleanup('Aucun événement à traiter');
        return;
    }

    $totalBefore = count($events);
    logCleanup("Nombre d'événements avant nettoyage: {$totalBefore}");

    // Date d'aujourd'hui au format YYYY-MM-DD
    $today = date('Y-m-d');
    logCleanup("Date du jour: {$today}");

    // Filtrer les événements
    $eventsToKeep = [];
    $deletedCount = 0;
    $keptRecurrentCount = 0;

    foreach ($events as $event) {
        // Garder TOUS les événements récurrents (pas de date fixe)
        if (isset($event['recurrent']) && !empty($event['recurrent'])) {
            $eventsToKeep[] = $event;
            $keptRecurrentCount++;
            continue;
        }

        // Pour les événements avec date fixe, garder seulement ceux >= aujourd'hui
        if (isset($event['date'])) {
            if ($event['date'] >= $today) {
                $eventsToKeep[] = $event;
            } else {
                logCleanup("Suppression: {$event['titre']} (date: {$event['date']})");
                $deletedCount++;
            }
        } else {
            // Événement sans date ni récurrence (anormal, on le garde par sécurité)
            $eventsToKeep[] = $event;
            logCleanup("WARNING: Événement sans date ni récurrence gardé: {$event['titre']}");
        }
    }

    $totalAfter = count($eventsToKeep);

    logCleanup("Événements supprimés: {$deletedCount}");
    logCleanup("Événements récurrents conservés: {$keptRecurrentCount}");
    logCleanup("Nombre d'événements après nettoyage: {$totalAfter}");

    // Sauvegarder si des changements ont été faits
    if ($deletedCount > 0) {
        logCleanup('Sauvegarde des événements nettoyés...');

        $saved = false;
        if (config('use_gist')) {
            $saved = saveEventsToGist($eventsToKeep);
            if (!$saved) {
                logCleanup('ERREUR: Impossible de sauvegarder dans Gist, tentative fichier local');
            }
        }

        if (!config('use_gist') || !$saved) {
            $jsonFile = __DIR__ . '/evenements.json';
            $json = json_encode($eventsToKeep, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $saved = file_put_contents($jsonFile, $json) !== false;
        }

        if ($saved) {
            logCleanup('✅ Sauvegarde réussie');
        } else {
            logCleanup('❌ ERREUR lors de la sauvegarde');
        }
    } else {
        logCleanup('Aucun événement à supprimer');
    }

    logCleanup('=== Fin du nettoyage ===');
}

// Exécuter le nettoyage
try {
    cleanupOldEvents();
} catch (Exception $e) {
    logCleanup('ERREUR CRITIQUE: ' . $e->getMessage());
    exit(1);
}

exit(0);
?>
