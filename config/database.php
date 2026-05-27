<?php
/**
 * Configuration de la connexion à la base de données
 */

// Paramètres de connexion
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_personnel');
define('DB_USER', 'root');
define('DB_PASS', '');

// Options PDO
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Générer des exceptions en cas d'erreur
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retourner les résultats sous forme de tableau associatif
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Désactiver l'émulation des requêtes préparées (sécurité)
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"     // Forcer l'encodage utf8mb4
];

try {
    // Variable globale (ou à utiliser via la classe Database)
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        $pdoOptions
    );
} catch (PDOException $e) {
    // Log l'erreur (dans un fichier de log en production)
    error_log("Erreur de connexion BDD : " . $e->getMessage());
    // Afficher une erreur propre à l'utilisateur
    die("Une erreur de connexion à la base de données est survenue. Veuillez contacter l'administrateur.");
}
