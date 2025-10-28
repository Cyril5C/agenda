<?php
// Charger la configuration
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . config('cors_origin'));
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$imagesFile = config('images_file');
$response = ['success' => false];

// Créer le fichier s'il n'existe pas (mode dev uniquement)
if (!config('use_gist') && !file_exists($imagesFile)) {
    file_put_contents($imagesFile, '[]');
}

// Fonctions Gist pour images
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

// Lire les images
function getImages() {
    if (config('use_gist')) {
        $images = getImagesFromGist();
        if ($images !== null) {
            return $images;
        }
        logError('Fallback sur le fichier local après échec Gist images');
    }

    global $imagesFile;
    $content = @file_get_contents($imagesFile);
    if ($content === false) {
        return [];
    }
    $images = json_decode($content, true);
    return is_array($images) ? $images : [];
}

// Sauvegarder les images
function saveImages($images) {
    if (config('use_gist')) {
        $success = saveImagesToGist($images);
        if ($success) {
            return true;
        }
        logError('Fallback sur le fichier local après échec écriture Gist images');
    }

    global $imagesFile;
    return file_put_contents($imagesFile, json_encode($images, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// GET - Lister toutes les images
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $images = getImages();
    $response['success'] = true;
    $response['images'] = $images;
}

// POST - Ajouter une image
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $url = $input['url'] ?? '';
    $titre = $input['titre'] ?? '';

    if (empty($url)) {
        $response['error'] = 'URL manquante';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $response['error'] = 'URL invalide';
    } else {
        $images = getImages();

        $newImage = [
            'id' => uniqid(),
            'url' => $url,
            'titre' => $titre,
            'date_ajout' => date('Y-m-d H:i:s')
        ];

        $images[] = $newImage;

        if (saveImages($images)) {
            $response['success'] = true;
            $response['image'] = $newImage;
            $response['message'] = 'Image ajoutée avec succès';

            if (APP_DEBUG) {
                logError('Image ajoutée', ['url' => $url]);
            }
        } else {
            $response['error'] = 'Erreur lors de la sauvegarde';
            logError('Erreur sauvegarde image', ['url' => $url]);
        }
    }
}

// DELETE - Supprimer une image
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? '';

    if (empty($id)) {
        $response['error'] = 'ID manquant';
    } else {
        $images = getImages();
        $newImages = array_filter($images, function($img) use ($id) {
            return $img['id'] !== $id;
        });

        // Réindexer le tableau
        $newImages = array_values($newImages);

        if (count($newImages) < count($images)) {
            if (saveImages($newImages)) {
                $response['success'] = true;
                $response['message'] = 'Image supprimée avec succès';

                if (APP_DEBUG) {
                    logError('Image supprimée', ['id' => $id]);
                }
            } else {
                $response['error'] = 'Erreur lors de la sauvegarde';
                logError('Erreur suppression image', ['id' => $id]);
            }
        } else {
            $response['error'] = 'Image non trouvée';
        }
    }
}

else {
    $response['error'] = 'Méthode non autorisée';
}

echo json_encode($response);
?>
