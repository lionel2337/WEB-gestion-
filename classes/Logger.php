<?php
/**
 * Classe Logger
 * Gère l'enregistrement dans logs_activite
 */
class Logger {
    
    /**
     * Enregistre une action dans le journal
     * 
     * @param string $action Ex: "Connexion", "Création employé"
     * @param string $categorie_action Ex: "authentification", "creation"
     * @param string $table_concernee Nom de la table impactée
     * @param int|null $id_enregistrement ID de l'enregistrement impacté
     * @param string $description Description détaillée
     * @param string|null $ancienne_valeur Valeur avant modification (JSON)
     * @param string|null $nouvelle_valeur Valeur après modification (JSON)
     * @param string $resultat "succes", "echec" ou "partiel"
     * @param string|null $message_erreur Message d'erreur éventuel
     */
    public static function log($action, $categorie_action, $table_concernee = null, $id_enregistrement = null, $description = '', $ancienne_valeur = null, $nouvelle_valeur = null, $resultat = 'succes', $message_erreur = null) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $id_utilisateur = $_SESSION['user_id'] ?? null;
            $nom_utilisateur = $_SESSION['username'] ?? 'Système';
            
            $sql = "INSERT INTO logs_activite (
                id_utilisateur, nom_utilisateur, action, categorie_action, 
                table_concernee, id_enregistrement, description, 
                ancienne_valeur, nouvelle_valeur, 
                adresse_ip, navigateur, url_page, 
                resultat, message_erreur, date_action
            ) VALUES (
                :id_utilisateur, :nom_utilisateur, :action, :categorie_action, 
                :table_concernee, :id_enregistrement, :description, 
                :ancienne_valeur, :nouvelle_valeur, 
                :adresse_ip, :navigateur, :url_page, 
                :resultat, :message_erreur, NOW()
            )";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id_utilisateur' => $id_utilisateur,
                ':nom_utilisateur' => $nom_utilisateur,
                ':action' => $action,
                ':categorie_action' => $categorie_action,
                ':table_concernee' => $table_concernee,
                ':id_enregistrement' => $id_enregistrement,
                ':description' => $description,
                ':ancienne_valeur' => $ancienne_valeur,
                ':nouvelle_valeur' => $nouvelle_valeur,
                ':adresse_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':navigateur' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ':url_page' => substr($_SERVER['REQUEST_URI'] ?? '', 0, 255),
                ':resultat' => $resultat,
                ':message_erreur' => $message_erreur
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur Logger : " . $e->getMessage());
            return false;
        }
    }
}
