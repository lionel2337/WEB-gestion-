<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Poste.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/config/functions.php';

require_login();
if (!Auth::hasRole('manager') && !Auth::hasRole('admin')) {
    json_response(false, 'Accès refusé', null, 403);
    exit;
}

// Decode JSON payload or fallback to $_POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

$id = $data['id'] ?? null;
if (empty($id)) {
    json_response(false, 'Identifiant du poste manquant');
    exit;
}

$required = ['titre_poste'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        json_response(false, "Le champ $field est requis");
        exit;
    }
}

$posteClass = new Poste();
$result = $posteClass->update($id, [
    'titre_poste' => $data['titre_poste'],
    'niveau_hierarchique' => $data['niveau_hierarchique'] ?? 5,
    'salaire_base_min' => $data['salaire_base_min'] ?? null,
    'salaire_base_max' => $data['salaire_base_max'] ?? null,
    'description' => $data['description'] ?? null
]);

json_response($result['success'], $result['success'] ? 'Poste mis à jour' : ($result['message'] ?? 'Erreur lors de la mise à jour'), $result);
?>
