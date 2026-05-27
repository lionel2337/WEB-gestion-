<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Departement.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();
if (!Auth::hasRole('manager') && !Auth::hasRole('admin')) {
    json_response(false, 'Accès refusé', null, 403);
}

// Accept both JSON payload and form data
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    // fallback to $_POST
    $data = $_POST;
}

$nom = clean_input($data['nom_departement'] ?? '');
$desc = clean_input($data['description'] ?? '');

if (empty($nom)) {
    json_response(false, 'Le nom du département est requis');
}

$deptClass = new Departement();
$result = $deptClass->create([
    'nom_departement' => $nom,
    'description' => $desc,
    'id_responsable' => $data['id_responsable'] ?? null
]);

json_response($result['success'], $result['success'] ? 'Département créé' : ($result['message'] ?? 'Erreur lors de la création'), $result);
?>
