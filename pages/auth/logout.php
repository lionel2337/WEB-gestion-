<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Logger.php';
require_once ROOT_PATH . '/classes/Auth.php';

// On appelle la fonction de déconnexion
Auth::logout();

// Redirection vers le login avec message flash
session_start(); // Redémarrer une session vide pour le flash message
require_once ROOT_PATH . '/config/functions.php';
set_flash_message('success', 'Vous avez été déconnecté avec succès.');

redirect(BASE_URL . '/pages/auth/login.php');
