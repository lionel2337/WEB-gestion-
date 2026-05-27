<?php
/**
 * Classe Poste
 * Gère le CRUD des postes au sein d'un département
 */
class Poste {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Retourne la liste des postes d'un département avec le nombre d'employés actifs
     */
    public function getByDepartment($deptId) {
        $sql = "SELECT p.*, 
                       (SELECT COUNT(*) FROM employes e WHERE e.id_poste = p.id_poste AND e.est_supprime = 0 AND e.statut_employe != 'archive') as nb_employes 
                FROM postes p 
                WHERE p.id_departement = :deptId AND p.est_supprime = 0 
                ORDER BY p.niveau_hierarchique DESC, p.titre_poste ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':deptId' => $deptId]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM postes WHERE id_poste = :id AND est_supprime = 0");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        try {
            $sql = "INSERT INTO postes (id_departement, titre_poste, niveau_hierarchique, salaire_base_min, salaire_base_max, description, cree_par, date_creation)
                    VALUES (:dept, :titre, :niv, :smin, :smax, :desc, :cree_par, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':dept' => $data['id_departement'],
                ':titre' => $data['titre_poste'],
                ':niv' => $data['niveau_hierarchique'] ?? 5,
                ':smin' => $data['salaire_base_min'] ?? null,
                ':smax' => $data['salaire_base_max'] ?? null,
                ':desc' => $data['description'] ?? null,
                ':cree_par' => $_SESSION['user_id']
            ]);
            $id = $this->db->lastInsertId();
            Logger::log('Création poste', 'creation', 'postes', $id, "Titre: {$data['titre_poste']}");
            return ['success' => true, 'id' => $id];
        } catch (PDOException $e) {
            error_log('Erreur création poste: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur BDD'];
        }
    }

    public function update($id, $data) {
        try {
            $sql = "UPDATE postes SET 
                    titre_poste = :titre,
                    niveau_hierarchique = :niv,
                    salaire_base_min = :smin,
                    salaire_base_max = :smax,
                    description = :desc,
                    date_modification = NOW()
                    WHERE id_poste = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':titre' => $data['titre_poste'],
                ':niv' => $data['niveau_hierarchique'] ?? 5,
                ':smin' => $data['salaire_base_min'] ?? null,
                ':smax' => $data['salaire_base_max'] ?? null,
                ':desc' => $data['description'] ?? null,
                ':id' => $id
            ]);
            Logger::log('Modification poste', 'modification', 'postes', $id);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log('Erreur modification poste: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur BDD'];
        }
    }

    public function softDelete($id) {
        try {
            // Vérifier qu'aucun employé n'est assigné
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM employes WHERE id_poste = :id AND est_supprime = 0');
            $stmt->execute([':id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => "Des employés utilisent ce poste."];
            }
            $sql = "UPDATE postes SET est_supprime = 1, date_modification = NOW() WHERE id_poste = :id";
            $this->db->prepare($sql)->execute([':id' => $id]);
            Logger::log('Suppression poste', 'suppression', 'postes', $id);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log('Erreur suppression poste: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur BDD'];
        }
    }
}
?>
