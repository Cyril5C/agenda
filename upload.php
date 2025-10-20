<?php
// Charger la configuration
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));

$uploadDir = config('upload_dir');
$response = ['success' => false];

// Upload d'image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];

    // Vérifier les erreurs
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['error'] = 'Erreur lors de l\'upload';
        echo json_encode($response);
        logError('Erreur upload', ['error' => $file['error'], 'filename' => $file['name']]);
        exit;
    }

    // Vérifier le type de fichier
    $allowedTypes = config('allowed_image_types');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        $response['error'] = 'Type de fichier non autorisé. Seulement JPG, PNG, GIF et WEBP.';
        echo json_encode($response);
        logError('Type de fichier non autorisé', ['mime' => $mimeType, 'filename' => $file['name']]);
        exit;
    }

    // Vérifier la taille
    $maxSize = config('upload_max_size');
    if ($file['size'] > $maxSize) {
        $response['error'] = 'Fichier trop volumineux (max ' . round($maxSize / 1024 / 1024) . 'MB)';
        echo json_encode($response);
        logError('Fichier trop volumineux', ['size' => $file['size'], 'filename' => $file['name']]);
        exit;
    }

    // Générer un nom de fichier sécurisé
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $file['name'];
    $targetPath = $uploadDir . $filename;

    // Si le fichier existe déjà, ajouter un suffixe
    $counter = 1;
    while (file_exists($targetPath)) {
        $filename = pathinfo($file['name'], PATHINFO_FILENAME) . '_' . $counter . '.' . $extension;
        $targetPath = $uploadDir . $filename;
        $counter++;
    }

    // Déplacer le fichier uploadé
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $response['success'] = true;
        $response['filename'] = $filename;
        $response['message'] = 'Image uploadée avec succès';
        if (APP_DEBUG) {
            logError('Image uploadée', ['filename' => $filename]);
        }
    } else {
        $response['error'] = 'Erreur lors de l\'enregistrement du fichier';
        logError('Erreur enregistrement fichier', ['filename' => $filename, 'path' => $targetPath]);
    }
}

// Suppression d'image
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $filename = $input['filename'] ?? '';

    if (empty($filename)) {
        $response['error'] = 'Nom de fichier manquant';
        echo json_encode($response);
        exit;
    }

    // Sécurité : vérifier que le fichier est bien dans le dossier images
    $targetPath = $uploadDir . basename($filename);

    if (!file_exists($targetPath)) {
        $response['error'] = 'Fichier non trouvé';
        echo json_encode($response);
        exit;
    }

    if (unlink($targetPath)) {
        $response['success'] = true;
        $response['message'] = 'Image supprimée avec succès';
        if (APP_DEBUG) {
            logError('Image supprimée', ['filename' => $filename]);
        }
    } else {
        $response['error'] = 'Erreur lors de la suppression';
        logError('Erreur suppression fichier', ['filename' => $filename, 'path' => $targetPath]);
    }
}

else {
    $response['error'] = 'Méthode non autorisée';
}

echo json_encode($response);
?>
