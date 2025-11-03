<?php
session_start();

// Charger la configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

// Récupérer le hash du mot de passe depuis l'environnement
$adminPasswordHash = getenv('ADMIN_PASSWORD_HASH');

// Si pas de mot de passe configuré, utiliser un mot de passe par défaut (dev uniquement)
if (empty($adminPasswordHash)) {
    // Hash password_hash de "admin123" pour le développement
    // Pour générer: password_hash('admin123', PASSWORD_ARGON2ID)
    // En dev, on accepte aussi SHA256 pour compatibilité
    $adminPasswordHash = '$argon2id$v=19$m=65536,t=4,p=1$' . base64_encode(random_bytes(16));
    // Fallback dev
    $devMode = true;
} else {
    $devMode = false;
}

// Fonction pour vérifier si l'utilisateur est authentifié
function isAuthenticated() {
    // Vérifier que la session est valide
    if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
        return false;
    }

    // Vérifier le timeout de session (30 minutes d'inactivité)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        return false;
    }

    // Mettre à jour le timestamp d'activité
    $_SESSION['last_activity'] = time();

    return true;
}

// Fonction pour vérifier le mot de passe
function checkPassword($password) {
    global $adminPasswordHash, $devMode;

    // En production, utiliser password_verify
    if (!$devMode && strpos($adminPasswordHash, '$argon2id$') === 0) {
        return password_verify($password, $adminPasswordHash);
    }

    // Fallback dev: SHA256 simple
    return hash('sha256', $password) === $adminPasswordHash || $password === 'admin123';
}

// Route de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    header('Content-Type: application/json');

    $password = $_POST['password'] ?? '';

    // Rate limiting: max 5 tentatives par minute
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    // Nettoyer les tentatives de plus d'une minute
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($timestamp) {
        return $timestamp > (time() - 60);
    });

    // Vérifier le nombre de tentatives
    if (count($_SESSION['login_attempts']) >= 5) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Trop de tentatives. Réessayez dans 1 minute.']);
        exit;
    }

    if (checkPassword($password)) {
        // IMPORTANT: Régénérer l'ID de session pour éviter session fixation
        session_regenerate_id(true);

        $_SESSION['admin_authenticated'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['login_attempts'] = []; // Reset

        // Générer un token CSRF
        generateCsrfToken();

        echo json_encode([
            'success' => true,
            'csrf_token' => getCsrfToken()
        ]);
    } else {
        // Enregistrer la tentative échouée
        $_SESSION['login_attempts'][] = time();

        echo json_encode(['success' => false, 'error' => 'Mot de passe incorrect']);
    }
    exit;
}

// Route de logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    header('Content-Type: application/json');
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// Route de vérification du statut
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    header('Content-Type: application/json');
    echo json_encode(['authenticated' => isAuthenticated()]);
    exit;
}

// Protéger l'accès à admin.html
if (!isAuthenticated() && basename($_SERVER['PHP_SELF']) === 'admin.html') {
    header('Location: login.html');
    exit;
}
