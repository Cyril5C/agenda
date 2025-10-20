<?php
/**
 * Script de test de la configuration
 * À utiliser pour diagnostiquer les problèmes en production
 *
 * Accès : http://votredomaine.com/pm/test-config.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test de Configuration</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 5px; }
        pre { background: #eee; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
<h1>🔍 Diagnostic de Configuration</h1>";

// Test 1: Fichier config.php
echo "<div class='section'>";
echo "<h2>1. Fichier config.php</h2>";
if (file_exists(__DIR__ . '/config.php')) {
    echo "<p class='success'>✅ config.php existe</p>";

    try {
        require_once __DIR__ . '/config.php';
        echo "<p class='success'>✅ config.php chargé sans erreur</p>";

        // Vérifier les constantes
        if (defined('APP_ENV')) {
            echo "<p class='success'>✅ APP_ENV défini: <strong>" . APP_ENV . "</strong></p>";
        } else {
            echo "<p class='error'>❌ APP_ENV non défini</p>";
        }

        if (defined('APP_DEBUG')) {
            echo "<p class='success'>✅ APP_DEBUG défini: <strong>" . (APP_DEBUG ? 'true' : 'false') . "</strong></p>";
        } else {
            echo "<p class='error'>❌ APP_DEBUG non défini</p>";
        }

    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur lors du chargement de config.php:</p>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
} else {
    echo "<p class='error'>❌ config.php n'existe pas</p>";
}
echo "</div>";

// Test 2: Fichier .env
echo "<div class='section'>";
echo "<h2>2. Fichier .env</h2>";
if (file_exists(__DIR__ . '/.env')) {
    echo "<p class='success'>✅ .env existe</p>";
    $envContent = @file_get_contents(__DIR__ . '/.env');
    if ($envContent !== false) {
        echo "<p class='success'>✅ .env lisible</p>";
        echo "<p><strong>Contenu (variables masquées pour sécurité):</strong></p>";
        $lines = explode("\n", $envContent);
        foreach ($lines as $line) {
            if (trim($line) && strpos($line, '=') !== false) {
                list($key, ) = explode('=', $line, 2);
                echo "<code>" . htmlspecialchars(trim($key)) . "=***</code><br>";
            }
        }
    } else {
        echo "<p class='error'>❌ .env non lisible (permissions?)</p>";
    }
} else {
    echo "<p class='warning'>⚠️ .env n'existe pas (comportement par défaut sera utilisé)</p>";
}
echo "</div>";

// Test 3: Configuration
echo "<div class='section'>";
echo "<h2>3. Configuration active</h2>";
if (function_exists('config')) {
    echo "<p class='success'>✅ Fonction config() disponible</p>";

    $configKeys = ['db_file', 'upload_dir', 'cors_origin', 'upload_max_size'];
    foreach ($configKeys as $key) {
        $value = config($key);
        echo "<p><strong>" . $key . ":</strong> " . htmlspecialchars(print_r($value, true)) . "</p>";
    }
} else {
    echo "<p class='error'>❌ Fonction config() non disponible</p>";
}
echo "</div>";

// Test 4: Fichier evenements.json
echo "<div class='section'>";
echo "<h2>4. Fichier evenements.json</h2>";
$jsonFile = function_exists('config') ? config('db_file') : __DIR__ . '/evenements.json';
if (file_exists($jsonFile)) {
    echo "<p class='success'>✅ evenements.json existe</p>";
    $content = @file_get_contents($jsonFile);
    if ($content !== false) {
        echo "<p class='success'>✅ evenements.json lisible</p>";
        $events = json_decode($content, true);
        if ($events !== null) {
            echo "<p class='success'>✅ JSON valide (" . count($events) . " événement(s))</p>";
        } else {
            echo "<p class='error'>❌ JSON invalide</p>";
        }
    } else {
        echo "<p class='error'>❌ evenements.json non lisible</p>";
    }
} else {
    echo "<p class='warning'>⚠️ evenements.json n'existe pas (sera créé automatiquement)</p>";
}
echo "</div>";

// Test 5: Dossier logs
echo "<div class='section'>";
echo "<h2>5. Dossier logs</h2>";
if (is_dir(__DIR__ . '/logs')) {
    echo "<p class='success'>✅ Dossier logs existe</p>";
    if (is_writable(__DIR__ . '/logs')) {
        echo "<p class='success'>✅ Dossier logs accessible en écriture</p>";
    } else {
        echo "<p class='error'>❌ Dossier logs non accessible en écriture (chmod 755 nécessaire)</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Dossier logs n'existe pas (créez-le avec: mkdir logs && chmod 755 logs)</p>";
}
echo "</div>";

// Test 6: Dossier images
echo "<div class='section'>";
echo "<h2>6. Dossier images</h2>";
$uploadDir = function_exists('config') ? config('upload_dir') : __DIR__ . '/images/';
if (is_dir($uploadDir)) {
    echo "<p class='success'>✅ Dossier images existe</p>";
    if (is_writable($uploadDir)) {
        echo "<p class='success'>✅ Dossier images accessible en écriture</p>";
        $images = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        echo "<p>Nombre d'images: " . count($images) . "</p>";
    } else {
        echo "<p class='error'>❌ Dossier images non accessible en écriture</p>";
    }
} else {
    echo "<p class='error'>❌ Dossier images n'existe pas</p>";
}
echo "</div>";

// Test 7: API
echo "<div class='section'>";
echo "<h2>7. Test API</h2>";
if (file_exists(__DIR__ . '/api.php')) {
    echo "<p class='success'>✅ api.php existe</p>";

    // Simuler une requête GET
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    try {
        include __DIR__ . '/api.php';
        $output = ob_get_clean();
        $json = json_decode($output, true);
        if ($json !== null) {
            echo "<p class='success'>✅ API retourne du JSON valide</p>";
            echo "<p>Nombre d'événements: " . (is_array($json) ? count($json) : 'N/A') . "</p>";
        } else {
            echo "<p class='error'>❌ API ne retourne pas du JSON valide</p>";
            echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p class='error'>❌ Erreur dans api.php:</p>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
} else {
    echo "<p class='error'>❌ api.php n'existe pas</p>";
}
echo "</div>";

// Test 8: Informations PHP
echo "<div class='section'>";
echo "<h2>8. Informations PHP</h2>";
echo "<p><strong>Version PHP:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>Upload max filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>Post max size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>Display errors:</strong> " . (ini_get('display_errors') ? 'On' : 'Off') . "</p>";
echo "<p><strong>Error reporting:</strong> " . error_reporting() . "</p>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>✅ Résumé</h2>";
echo "<p>Si tous les tests sont verts ✅, votre configuration est correcte.</p>";
echo "<p>Si vous voyez des erreurs ❌ ou des avertissements ⚠️, corrigez-les avant d'utiliser l'application.</p>";
echo "<p style='color: red;'><strong>⚠️ IMPORTANT: Supprimez ce fichier test-config.php après le diagnostic pour des raisons de sécurité!</strong></p>";
echo "</div>";

echo "</body></html>";
?>
