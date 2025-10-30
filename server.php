#!/usr/bin/env php
<?php
// Wrapper PHP qui configure l'environnement puis démarre le serveur

// Tenter de modifier les limites (peut ne pas fonctionner pour toutes les directives)
@ini_set('upload_max_filesize', '10M');
@ini_set('post_max_size', '10M');
@ini_set('max_execution_time', '120');
@ini_set('memory_limit', '256M');

// Démarrer le serveur built-in
$host = '0.0.0.0';
$port = getenv('PORT') ?: 8000;
$router = __DIR__ . '/router.php';

echo "Démarrage du serveur PHP sur {$host}:{$port}\n";
echo "Configuration:\n";
echo "- upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "- post_max_size: " . ini_get('post_max_size') . "\n";
echo "- max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "- memory_limit: " . ini_get('memory_limit') . "\n";

// Utiliser exec pour remplacer le processus actuel par le serveur PHP
// avec les flags -d pour forcer la configuration
$cmd = sprintf(
    'exec php -d upload_max_filesize=10M -d post_max_size=10M -d max_execution_time=120 -d memory_limit=256M -S %s:%d %s',
    $host,
    $port,
    $router
);

passthru($cmd);
