<?php
/**
 * Script de test pour la connexion CalDAV
 * Usage: php test-caldav.php
 */

require_once __DIR__ . '/caldav-client.php';

echo "🧪 Test de connexion CalDAV avec Nextcloud\n";
echo "==========================================\n\n";

// Vérifier la configuration
echo "📋 Vérification de la configuration...\n";
$url = config('caldav_url');
$username = config('caldav_username');
$password = config('caldav_password');
$calendar = config('caldav_calendar');

if (empty($url)) {
    die("❌ CALDAV_URL non défini dans .env\n");
}
if (empty($username)) {
    die("❌ CALDAV_USERNAME non défini dans .env\n");
}
if (empty($password)) {
    die("❌ CALDAV_PASSWORD non défini dans .env\n");
}
if (empty($calendar)) {
    die("❌ CALDAV_CALENDAR non défini dans .env\n");
}

echo "✅ URL: $url\n";
echo "✅ Username: $username\n";
echo "✅ Calendar: $calendar\n\n";

// Test de connexion
echo "🔌 Test de connexion...\n";
try {
    $caldav = new CalDAVClient();
    echo "✅ Client CalDAV initialisé\n\n";
} catch (Exception $e) {
    die("❌ Erreur d'initialisation: " . $e->getMessage() . "\n");
}

// Test de lecture des événements
echo "📅 Lecture des événements...\n";
try {
    $events = $caldav->getEvents();
    echo "✅ " . count($events) . " événement(s) trouvé(s)\n\n";

    if (count($events) > 0) {
        echo "📋 Premiers événements:\n";
        foreach (array_slice($events, 0, 3) as $event) {
            echo "  - " . $event['titre'];
            if (isset($event['date'])) echo " (" . $event['date'];
            if (isset($event['heure'])) echo " à " . $event['heure'];
            if (isset($event['date'])) echo ")";
            echo "\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur lecture: " . $e->getMessage() . "\n\n";
}

// Test de création d'événement
echo "➕ Test de création d'événement...\n";
try {
    $testEvent = [
        'titre' => 'Test CalDAV - ' . date('Y-m-d H:i:s'),
        'date' => date('Y-m-d', strtotime('+1 day')),
        'heure' => '14:00',
        'couleur' => '#feff9c',
    ];

    $uid = $caldav->createEvent($testEvent);

    if ($uid) {
        echo "✅ Événement créé avec UID: $uid\n\n";

        // Test de suppression
        echo "🗑️  Test de suppression...\n";
        if ($caldav->deleteEvent($uid)) {
            echo "✅ Événement supprimé\n\n";
        } else {
            echo "❌ Échec de la suppression\n\n";
        }
    } else {
        echo "❌ Échec de la création\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur création/suppression: " . $e->getMessage() . "\n\n";
}

echo "✨ Tests terminés!\n";
?>
