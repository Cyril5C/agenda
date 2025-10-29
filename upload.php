<?php
// Charger la configuration
require_once __DIR__ . '/config.php';

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

// Vérifier qu'un fichier a été uploadé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $response['error'] = 'Aucun fichier uploadé ou erreur lors de l\'upload';
    if (isset($_FILES['image']['error'])) {
        $response['error_code'] = $_FILES['image']['error'];
    }
    logError('Erreur upload fichier', ['error' => $_FILES['image']['error'] ?? 'no file']);
    echo json_encode($response);
    exit;
}

// Récupérer les informations du fichier
$file = $_FILES['image'];
$titre = isset($_POST['titre']) ? trim($_POST['titre']) : '';

// Vérifier le type de fichier
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

// Ajouter l'image au JSON via l'API images.php
$imageData = [
    'url' => $imageUrl,
    'titre' => $titre
];

// Appeler l'API interne pour ajouter l'image
$ch = curl_init('http://localhost/images.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($imageData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $apiResponse = json_decode($result, true);
    if ($apiResponse && $apiResponse['success']) {
        $response['success'] = true;
        $response['message'] = 'Image uploadée et ajoutée avec succès';
        $response['url'] = $imageUrl;
        $response['image'] = $apiResponse['image'];

        if (APP_DEBUG) {
            logError('Image uploadée avec succès', ['url' => $imageUrl, 'filename' => $filename]);
        }
    } else {
        $response['error'] = 'Image uploadée mais erreur lors de l\'ajout au JSON';
        logError('Erreur ajout image au JSON', ['api_response' => $apiResponse]);
    }
} else {
    $response['error'] = 'Image uploadée mais erreur lors de l\'appel API';
    logError('Erreur appel API images', ['http_code' => $httpCode]);
}

echo json_encode($response);
?>
