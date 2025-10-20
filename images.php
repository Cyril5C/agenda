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

// Créer le fichier s'il n'existe pas
if (!file_exists($imagesFile)) {
    file_put_contents($imagesFile, '[]');
}

// Lire les images
function getImages() {
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
