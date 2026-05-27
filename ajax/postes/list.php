<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Poste.php';
require_once ROOT_PATH . '/classes/Logger.php';
require_once ROOT_PATH . '/classes/Auth.php'; // ensure Auth is loaded
require_once ROOT_PATH . '/config/functions.php';

require_login();
if (!Auth::hasRole('manager')) {
    json_response(false, 'Accès refusé', null, 403);
    exit;
}

$deptId = $_GET['id_departement'] ?? null;
if (!$deptId) {
    json_response(false, 'Identifiant du département manquant');
    exit;
}

$posteClass = new Poste();
$posts = $posteClass->getByDepartment($deptId);

json_response(true, 'Postes récupérés', $posts);
?>
