<?php
/**
 * Classe Corbeille
 * Gestion des éléments supprimés temporairement, restauration et purges définitives.
 */
class Corbeille {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Ajoute un élément dans la corbeille
     */
    public function ajouter($table, $id_enregistrement, $donnees, $description, $supprime_par) {
        try {
            $stmt = $this->db->prepare("INSERT INTO corbeille (table_origine, id_enregistrement_original, donnees_sauvegardees, description, supprime_par, date_suppression, date_expiration_corbeille, est_restaure, est_purge) VALUES (:table, :id_orig, :data, :desc, :user, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 0, 0)");
            
            return $stmt->execute([
                ':table' => $table,
                ':id_orig' => $id_enregistrement,
                ':data' => json_encode($donnees, JSON_UNESCAPED_UNICODE),
                ':desc' => $description,
                ':user' => $supprime_par
            ]);
        } catch (PDOException $e) {
            error_log('Erreur ajout corbeille: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les éléments de la corbeille
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT c.*, u.nom_utilisateur FROM corbeille c LEFT JOIN utilisateurs u ON c.supprime_par = u.id_utilisateur WHERE c.est_restaure = 0 AND c.est_purge = 0 ORDER BY c.date_suppression DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un élément par son ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM corbeille WHERE id_corbeille = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Restaure un élément depuis la corbeille
     */
    public function restaurer($id_corbeille, $restaure_par) {
        try {
            $item = $this->getById($id_corbeille);
            if (!$item) return ['success' => false, 'message' => 'Élément introuvable'];

            $this->db->beginTransaction();

            $table = $item['table_origine'];
            $id_orig = $item['id_enregistrement_original'];

            // 1. Mettre à jour l'enregistrement d'origine pour enlever est_supprime
            // Nous supposons que le champ de suppression logique est est_supprime
            $id_field = 'id_' . rtrim($table, 's'); // ex: id_employe pour employes
            if ($table === 'employes') {
                $id_field = 'id_employe';
                // Restauration de l'employé
                $stmt_orig = $this->db->prepare("UPDATE employes SET est_supprime = 0, statut_employe = 'actif', peut_etre_restaure = 0 WHERE id_employe = :id");
                $stmt_orig->execute([':id' => $id_orig]);

                // Réactiver l'utilisateur lié si existant
                $stmt_u = $this->db->prepare("UPDATE utilisateurs SET statut = 'actif', est_supprime = 0 WHERE id_employe = :id");
                $stmt_u->execute([':id' => $id_orig]);
            } else {
                $stmt_orig = $this->db->prepare("UPDATE `$table` SET est_supprime = 0 WHERE `$id_field` = :id");
                $stmt_orig->execute([':id' => $id_orig]);
            }

            // 2. Mettre à jour le statut dans la corbeille
            $stmt_c = $this->db->prepare("UPDATE corbeille SET est_restaure = 1, date_restauration = NOW(), restaure_par = :user WHERE id_corbeille = :id");
            $stmt_c->execute([':user' => $restaure_par, ':id' => $id_corbeille]);

            Logger::log('Restauration depuis la corbeille', 'restauration', $table, $id_orig);

            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Erreur restauration corbeille: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()];
        }
    }

    /**
     * Purge définitivement un élément (Suppression SQL réelle)
     */
    public function purger($id_corbeille, $purge_par) {
        try {
            $item = $this->getById($id_corbeille);
            if (!$item) return ['success' => false, 'message' => 'Élément introuvable'];

            $this->db->beginTransaction();

            $table = $item['table_origine'];
            $id_orig = $item['id_enregistrement_original'];
            $id_field = 'id_' . rtrim($table, 's');
            if ($table === 'employes') $id_field = 'id_employe';

            // 1. Supprimer l'enregistrement d'origine physiquement de la base de données
            if ($table === 'employes') {
                // Supprimer les utilisateurs d'abord pour l'intégrité référentielle
                $this->db->prepare("DELETE FROM utilisateurs WHERE id_employe = :id")->execute([':id' => $id_orig]);
                $this->db->prepare("DELETE FROM solde_conges WHERE id_employe = :id")->execute([':id' => $id_orig]);
            }
            $stmt_orig = $this->db->prepare("DELETE FROM `$table` WHERE `$id_field` = :id");
            $stmt_orig->execute([':id' => $id_orig]);

            // 2. Marquer l'élément comme purgé dans la corbeille
            $stmt_c = $this->db->prepare("UPDATE corbeille SET est_purge = 1, date_purge = NOW(), purge_par = :user WHERE id_corbeille = :id");
            $stmt_c->execute([':user' => $purge_par, ':id' => $id_corbeille]);

            Logger::log('Purge définitive', 'suppression_definitive', $table, $id_orig, "Purgé par l'administrateur");

            $this->db->commit();
            return ['success' => true];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Erreur purge corbeille: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()];
        }
    }
}
