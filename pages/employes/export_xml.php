<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/classes/Auth.php';
require_once ROOT_PATH . '/classes/XMLExporter.php';
require_once ROOT_PATH . '/classes/Logger.php';

require_login();

// Seuls les RH et Admins peuvent exporter
if (!Auth::hasRole('rh') && !Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    http_response_code(403);
    echo "Accès refusé.";
    exit;
}

$exporter = new XMLExporter();
$xml_data = $exporter->exporterEmployes();

if ($xml_data) {
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="employes_export_' . date('Ymd_His') . '.xml"');
    echo $xml_data;
    exit;
} else {
    echo "Une erreur est survenue lors de la génération de l'export XML.";
}
