<?php
/**
 * Gestion des tokens CSRF (Cross-Site Request Forgery)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Générer un token CSRF
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Obtenir le token CSRF actuel
 */
function getCsrfToken() {
    return $_SESSION['csrf_token'] ?? generateCsrfToken();
}

/**
 * Vérifier le token CSRF
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Vérifier le token CSRF depuis la requête et bloquer si invalide
 */
function requireCsrfToken() {
    // Méthodes qui nécessitent CSRF protection
    $protectedMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

    if (!in_array($_SERVER['REQUEST_METHOD'], $protectedMethods)) {
        return true;
    }

    // Récupérer le token depuis le header ou le body
    $token = null;

    // Priorité 1: Header HTTP
    if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    // Priorité 2: POST data
    elseif (isset($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    }
    // Priorité 3: JSON body
    else {
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['csrf_token'])) {
            $token = $input['csrf_token'];
        }
    }

    if (!$token || !verifyCsrfToken($token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Token CSRF invalide ou manquant'
        ]);
        exit;
    }

    return true;
}

/**
 * Endpoint pour obtenir un token CSRF (GET uniquement)
 */
if (basename($_SERVER['PHP_SELF']) === 'csrf.php' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'csrf_token' => getCsrfToken()
    ]);
    exit;
}
