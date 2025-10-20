<?php
/**
 * Configuration de l'application - Gestion des environnements
 *
 * Ce fichier charge la configuration en fonction de l'environnement (dev/prod)
 */

// Détecter l'environnement
// Par défaut, on utilise le fichier .env s'il existe, sinon on utilise la variable d'environnement
$dotenv = __DIR__ . '/.env';
if (file_exists($dotenv)) {
    $lines = @file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parser la ligne KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Supprimer les guillemets si présents
                $value = trim($value, '"\'');

                // Définir la variable d'environnement si elle n'existe pas déjà
                if (!isset($_ENV[$key]) && !getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                }
            }
        }
    }
}

// Récupérer l'environnement (dev par défaut)
define('APP_ENV', getenv('APP_ENV') ?: 'dev');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true' || APP_ENV === 'dev');

// Configuration selon l'environnement
$config = [
    'dev' => [
        'db_file' => __DIR__ . '/evenements.json',
        'upload_dir' => __DIR__ . '/images/',
        'upload_max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_image_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        'cors_origin' => '*',
        'error_reporting' => E_ALL,
        'display_errors' => true,
        'log_file' => __DIR__ . '/logs/app.log',
        // Cloudinary (optionnel en dev)
        'use_cloudinary' => getenv('CLOUDINARY_CLOUD_NAME') ? true : false,
        'cloudinary' => [
            'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
            'api_key' => getenv('CLOUDINARY_API_KEY'),
            'api_secret' => getenv('CLOUDINARY_API_SECRET'),
            'folder' => 'agenda_dev', // Dossier dans Cloudinary
        ],
    ],
    'prod' => [
        'db_file' => __DIR__ . '/evenements.json',
        'upload_dir' => __DIR__ . '/images/',
        'upload_max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_image_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        'cors_origin' => getenv('CORS_ORIGIN') ?: 'https://papoumamine.cincet.net',
        'error_reporting' => E_ALL & ~E_NOTICE & ~E_DEPRECATED,
        'display_errors' => false,
        'log_file' => __DIR__ . '/logs/app.log',
        // Cloudinary (activé en prod)
        'use_cloudinary' => true,
        'cloudinary' => [
            'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
            'api_key' => getenv('CLOUDINARY_API_KEY'),
            'api_secret' => getenv('CLOUDINARY_API_SECRET'),
            'folder' => 'agenda_prod', // Dossier dans Cloudinary
        ],
    ]
];

// Sélectionner la configuration
$currentConfig = $config[APP_ENV] ?? $config['dev'];

// Appliquer la configuration PHP
error_reporting($currentConfig['error_reporting']);
ini_set('display_errors', $currentConfig['display_errors'] ? '1' : '0');

// Fonction helper pour accéder à la configuration
function config($key, $default = null) {
    global $currentConfig;
    return $currentConfig[$key] ?? $default;
}

// Fonction pour logger les erreurs
function logError($message, $context = []) {
    $logFile = config('log_file');
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logMessage = "[$timestamp] " . APP_ENV . " - $message$contextStr" . PHP_EOL;

    @file_put_contents($logFile, $logMessage, FILE_APPEND);

    // En mode debug, afficher aussi l'erreur
    if (APP_DEBUG) {
        error_log($logMessage);
    }
}

// Retourner la configuration
return $currentConfig;
