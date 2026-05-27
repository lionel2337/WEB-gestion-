<?php
/**
 * Classe Paie
 * Gestion des enregistrements de paie
 */
class Paie {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    public function create($data) {
        $stmt = $this->db->prepare('INSERT INTO paie (id_employe, mois, salaire_brut, salaire_net, cree_par) VALUES (:id_emp, :mois, :brut, :net, :cree_par)');
        return $stmt->execute([
            ':id_emp' => $data['id_employe'],
            ':mois' => $data['mois'],
            ':brut' => $data['salaire_brut'],
            ':net' => $data['salaire_net'],
            ':cree_par' => $_SESSION['user_id']
        ]);
    }
    public function getAll() {
        $stmt = $this->db->query('SELECT * FROM paie WHERE est_supprime = 0');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getById($id) {
        $stmt = $this->db->prepare('SELECT * FROM paie WHERE id_paie = :id AND est_supprime = 0');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function update($id, $data) {
        $stmt = $this->db->prepare('UPDATE paie SET mois = :mois, salaire_brut = :brut, salaire_net = :net WHERE id_paie = :id');
        return $stmt->execute([
            ':mois' => $data['mois'],
            ':brut' => $data['salaire_brut'],
            ':net' => $data['salaire_net'],
            ':id' => $id
        ]);
    }
    public function delete($id) {
        $stmt = $this->db->prepare('UPDATE paie SET est_supprime = 1 WHERE id_paie = :id');
        return $stmt->execute([':id' => $id]);
    }
}
?>
