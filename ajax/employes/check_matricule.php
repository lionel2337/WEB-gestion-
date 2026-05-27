<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Employe.php';
require_once ROOT_PATH . '/config/functions.php';

if (!isset($_SESSION['user_id'])) {
    json_response(false, "Non autorisé", null, 401);
}

$matricule = clean_input($_GET['matricule'] ?? '');
$exclude_id = isset($_GET['exclude']) ? (int)$_GET['exclude'] : null;

if (empty($matricule)) {
    json_response(false, "Matricule vide.");
}

$employeClass = new Employe();
$existe = $employeClass->matriculeExiste($matricule, $exclude_id);

json_response(true, "Succès", ['existe' => $existe]);
