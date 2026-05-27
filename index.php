<?php
// On récupère le chemin absolu vers config
require_once __DIR__ . '/config/constants.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/functions.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Logger.php';
require_once ROOT_PATH . '/classes/Auth.php';

// Redirection basée sur l'état de la session
if (isset($_SESSION['user_id'])) {
    redirect(BASE_URL . '/pages/dashboard/index.php');
} else {
    redirect(BASE_URL . '/pages/auth/login.php');
}
