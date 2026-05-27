<?php
/**
 * Fonctions utilitaires globales
 */

/**
 * Nettoie une chaîne pour l'affichage HTML (protection XSS)
 */
function escape($string) {
    if (is_null($string)) return '';
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Nettoie une entrée provenant d'un formulaire
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = escape($data);
    return $data;
}

/**
 * Redirection HTTP propre
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Ajoute un message flash dans la session
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Récupère et vide les messages flash
 */
function get_flash_messages() {
    if (isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return [];
}

/**
 * Affiche les messages flash sous forme d'alertes HTML
 */
function display_flash_messages() {
    $messages = get_flash_messages();
    $html = '';
    foreach ($messages as $msg) {
        $icon = 'info-circle';
        if ($msg['type'] == 'success') $icon = 'check-circle';
        if ($msg['type'] == 'danger') $icon = 'exclamation-circle';
        if ($msg['type'] == 'warning') $icon = 'exclamation-triangle';

        $html .= '<div class="alert alert-' . escape($msg['type']) . ' alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">';
        $html .= '<i class="fas fa-' . $icon . ' me-2"></i>' . escape($msg['message']);
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $html .= '</div>';
    }
    return $html;
}

/**
 * Vérifie si l'utilisateur est connecté, sinon redirige
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        redirect(BASE_URL . '/pages/auth/login.php');
    }
}

/**
 * Répond au format JSON (pour les appels AJAX)
 */
function json_response($success, $message = '', $data = null, $http_code = 200) {
    http_response_code($http_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Formate une date en français
 */
function format_date_fr($date_string, $format = 'd/m/Y') {
    if (empty($date_string)) return '-';
    $date = new DateTime($date_string);
    return $date->format($format);
}

/**
 * Formate un montant en monnaie locale
 */
function format_money($amount) {
    if (!is_numeric($amount)) return '0 ' . DEFAULT_CURRENCY;
    return number_format($amount, 0, ',', ' ') . ' ' . DEFAULT_CURRENCY;
}

/**
 * Gère l'upload de fichiers (photos d'employés, PDF de contrats).
 * Retourne un tableau avec 'path' ou 'error'.
 */
function handle_file_upload($fieldName, $targetDir, $allowedTypes = ['image/jpeg','image/png','application/pdf'], $maxSize = 5242880) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Aucun fichier téléchargé ou erreur d\'upload.'];
    }
    $file = $_FILES[$fieldName];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => 'Type de fichier non autorisé.'];
    }
    if ($file['size'] > $maxSize) {
        return ['error' => 'Fichier trop volumineux.'];
    }
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid('upload_', true) . '.' . $ext;
    $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'Échec du déplacement du fichier.'];
    }
    return ['path' => $dest];
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Vérifie le token CSRF envoyé dans les formulaires/AJAX.
     * Retourne true si le token correspond à celui stocké en session.
     */
    function verify_csrf_token($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}


/**
 * Envoie un email de notification.
 * Utilise la fonction native mail() – configurez votre serveur SMTP.
 */
function send_email_notification($to, $subject, $body) {
    $headers = "From: no-reply@yourdomain.com\r\n" .
               "Reply-To: no-reply@yourdomain.com\r\n" .
               "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $body, $headers);
}

/**
 * Génère le token CSRF et le stocke en session.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
