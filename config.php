<?php
/**
 * Configuration de l'application
 * Gestion des environnements dev/prod
 */

// Charger le fichier .env s'il existe (mode dev local)
$dotenv = __DIR__ . '/.env';
if (file_exists($dotenv)) {
    $lines = @file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');

            if (!isset($_ENV[$key]) && !getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Définir l'environnement
define('APP_ENV', getenv('APP_ENV') ?: 'dev');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true' || APP_ENV === 'dev');

// Configuration par environnement
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
        'use_cloudinary' => getenv('CLOUDINARY_CLOUD_NAME') ? true : false,
        'cloudinary' => [
            'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
            'api_key' => getenv('CLOUDINARY_API_KEY'),
            'api_secret' => getenv('CLOUDINARY_API_SECRET'),
            'folder' => 'agenda_dev',
        ],
    ],
    'prod' => [
        'db_file' => __DIR__ . '/evenements.json',
        'upload_dir' => __DIR__ . '/images/',
        'upload_max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_image_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        'cors_origin' => getenv('CORS_ORIGIN') ?: '*', // Permissif par défaut, définir CORS_ORIGIN sur Railway pour restreindre
        'error_reporting' => E_ALL & ~E_NOTICE & ~E_DEPRECATED,
        'display_errors' => false,
        'log_file' => __DIR__ . '/logs/app.log',
        'use_cloudinary' => true,
        'cloudinary' => [
            'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME'),
            'api_key' => getenv('CLOUDINARY_API_KEY'),
            'api_secret' => getenv('CLOUDINARY_API_SECRET'),
            'folder' => 'agenda_prod',
        ],
    ]
];

// Sélectionner et appliquer la configuration
$currentConfig = $config[APP_ENV] ?? $config['dev'];
error_reporting($currentConfig['error_reporting']);
ini_set('display_errors', $currentConfig['display_errors'] ? '1' : '0');

/**
 * Accéder à une valeur de configuration
 */
function config($key, $default = null) {
    global $currentConfig;
    return $currentConfig[$key] ?? $default;
}

/**
 * Logger un message
 */
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

    if (APP_DEBUG) {
        error_log($logMessage);
    }
}

return $currentConfig;
