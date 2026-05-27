<?php
/**
 * Classe Contrat
 * Gestion CRUD des contrats avec upload de PDF.
 */
class Contrat {
    private $db;
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    public function create($data, $file) {
        // gérer l'upload du PDF
        $upload = handle_file_upload('fichier', __DIR__.'/../uploads/contrats/', ['application/pdf']);
        if (isset($upload['error'])) return ['success'=>false,'message'=>$upload['error']];
        $path = $upload['path'];
        $stmt = $this->db->prepare("INSERT INTO contrats (id_employe, date_debut, date_fin, fichier, cree_par) VALUES (:emp,:debut,:fin,:file,:user)");
        $stmt->execute([
            ':emp' => $data['id_employe'],
            ':debut' => $data['date_debut'],
            ':fin' => $data['date_fin'],
            ':file' => $path,
            ':user' => $_SESSION['user_id']
        ]);
        return ['success'=>true];
    }
    public function getAll($deptId=null) {
        $sql = "SELECT * FROM contrats WHERE est_supprime = 0";
        if ($deptId) $sql .= " AND id_departement = :dept";
        $stmt = $this->db->prepare($sql);
        if ($deptId) $stmt->bindParam(':dept',$deptId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function update($id, $data, $file=null) {
        $fields = [];
        $params = [':id'=>$id];
        if (!empty($data['date_debut'])) { $fields[]='date_debut=:debut'; $params[':debut']=$data['date_debut']; }
        if (!empty($data['date_fin']))   { $fields[]='date_fin=:fin'; $params[':fin']=$data['date_fin']; }
        if ($file && $file['error']===UPLOAD_ERR_OK) {
            $upload = handle_file_upload('fichier', __DIR__.'/../uploads/contrats/', ['application/pdf']);
            if (isset($upload['error'])) return ['success'=>false,'message'=>$upload['error']];
            $fields[]='fichier=:file';
            $params[':file']=$upload['path'];
        }
        if (empty($fields)) return ['success'=>false,'message'=>'Aucun champ à mettre à jour'];
        $stmt = $this->db->prepare('UPDATE contrats SET '.implode(',', $fields).' WHERE id_contrat=:id');
        $stmt->execute($params);
        return ['success'=>true];
    }
    public function delete($id) {
        $stmt = $this->db->prepare('UPDATE contrats SET est_supprime=1 WHERE id_contrat=:id');
        $stmt->execute([':id'=>$id]);
        return ['success'=>true];
    }
}
?>
