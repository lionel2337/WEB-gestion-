<?php
/**
 * Constantes globales de l'application
 */

// Détection dynamique du chemin racine (utile pour les inclusions)
define('ROOT_PATH', dirname(__DIR__));

// URL de base de l'application (à adapter si nécessaire)
define('BASE_URL', 'http://localhost/gestion_personnel');

// Chemins des dossiers principaux
define('ASSETS_PATH', BASE_URL . '/assets');
define('CSS_PATH', ASSETS_PATH . '/css');
define('JS_PATH', ASSETS_PATH . '/js');
define('IMG_PATH', ASSETS_PATH . '/images');
define('UPLOADS_PATH', ASSETS_PATH . '/uploads');

// Noms de l'application
define('APP_NAME', 'GLOBAL ENTERPRISE CORP');
define('APP_VERSION', '1.0.0');

// Rôles utilisateurs
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_RH', 'rh');
define('ROLE_MANAGER', 'manager');
define('ROLE_COMPTABLE', 'comptable');
define('ROLE_EMPLOYE', 'employe');

// Paramètres par défaut de l'entreprise
define('DEFAULT_CURRENCY', 'XAF');
define('DEFAULT_TIMEZONE', 'Africa/Douala');

// Limites d'upload
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
