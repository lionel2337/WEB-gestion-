<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Departement.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();
// Manager or admin can delete (soft delete) a department
if (!Auth::hasRole('manager') && !Auth::hasRole('admin')) {
    json_response(false, 'Accès refusé', null, 403);
    exit;
}

// Accept JSON or fallback to POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

$id = $data['id'] ?? null;
if (empty($id)) {
    json_response(false, 'Identifiant du département manquant');
    exit;
}

$deptClass = new Departement();
$result = $deptClass->softDelete((int)$id);

json_response($result['success'], $result['success'] ? 'Département supprimé' : ($result['message'] ?? 'Erreur lors de la suppression'), $result);
?>
