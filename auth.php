<?php
session_start();

// Charger la configuration
require_once __DIR__ . '/config.php';

// Récupérer le hash du mot de passe depuis l'environnement
$adminPasswordHash = getenv('ADMIN_PASSWORD_HASH');

// Si pas de mot de passe configuré, utiliser un mot de passe par défaut (dev uniquement)
if (empty($adminPasswordHash)) {
    // Hash SHA256 de "admin123" pour le développement
    $adminPasswordHash = hash('sha256', 'admin123');
}

// Fonction pour vérifier si l'utilisateur est authentifié
function isAuthenticated() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

// Fonction pour vérifier le mot de passe
function checkPassword($password) {
    global $adminPasswordHash;
    return hash('sha256', $password) === $adminPasswordHash;
}

// Route de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    header('Content-Type: application/json');

    $password = $_POST['password'] ?? '';

    if (checkPassword($password)) {
        $_SESSION['admin_authenticated'] = true;
        echo json_encode(['success' => true]);
    } else {
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
