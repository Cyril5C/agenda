<?php
// Router PHP pour le serveur built-in avec configuration custom
// Ce fichier est appelé par php -S et permet de configurer l'environnement

// Configurer les limites (avant toute requête)
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '120');

// Note: upload_max_filesize et post_max_size ne peuvent pas être modifiés avec ini_set
// Ils doivent être définis via les flags -d au démarrage du serveur

// Passer au routage normal
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// Si c'est un fichier qui existe et n'est pas un .php, le servir directement
if ($path !== '/' && file_exists($file) && !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false; // Laisser le serveur built-in servir le fichier
}

// Si c'est un fichier PHP, l'inclure
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    include $file;
    return true;
}

// Si c'est un répertoire, chercher index.php ou index.html
if (is_dir($file)) {
    if (file_exists($file . '/index.php')) {
        include $file . '/index.php';
        return true;
    }
    if (file_exists($file . '/index.html')) {
        include $file . '/index.html';
        return true;
    }
}

// 404
http_response_code(404);
echo "404 Not Found";
return true;
