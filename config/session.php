<?php
// Autochargement des classes de l'application
spl_autoload_register(function ($class_name) {
    $file = ROOT_PATH . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Paramètres de sécurité de session avant le démarrage
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

session_set_cookie_params([
    'lifetime' => 1800, // 30 minutes (timeout)
    'path' => '/',
    'domain' => '', 
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // True si HTTPS
    'httponly' => true, // Empêche l'accès au cookie via JavaScript (XSS)
    'samesite' => 'Lax' // Protection CSRF basique
]);

session_start();

// Vérification de la session hijacking (vol de session)
if (isset($_SESSION['last_ip']) && $_SESSION['last_ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR'];

if (isset($_SESSION['last_user_agent']) && $_SESSION['last_user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

// Régénération périodique de l'ID de session pour éviter la fixation de session
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} else {
    $interval = 60 * 15; // 15 minutes
    if (time() - $_SESSION['last_regeneration'] >= $interval) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Timeout de session d'inactivité (30 minutes)
$timeout_duration = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL . "/pages/auth/login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// Génération du token CSRF si non existant
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Vérifie la validité du token CSRF
     */
    function verify_csrf_token($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

