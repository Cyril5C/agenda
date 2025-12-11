<?php
/**
 * Test du client CalDAV simple
 */

require_once __DIR__ . '/simple-caldav-client.php';

echo "🧪 Test du client CalDAV simple\n";
echo "================================\n\n";

try {
    $client = new SimpleCalDAVClient();
    echo "✅ Client CalDAV initialisé\n\n";
} catch (Exception $e) {
    die("❌ Erreur: " . $e->getMessage() . "\n");
}

// Test 1: Lecture des événements
echo "📅 Test 1: Lecture des événements existants\n";
echo "--------------------------------------------\n";
$events = $client->getEvents();
echo "Nombre d'événements: " . count($events) . "\n";

if (count($events) > 0) {
    echo "\nPremiers événements:\n";
    foreach (array_slice($events, 0, 3) as $event) {
        echo "  • " . $event['titre'];
        if (isset($event['date'])) echo " (" . $event['date'];
        if (isset($event['heure'])) echo " à " . $event['heure'];
        if (isset($event['date'])) echo ")";
        if (isset($event['recurrent'])) echo " [Récurrent: " . $event['recurrent'] . "]";
        echo "\n";
    }
}
echo "\n";

// Test 2: Création d'un événement simple
echo "➕ Test 2: Création d'un événement simple\n";
echo "-------------------------------------------\n";
$testEvent1 = [
    'titre' => 'Test événement simple - ' . date('H:i:s'),
    'date' => date('Y-m-d', strtotime('+1 day')),
    'heure' => '14:00',
    'couleur' => '#feff9c',
];

$uid1 = $client->createEvent($testEvent1);
if ($uid1) {
    echo "✅ Événement créé avec UID: $uid1\n\n";
} else {
    echo "❌ Échec de la création\n\n";
}

// Test 3: Création d'un événement toute la journée
echo "➕ Test 3: Création d'un événement toute la journée\n";
echo "----------------------------------------------------\n";
$testEvent2 = [
    'titre' => 'Test journée entière - ' . date('H:i:s'),
    'date' => date('Y-m-d', strtotime('+2 days')),
    'couleur' => '#a7ffeb',
];

$uid2 = $client->createEvent($testEvent2);
if ($uid2) {
    echo "✅ Événement créé avec UID: $uid2\n\n";
} else {
    echo "❌ Échec de la création\n\n";
}

// Test 4: Création d'un événement récurrent
echo "➕ Test 4: Création d'un événement récurrent\n";
echo "----------------------------------------------\n";
$testEvent3 = [
    'titre' => 'Test récurrent quotidien',
    'date' => date('Y-m-d'),
    'heure' => '09:00',
    'couleur' => '#cbf0f8',
    'recurrent' => 'quotidien',
];

$uid3 = $client->createEvent($testEvent3);
if ($uid3) {
    echo "✅ Événement récurrent créé avec UID: $uid3\n\n";
} else {
    echo "❌ Échec de la création\n\n";
}

// Attendre un peu pour que le serveur enregistre
sleep(1);

// Test 5: Vérifier que les événements ont été créés
echo "🔍 Test 5: Vérification des événements créés\n";
echo "----------------------------------------------\n";
$events = $client->getEvents();
echo "Nombre total d'événements: " . count($events) . "\n";

$found = 0;
foreach ($events as $event) {
    if (isset($event['uid']) && in_array($event['uid'], [$uid1, $uid2, $uid3])) {
        echo "  ✅ Trouvé: " . $event['titre'] . "\n";
        $found++;
    }
}
echo "Événements de test trouvés: $found/3\n\n";

// Test 6: Modification d'un événement
if ($uid1) {
    echo "✏️  Test 6: Modification d'un événement\n";
    echo "----------------------------------------\n";
    $modifiedEvent = [
        'titre' => 'Événement MODIFIÉ - ' . date('H:i:s'),
        'date' => date('Y-m-d', strtotime('+1 day')),
        'heure' => '15:30',
        'couleur' => '#d7aefb',
    ];

    if ($client->updateEvent($uid1, $modifiedEvent)) {
        echo "✅ Événement modifié\n\n";
    } else {
        echo "❌ Échec de la modification\n\n";
    }
}

// Test 7: Suppression des événements de test
echo "🗑️  Test 7: Suppression des événements de test\n";
echo "------------------------------------------------\n";

$deleted = 0;
foreach ([$uid1, $uid2, $uid3] as $uid) {
    if ($uid && $client->deleteEvent($uid)) {
        echo "  ✅ Supprimé: $uid\n";
        $deleted++;
    }
}
echo "Événements supprimés: $deleted\n\n";

// Vérification finale
sleep(1);
echo "🔍 Vérification finale\n";
echo "----------------------\n";
$events = $client->getEvents();
echo "Nombre d'événements restants: " . count($events) . "\n\n";

echo "✨ Tests terminés!\n";
?>
