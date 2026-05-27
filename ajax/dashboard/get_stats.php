<?php
require_once dirname(__DIR__, 2) . '/config/constants.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/classes/Database.php';
require_once ROOT_PATH . '/config/functions.php';

// Sécurité : Vérifier si connecté et si c'est une requête GET (ou POST selon le besoin)
if (!isset($_SESSION['user_id'])) {
    json_response(false, "Non autorisé", null, 401);
}

try {
    $db = Database::getInstance()->getConnection();
    
    // --- 1. Statistiques globales ---
    
    // Total employés actifs
    $stmt = $db->query("SELECT COUNT(*) FROM employes WHERE est_supprime = 0 AND statut_employe != 'archive'");
    $total_employes = $stmt->fetchColumn() ?: 0;
    
    // Total en congé
    $stmt = $db->query("SELECT COUNT(*) FROM employes WHERE est_supprime = 0 AND statut_employe = 'en_conge'");
    $en_conge = $stmt->fetchColumn() ?: 0;
    
    // Total en mission
    $stmt = $db->query("SELECT COUNT(*) FROM employes WHERE est_supprime = 0 AND statut_employe = 'en_mission'");
    $en_mission = $stmt->fetchColumn() ?: 0;
    
    // Congés en attente d'approbation (statut 'soumis' ou 'en_attente')
    // Pour simplifier selon le schéma on compte les statuts spécifiques
    $stmt = $db->query("SELECT COUNT(*) FROM conges WHERE est_supprime = 0 AND statut IN ('soumis', 'approuve_manager', 'approuve_rh', 'en_cours_approbation')");
    $conges_attente = $stmt->fetchColumn() ?: 0;
    
    // --- 2. Données pour les graphiques ---
    
    // Répartition par département
    $dept_labels = [];
    $dept_values = [];
    $stmt = $db->query("
        SELECT d.nom_departement, COUNT(e.id_employe) as nb
        FROM departements d
        LEFT JOIN employes e ON d.id_departement = e.id_departement AND e.est_supprime = 0 AND e.statut_employe != 'archive'
        WHERE d.est_supprime = 0
        GROUP BY d.id_departement
        ORDER BY nb DESC
        LIMIT 7
    ");
    while ($row = $stmt->fetch()) {
        $dept_labels[] = $row['nom_departement'];
        $dept_values[] = (int)$row['nb'];
    }
    
    // Répartition Genre
    $stmt = $db->query("SELECT sexe, COUNT(*) as nb FROM employes WHERE est_supprime = 0 AND statut_employe != 'archive' GROUP BY sexe");
    $hommes = 0;
    $femmes = 0;
    while ($row = $stmt->fetch()) {
        if ($row['sexe'] == 'M') $hommes = (int)$row['nb'];
        if ($row['sexe'] == 'F') $femmes = (int)$row['nb'];
    }
    
    // Structure de réponse
    $response_data = [
        'total_employes' => (int)$total_employes,
        'en_conge' => (int)$en_conge,
        'en_mission' => (int)$en_mission,
        'conges_attente' => (int)$conges_attente,
        'graphiques' => [
            'departements' => [
                'labels' => $dept_labels,
                'values' => $dept_values
            ],
            'genre' => [
                'hommes' => $hommes,
                'femmes' => $femmes
            ]
        ]
    ];
    
    json_response(true, "Statistiques récupérées", $response_data);
    
} catch (PDOException $e) {
    error_log("Erreur AJAX get_stats : " . $e->getMessage());
    json_response(false, "Erreur lors du calcul des statistiques", null, 500);
}
