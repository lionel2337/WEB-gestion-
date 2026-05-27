<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/config/functions.php';

if (!isset($_SESSION['user_id'])) {
    json_response(false, "Non autorisé", null, 401);
}

$id_departement = isset($_GET['id_departement']) ? (int)$_GET['id_departement'] : 0;

if ($id_departement <= 0) {
    json_response(false, "Département invalide.");
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id_poste, titre_poste, niveau_hierarchique FROM postes WHERE id_departement = :id AND est_supprime = 0 ORDER BY titre_poste ASC");
    $stmt->execute([':id' => $id_departement]);
    $postes = $stmt->fetchAll();
    
    json_response(true, "Succès", $postes);
} catch (PDOException $e) {
    error_log("Erreur AJAX get_postes : " . $e->getMessage());
    json_response(false, "Erreur serveur.", null, 500);
}
