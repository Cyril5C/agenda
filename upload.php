<?php
// Charger la configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// Vérifier l'authentification (upload nécessite toujours authentification)
if (!isAuthenticated()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Vérifier le token CSRF (pour POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
}

// Charger les fonctions de gestion des images pour avoir accès aux fonctions Gist
// On doit définir les fonctions Gist ici car images.php les utilise mais ne les exporte pas
function getImagesFromGist() {
    $gistId = config('gist_id');
    $token = config('gist_token');
    $filename = config('gist_images_filename');

    if (empty($gistId) || empty($token)) {
        logError('Configuration Gist manquante pour images');
        return null;
    }

    $url = "https://api.github.com/gists/{$gistId}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: PHP-Agenda-App',
        'Accept: application/vnd.github.v3+json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        logError('Erreur lors de la lecture du Gist images', ['http_code' => $httpCode]);
        return null;
    }

    $gist = json_decode($response, true);

    if (isset($gist['files'][$filename]['content'])) {
        $images = json_decode($gist['files'][$filename]['content'], true);
        return is_array($images) ? $images : [];
    }

    return null;
}

function saveImagesToGist($images) {
    $gistId = config('gist_id');
    $token = config('gist_token');
    $filename = config('gist_images_filename');

    if (empty($gistId) || empty($token)) {
        logError('Configuration Gist manquante pour images');
        return false;
    }

    $url = "https://api.github.com/gists/{$gistId}";

    $gistData = [
        'files' => [
            $filename => [
                'content' => json_encode($images, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gistData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . $token,
        'User-Agent: PHP-Agenda-App',
        'Accept: application/vnd.github.v3+json',
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        logError('Erreur lors de l\'écriture du Gist images', ['http_code' => $httpCode]);
        return false;
    }

    return true;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Méthode non autorisée';
    echo json_encode($response);
    exit;
}

// Debug: afficher les informations reçues
if (APP_DEBUG) {
    logError('Upload attempt', [
        'FILES' => $_FILES,
        'POST' => $_POST
    ]);
}

// Vérifier qu'un fichier a été uploadé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite upload_max_filesize du php.ini',
        UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite MAX_FILE_SIZE du formulaire',
        UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été uploadé',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
        UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
        UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload'
    ];

    $errorCode = $_FILES['image']['error'] ?? 'no file';
    $errorMsg = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'Erreur inconnue';

    $response['error'] = 'Erreur upload: ' . $errorMsg;
    $response['error_code'] = $errorCode;

    logError('Erreur upload fichier', [
        'error_code' => $errorCode,
        'error_msg' => $errorMsg,
        'FILES' => $_FILES
    ]);
    echo json_encode($response);
    exit;
}

// Récupérer les informations du fichier
$file = $_FILES['image'];
$titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';

// Vérifier le type de fichier avec MIME type ET magic bytes
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    $response['error'] = 'Type de fichier non autorisé. Formats acceptés : JPG, PNG, GIF, WEBP';
    logError('Type de fichier non autorisé', ['mime' => $mimeType]);
    echo json_encode($response);
    exit;
}

// Vérification supplémentaire: vérifier les magic bytes (signatures de fichier)
$handle = fopen($file['tmp_name'], 'rb');
$magicBytes = fread($handle, 12);
fclose($handle);

$validSignature = false;
// JPEG: FF D8 FF
if (substr($magicBytes, 0, 3) === "\xFF\xD8\xFF") {
    $validSignature = true;
}
// PNG: 89 50 4E 47 0D 0A 1A 0A
elseif (substr($magicBytes, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
    $validSignature = true;
}
// GIF: 47 49 46 38
elseif (substr($magicBytes, 0, 4) === "GIF8") {
    $validSignature = true;
}
// WEBP: 52 49 46 46 ... 57 45 42 50
elseif (substr($magicBytes, 0, 4) === "RIFF" && substr($magicBytes, 8, 4) === "WEBP") {
    $validSignature = true;
}

if (!$validSignature) {
    $response['error'] = 'Fichier corrompu ou tentative de contournement détectée';
    logError('Magic bytes invalides', ['mime' => $mimeType, 'bytes' => bin2hex($magicBytes)]);
    echo json_encode($response);
    exit;
}

// Vérifier la taille du fichier (max 10MB)
$maxSize = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $maxSize) {
    $response['error'] = 'Fichier trop volumineux (max 10MB)';
    logError('Fichier trop volumineux', ['size' => $file['size']]);
    echo json_encode($response);
    exit;
}

// Générer un nom de fichier unique
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('img_', true) . '.' . $extension;

// Configuration FTP depuis les variables d'environnement
$ftpHost = getenv('FTP_HOST');
$ftpUser = getenv('FTP_USER');
$ftpPass = getenv('FTP_PASS');
$ftpPath = getenv('FTP_PATH') ?: '/images/';
$publicUrl = getenv('FTP_PUBLIC_URL') ?: '';

if (empty($ftpHost) || empty($ftpUser) || empty($ftpPass)) {
    $response['error'] = 'Configuration FTP manquante';
    logError('Configuration FTP manquante');
    echo json_encode($response);
    exit;
}

// Connexion FTP
$conn = ftp_connect($ftpHost);
if (!$conn) {
    $response['error'] = 'Impossible de se connecter au serveur FTP';
    logError('Connexion FTP échouée', ['host' => $ftpHost]);
    echo json_encode($response);
    exit;
}

// Authentification FTP
$login = @ftp_login($conn, $ftpUser, $ftpPass);
if (!$login) {
    $response['error'] = 'Authentification FTP échouée';
    logError('Authentification FTP échouée', ['user' => $ftpUser]);
    ftp_close($conn);
    echo json_encode($response);
    exit;
}

// Activer le mode passif (recommandé)
ftp_pasv($conn, true);

// Upload du fichier
$remoteFile = rtrim($ftpPath, '/') . '/' . $filename;
$upload = ftp_put($conn, $remoteFile, $file['tmp_name'], FTP_BINARY);

if (!$upload) {
    $response['error'] = 'Échec de l\'upload sur le serveur FTP';
    logError('Upload FTP échoué', ['remote' => $remoteFile]);
    ftp_close($conn);
    echo json_encode($response);
    exit;
}

// Fermer la connexion FTP
ftp_close($conn);

// Construire l'URL publique de l'image
$imageUrl = rtrim($publicUrl, '/') . '/' . $filename;

// Ajouter l'image au JSON directement (sans passer par HTTP)
// Charger les fonctions de images.php
$imagesFile = config('images_file');

// Fonction locale pour lire les images
function getImagesLocal() {
    if (config('use_gist')) {
        $images = getImagesFromGist();
        if ($images !== null) {
            return $images;
        }
        logError('Fallback sur le fichier local après échec Gist images');
    }

    $imagesFile = config('images_file');
    $content = @file_get_contents($imagesFile);
    if ($content === false) {
        return [];
    }
    $images = json_decode($content, true);
    return is_array($images) ? $images : [];
}

// Fonction locale pour sauvegarder les images
function saveImagesLocal($images) {
    if (config('use_gist')) {
        $success = saveImagesToGist($images);
        if ($success) {
            return true;
        }
        logError('Fallback sur le fichier local après échec écriture Gist images');
    }

    $imagesFile = config('images_file');
    return file_put_contents($imagesFile, json_encode($images, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Ajouter l'image
$images = getImagesLocal();

$newImage = [
    'id' => uniqid(),
    'url' => $imageUrl,
    'titre' => $titre,
    'date_ajout' => date('Y-m-d H:i:s')
];

$images[] = $newImage;

if (saveImagesLocal($images)) {
    $response['success'] = true;
    $response['message'] = 'Image uploadée et ajoutée avec succès';
    $response['url'] = $imageUrl;
    $response['image'] = $newImage;

    if (APP_DEBUG) {
        logError('Image uploadée avec succès', ['url' => $imageUrl, 'filename' => $filename]);
    }
} else {
    $response['error'] = 'Image uploadée mais erreur lors de l\'ajout au JSON';
    logError('Erreur sauvegarde image dans JSON');
}

echo json_encode($response);
?>
