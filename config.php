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
        'images_file' => __DIR__ . '/images.json',
        'cors_origin' => '*',
        'error_reporting' => E_ALL,
        'display_errors' => true,
        'log_file' => __DIR__ . '/logs/app.log',
    ],
    'prod' => [
        'db_file' => __DIR__ . '/evenements.json',
        'images_file' => __DIR__ . '/images.json',
        'cors_origin' => getenv('CORS_ORIGIN') ?: '*',
        'error_reporting' => E_ALL & ~E_NOTICE & ~E_DEPRECATED,
        'display_errors' => false,
        'log_file' => __DIR__ . '/logs/app.log',
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
