<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Departement.php';
require_once ROOT_PATH . '/classes/Logger.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();
if (!Auth::hasRole('admin')) {
    json_response(false, 'Accès refusé', null, 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Méthode non autorisée');
}

$id = intval($_POST['id'] ?? 0);
$nom = clean_input($_POST['nom_departement'] ?? '');
$desc = clean_input($_POST['description'] ?? '');

if ($id <= 0) {
    json_response(false, 'Identifiant invalide');
}

if (empty($nom)) {
    json_response(false, 'Le nom du département est requis');
}

$deptClass = new Departement();
$result = $deptClass->update($id, [
    'nom_departement' => $nom,
    'description' => $desc,
    'id_responsable' => null
]);

if ($result['success']) {
    json_response(true, 'Département mis à jour');
} else {
    json_response(false, $result['message'] ?? 'Erreur lors de la mise à jour');
}
?>
