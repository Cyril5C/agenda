<?php
// Charger la configuration
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));

$uploadDir = config('upload_dir');
$response = ['success' => false];

/**
 * Upload une image sur Cloudinary
 */
function uploadToCloudinary($filePath, $filename) {
    $cloudinary = config('cloudinary');

    if (!$cloudinary['cloud_name'] || !$cloudinary['api_key'] || !$cloudinary['api_secret']) {
        logError('Cloudinary non configuré');
        return false;
    }

    // Générer la signature
    $timestamp = time();
    $public_id = pathinfo($filename, PATHINFO_FILENAME) . '_' . $timestamp;
    $folder = $cloudinary['folder'];

    // Paramètres de l'upload
    $params = [
        'timestamp' => $timestamp,
        'folder' => $folder,
        'public_id' => $public_id,
    ];

    // Signature = hash des paramètres + api_secret
    $signature = '';
    ksort($params);
    foreach ($params as $key => $value) {
        $signature .= $key . '=' . $value . '&';
    }
    $signature = rtrim($signature, '&');
    $signature = sha1($signature . $cloudinary['api_secret']);

    // Préparer les données pour l'upload
    $postData = [
        'file' => new CURLFile($filePath),
        'timestamp' => $timestamp,
        'folder' => $folder,
        'public_id' => $public_id,
        'api_key' => $cloudinary['api_key'],
        'signature' => $signature,
    ];

    // Envoyer à Cloudinary
    $url = "https://api.cloudinary.com/v1_1/{$cloudinary['cloud_name']}/image/upload";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        logError('Erreur upload Cloudinary', [
            'http_code' => $httpCode,
            'error' => $error,
            'result' => $result
        ]);
        return false;
    }

    $resultData = json_decode($result, true);

    if (isset($resultData['secure_url'])) {
        return [
            'url' => $resultData['secure_url'],
            'public_id' => $resultData['public_id'],
            'format' => $resultData['format'],
        ];
    }

    return false;
}

/**
 * Supprime une image de Cloudinary
 */
function deleteFromCloudinary($publicId) {
    $cloudinary = config('cloudinary');

    if (!$cloudinary['cloud_name'] || !$cloudinary['api_key'] || !$cloudinary['api_secret']) {
        return false;
    }

    $timestamp = time();

    // Signature pour la suppression
    $signature = sha1("public_id={$publicId}&timestamp={$timestamp}" . $cloudinary['api_secret']);

    $postData = [
        'public_id' => $publicId,
        'timestamp' => $timestamp,
        'api_key' => $cloudinary['api_key'],
        'signature' => $signature,
    ];

    $url = "https://api.cloudinary.com/v1_1/{$cloudinary['cloud_name']}/image/destroy";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    curl_close($ch);

    return true;
}

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

    // Si Cloudinary est activé, uploader vers Cloudinary
    if (config('use_cloudinary')) {
        $cloudinaryResult = uploadToCloudinary($file['tmp_name'], $filename);

        if ($cloudinaryResult) {
            $response['success'] = true;
            $response['filename'] = $filename;
            $response['url'] = $cloudinaryResult['url'];
            $response['cloudinary'] = true;
            $response['message'] = 'Image uploadée sur Cloudinary avec succès';

            if (APP_DEBUG) {
                logError('Image uploadée sur Cloudinary', [
                    'filename' => $filename,
                    'url' => $cloudinaryResult['url']
                ]);
            }
        } else {
            $response['error'] = 'Erreur lors de l\'upload sur Cloudinary';
            logError('Échec upload Cloudinary', ['filename' => $filename]);
        }
    }
    // Sinon, uploader en local (mode dev)
    else {
        $targetPath = $uploadDir . $filename;

        // Si le fichier existe déjà, ajouter un suffixe
        $counter = 1;
        while (file_exists($targetPath)) {
            $filename = pathinfo($file['name'], PATHINFO_FILENAME) . '_' . $counter . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            $counter++;
        }

        // Créer le dossier s'il n'existe pas
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        // Déplacer le fichier uploadé
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $response['success'] = true;
            $response['filename'] = $filename;
            $response['cloudinary'] = false;
            $response['message'] = 'Image uploadée localement avec succès';

            if (APP_DEBUG) {
                logError('Image uploadée localement', ['filename' => $filename]);
            }
        } else {
            $response['error'] = 'Erreur lors de l\'enregistrement du fichier';
            logError('Erreur enregistrement fichier', ['filename' => $filename, 'path' => $targetPath]);
        }
    }
}

// Suppression d'image
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $filename = $input['filename'] ?? '';
    $cloudinaryPublicId = $input['cloudinary_public_id'] ?? '';

    if (empty($filename)) {
        $response['error'] = 'Nom de fichier manquant';
        echo json_encode($response);
        exit;
    }

    // Si l'image est sur Cloudinary
    if (!empty($cloudinaryPublicId) && config('use_cloudinary')) {
        if (deleteFromCloudinary($cloudinaryPublicId)) {
            $response['success'] = true;
            $response['message'] = 'Image supprimée de Cloudinary avec succès';

            if (APP_DEBUG) {
                logError('Image supprimée de Cloudinary', ['public_id' => $cloudinaryPublicId]);
            }
        } else {
            $response['error'] = 'Erreur lors de la suppression de Cloudinary';
            logError('Erreur suppression Cloudinary', ['public_id' => $cloudinaryPublicId]);
        }
    }
    // Sinon, supprimer en local
    else {
        // Sécurité : vérifier que le fichier est bien dans le dossier images
        $targetPath = $uploadDir . basename($filename);

        if (!file_exists($targetPath)) {
            $response['error'] = 'Fichier non trouvé';
            echo json_encode($response);
            exit;
        }

        if (unlink($targetPath)) {
            $response['success'] = true;
            $response['message'] = 'Image supprimée localement avec succès';

            if (APP_DEBUG) {
                logError('Image supprimée localement', ['filename' => $filename]);
            }
        } else {
            $response['error'] = 'Erreur lors de la suppression';
            logError('Erreur suppression fichier', ['filename' => $filename, 'path' => $targetPath]);
        }
    }
}

else {
    $response['error'] = 'Méthode non autorisée';
}

echo json_encode($response);
?>
