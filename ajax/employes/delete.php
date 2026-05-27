<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Logger.php';
require_once ROOT_PATH . '/classes/Employe.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, "Méthode non autorisée", null, 405);
}

if (!isset($_SESSION['user_id']) || !Auth::hasRole('manager')) {
    json_response(false, "Accès refusé", null, 403);
}

// Vérification CSRF
$data = json_decode(file_get_contents('php://input'), true);
if (!verify_csrf_token($data['csrf_token'] ?? '')) {
    json_response(false, "Token de sécurité invalide", null, 403);
}

$id_employe = $data['id_employe'] ?? null;
$motif_depart = clean_input($data['motif_depart'] ?? '');
$commentaire = clean_input($data['commentaire'] ?? '');

if (empty($id_employe) || empty($motif_depart)) {
    json_response(false, "Veuillez fournir l'employé et le motif de départ.");
}

// Ne pas s'archiver soi-même
if ($id_employe == $_SESSION['employe_id']) {
    json_response(false, "Vous ne pouvez pas archiver votre propre compte.");
}

$employeClass = new Employe();
$success = $employeClass->softDelete($id_employe, $motif_depart, $commentaire, $_SESSION['user_id']);

if ($success) {
    json_response(true, "Employé archivé avec succès.");
} else {
    json_response(false, "Erreur lors de l'archivage de l'employé.");
}
