<?php
/**
 * Script de nettoyage des événements passés
 * Supprime automatiquement les événements avec date fixe qui sont dans le passé
 */

require_once __DIR__ . '/simple-caldav-client.php';

echo "🧹 Nettoyage des événements passés\n";
echo "===================================\n\n";

try {
    $client = new SimpleCalDAVClient();
    $events = $client->getEvents();

    echo "📊 Total d'événements: " . count($events) . "\n\n";

    $today = new DateTime();
    $today->setTime(0, 0, 0);

    $toDelete = [];

    // Identifier les événements passés (non récurrents uniquement)
    foreach ($events as $event) {
        if (!isset($event['recurrent']) && isset($event['date'])) {
            $eventDate = new DateTime($event['date']);
            if ($eventDate < $today && isset($event['uid'])) {
                $toDelete[] = $event;
            }
        }
    }

    if (empty($toDelete)) {
        echo "✅ Aucun événement passé à supprimer.\n";
        exit(0);
    }

    echo "⚠️  Événements passés trouvés: " . count($toDelete) . "\n\n";
    echo "Liste des événements à supprimer:\n";
    echo "-----------------------------------\n";

    // Trier par date
    usort($toDelete, function($a, $b) {
        return strcmp($a['date'], $b['date']);
    });

    foreach ($toDelete as $event) {
        echo sprintf(
            "  • %s - %s [UID: %s]\n",
            $event['date'],
            $event['titre'],
            substr($event['uid'], 0, 30) . '...'
        );
    }

    echo "\n";
    echo "⚠️  ATTENTION: Cette action est irréversible!\n";
    echo "Voulez-vous supprimer ces " . count($toDelete) . " événements? (oui/non): ";

    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if (strtolower($line) !== 'oui') {
        echo "\n❌ Nettoyage annulé.\n";
        exit(0);
    }

    echo "\n🗑️  Suppression en cours...\n";
    $deleted = 0;
    $errors = 0;

    foreach ($toDelete as $event) {
        if ($client->deleteEvent($event['uid'])) {
            echo "  ✅ Supprimé: " . $event['titre'] . "\n";
            $deleted++;
        } else {
            echo "  ❌ Erreur: " . $event['titre'] . "\n";
            $errors++;
        }
    }

    echo "\n";
    echo "📊 Résumé:\n";
    echo "  - Supprimés: $deleted\n";
    echo "  - Erreurs: $errors\n";
    echo "\n✨ Nettoyage terminé!\n";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>
