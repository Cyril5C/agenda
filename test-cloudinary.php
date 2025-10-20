<?php
/**
 * Script de test de la configuration Cloudinary
 * À utiliser pour vérifier que vos identifiants Cloudinary sont corrects
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST CONFIGURATION CLOUDINARY ===\n\n";

echo "Environnement: " . APP_ENV . "\n";
echo "Mode Debug: " . (APP_DEBUG ? 'Activé' : 'Désactivé') . "\n";
echo "Cloudinary activé: " . (config('use_cloudinary') ? 'OUI' : 'NON') . "\n\n";

if (config('use_cloudinary')) {
    $cloudinary = config('cloudinary');

    echo "Configuration Cloudinary:\n";
    echo "- Cloud Name: " . ($cloudinary['cloud_name'] ? '✓ Configuré' : '✗ Manquant') . "\n";
    echo "- API Key: " . ($cloudinary['api_key'] ? '✓ Configuré' : '✗ Manquant') . "\n";
    echo "- API Secret: " . ($cloudinary['api_secret'] ? '✓ Configuré (masqué)' : '✗ Manquant') . "\n";
    echo "- Dossier: " . $cloudinary['folder'] . "\n\n";

    if ($cloudinary['cloud_name'] && $cloudinary['api_key'] && $cloudinary['api_secret']) {
        echo "✓ Configuration complète!\n";
        echo "\nProchaines étapes:\n";
        echo "1. Allez sur admin.html\n";
        echo "2. Créez un événement avec une image\n";
        echo "3. L'image sera automatiquement uploadée sur Cloudinary\n";
        echo "4. Vérifiez sur https://console.cloudinary.com/console/media_library\n";
    } else {
        echo "✗ Configuration incomplète!\n";
        echo "\nPour configurer Cloudinary:\n";
        echo "1. Éditez le fichier .env\n";
        echo "2. Ajoutez vos identifiants Cloudinary:\n";
        echo "   CLOUDINARY_CLOUD_NAME=votre-cloud-name\n";
        echo "   CLOUDINARY_API_KEY=votre-api-key\n";
        echo "   CLOUDINARY_API_SECRET=votre-api-secret\n";
        echo "3. Relancez le serveur: ./start-dev.sh\n";
    }
} else {
    echo "Mode local activé - les images seront sauvegardées dans /images/\n";
    echo "\nPour activer Cloudinary:\n";
    echo "1. Créez un compte sur https://cloudinary.com/users/register_free\n";
    echo "2. Ajoutez vos identifiants dans .env\n";
    echo "3. En production (Railway), Cloudinary sera automatiquement activé\n";
}

echo "\n=== FIN DU TEST ===\n";
?>
