<?php
/**
 * Classe Departement
 * Gestion des départements de l'entreprise.
 * Fournit des méthodes CRUD simples en utilisant la classe Database (singleton).
 */
class Departement {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Retourne tous les départements avec le nombre d'employés actifs */
    public function getAll() {
        $sql = "SELECT d.*, COALESCE((SELECT COUNT(*) FROM employes e WHERE e.id_departement = d.id_departement AND e.est_supprime = 0 AND e.statut_employe != 'archive'), 0) AS nb_employes
                FROM departements d
                ORDER BY d.nom_departement ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Retourne un département par son ID */
    public function getById(int $id) {
        $stmt = $this->db->prepare('SELECT * FROM departements WHERE id_departement = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Crée un nouveau département */
    public function create(array $data) {
        $stmt = $this->db->prepare('INSERT INTO departements (nom_departement, description, id_responsable, cree_par, date_creation) VALUES (:nom, :desc, :resp, :cree_par, NOW())');
        $stmt->execute([
            ':nom' => $data['nom_departement'],
            ':desc' => $data['description'] ?? null,
            ':resp' => $data['id_responsable'] ?? null,
            ':cree_par' => $_SESSION['user_id']
        ]);
        $id = $this->db->lastInsertId();
        Logger::log('Création département', 'creation', 'departements', $id, 'Nom: ' . $data['nom_departement']);
        return ['success' => true, 'id' => $id];
    }

    /** Met à jour un département existant */
    public function update(int $id, array $data) {
        $stmt = $this->db->prepare('UPDATE departements SET nom_departement = :nom, description = :desc, id_responsable = :resp, date_modification = NOW() WHERE id_departement = :id');
        $stmt->execute([
            ':id' => $id,
            ':nom' => $data['nom_departement'],
            ':desc' => $data['description'] ?? null,
            ':resp' => $data['id_responsable'] ?? null
        ]);
        Logger::log('Modification département', 'modification', 'departements', $id);
        return ['success' => true];
    }

    /** Supprime logiquement un département */
    public function softDelete(int $id) {
        // Vérifier qu'aucun employé n'est dans le département
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM employes WHERE id_departement = :id AND est_supprime = 0');
        $stmt->execute([':id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Des employés sont encore associés à ce département.'];
        }
        $stmt = $this->db->prepare('UPDATE departements SET est_supprime = 1, date_modification = NOW() WHERE id_departement = :id');
        $stmt->execute([':id' => $id]);
        // Archiver les postes liés
        $this->db->prepare('UPDATE postes SET est_supprime = 1 WHERE id_departement = :id')->execute([':id' => $id]);
        Logger::log('Suppression département', 'suppression', 'departements', $id);
        return ['success' => true];
    }
}
?>
