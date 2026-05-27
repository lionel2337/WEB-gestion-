<?php
/**
 * Classe Employe
 * Gère le CRUD des employés, la génération des matricules et le soft delete
 */
class Employe {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Génère un matricule unique : GEC-YYYY-XXXXX
     */
    public function genererMatricule() {
        $annee = date('Y');
        
        // On cherche le dernier matricule de cette année
        $stmt = $this->db->prepare("SELECT matricule FROM employes WHERE matricule LIKE :pattern ORDER BY id_employe DESC LIMIT 1");
        $stmt->execute([':pattern' => "GEC-$annee-%"]);
        $dernier = $stmt->fetchColumn();
        
        if ($dernier) {
            // Ex: GEC-2025-00042 -> 00042
            $parts = explode('-', $dernier);
            $numero = (int)end($parts);
            $nouveau_numero = $numero + 1;
        } else {
            $nouveau_numero = 1;
        }
        
        return "GEC-" . $annee . "-" . str_pad($nouveau_numero, 5, "0", STR_PAD_LEFT);
    }

    /**
     * Vérifie si un matricule existe déjà
     */
    public function matriculeExiste($matricule, $exclude_id = null) {
        $sql = "SELECT COUNT(*) FROM employes WHERE matricule = :matricule";
        $params = [':matricule' => $matricule];
        
        if ($exclude_id) {
            $sql .= " AND id_employe != :id";
            $params[':id'] = $exclude_id;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Vérifie si un email professionnel existe déjà
     */
    public function emailExiste($email, $exclude_id = null) {
        if (empty($email)) return false;
        
        $sql = "SELECT COUNT(*) FROM employes WHERE email_professionnel = :email";
        $params = [':email' => $email];
        
        if ($exclude_id) {
            $sql .= " AND id_employe != :id";
            $params[':id'] = $exclude_id;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Récupère la liste des employés avec filtres et recherche
     */
    public function getEmployes($filtres = [], $recherche = '', $limit = 50, $offset = 0, $sort_col = 'nom', $sort_dir = 'ASC') {
        $sql = "SELECT e.id_employe, e.matricule, e.nom, e.prenom, e.photo, e.sexe, 
                       e.email_professionnel, e.telephone_principal, e.date_embauche, 
                       e.statut_employe, e.type_employe,
                       d.nom_departement, p.titre_poste, s.nom_succursale
                FROM employes e
                LEFT JOIN departements d ON e.id_departement = d.id_departement
                LEFT JOIN postes p ON e.id_poste = p.id_poste
                LEFT JOIN succursales s ON e.id_succursale = s.id_succursale
                WHERE e.est_supprime = 0 AND e.statut_employe != 'archive'";
        
        $params = [];

        // Recherche (Nom, Prénom, Matricule, Email)
        if (!empty($recherche)) {
            $sql .= " AND (e.nom LIKE :search OR e.prenom LIKE :search OR e.matricule LIKE :search OR e.email_professionnel LIKE :search)";
            $params[':search'] = "%$recherche%";
        }

        // Filtres (Département, Statut, etc.)
        if (!empty($filtres['id_departement'])) {
            $sql .= " AND e.id_departement = :id_dept";
            $params[':id_dept'] = $filtres['id_departement'];
        }
        if (!empty($filtres['statut_employe'])) {
            $sql .= " AND e.statut_employe = :statut";
            $params[':statut'] = $filtres['statut_employe'];
        }
        if (!empty($filtres['type_employe'])) {
            $sql .= " AND e.type_employe = :type";
            $params[':type'] = $filtres['type_employe'];
        }

        // Tri sécurisé
        $allowed_sorts = ['nom', 'matricule', 'date_embauche', 'nom_departement', 'titre_poste', 'statut_employe'];
        if (!in_array($sort_col, $allowed_sorts)) $sort_col = 'nom';
        $sort_dir = strtoupper($sort_dir) === 'DESC' ? 'DESC' : 'ASC';
        
        // Les colonnes de jointure doivent être préfixées
        if ($sort_col == 'nom' || $sort_col == 'matricule' || $sort_col == 'date_embauche' || $sort_col == 'statut_employe') {
            $sort_col = "e." . $sort_col;
        } elseif ($sort_col == 'nom_departement') {
            $sort_col = "d.nom_departement";
        } elseif ($sort_col == 'titre_poste') {
            $sort_col = "p.titre_poste";
        }

        $sql .= " ORDER BY $sort_col $sort_dir LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Compte le nombre total d'employés selon les filtres
     */
    public function countEmployes($filtres = [], $recherche = '') {
        $sql = "SELECT COUNT(*) FROM employes e WHERE e.est_supprime = 0 AND e.statut_employe != 'archive'";
        $params = [];

        if (!empty($recherche)) {
            $sql .= " AND (e.nom LIKE :search OR e.prenom LIKE :search OR e.matricule LIKE :search OR e.email_professionnel LIKE :search)";
            $params[':search'] = "%$recherche%";
        }

        if (!empty($filtres['id_departement'])) {
            $sql .= " AND e.id_departement = :id_dept";
            $params[':id_dept'] = $filtres['id_departement'];
        }
        if (!empty($filtres['statut_employe'])) {
            $sql .= " AND e.statut_employe = :statut";
            $params[':statut'] = $filtres['statut_employe'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Récupère un employé par son ID
     */
    public function getById($id) {
        $sql = "SELECT e.*, d.nom_departement, p.titre_poste, s.nom_succursale, pays.nom_pays as pays_residence_nom
                FROM employes e
                LEFT JOIN departements d ON e.id_departement = d.id_departement
                LEFT JOIN postes p ON e.id_poste = p.id_poste
                LEFT JOIN succursales s ON e.id_succursale = s.id_succursale
                LEFT JOIN pays ON e.pays_residence = pays.id_pays
                WHERE e.id_employe = :id AND e.est_supprime = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Supprime logiquement un employé (Soft Delete)
     */
    public function softDelete($id, $motif_depart, $commentaire, $supprime_par) {
        try {
            $this->db->beginTransaction();

            // 1. Mettre à jour l'employé
            $sql = "UPDATE employes SET 
                    est_supprime = 1, 
                    date_suppression = NOW(), 
                    supprime_par = :supprime_par,
                    motif_suppression = :motif,
                    statut_employe = 'archive',
                    date_depart = NOW(),
                    motif_depart = :motif_depart,
                    commentaire_depart = :commentaire,
                    peut_etre_restaure = 1,
                    date_limite_restauration = DATE_ADD(NOW(), INTERVAL 90 DAY)
                    WHERE id_employe = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':supprime_par' => $supprime_par,
                ':motif' => "Suppression via l'interface",
                ':motif_depart' => $motif_depart,
                ':commentaire' => $commentaire,
                ':id' => $id
            ]);

            // 2. Désactiver le compte utilisateur lié
            $sql_user = "UPDATE utilisateurs SET statut = 'inactif', est_supprime = 1 WHERE id_employe = :id";
            $stmt_user = $this->db->prepare($sql_user);
            $stmt_user->execute([':id' => $id]);

            Logger::log("Suppression logique", "suppression", "employes", $id, "Motif: $motif_depart");

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur Soft Delete Employe : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée un nouvel employé (Partiel, pour le wizard)
     */
    public function creer($data) {
        try {
            $champs = [];
            $valeurs = [];
            $params = [];
            
            // On génère le matricule si non fourni
            if (empty($data['matricule'])) {
                $data['matricule'] = $this->genererMatricule();
            }

            // On ajoute les champs de tracking
            $data['cree_par'] = $_SESSION['user_id'] ?? null;
            $data['date_creation'] = date('Y-m-d H:i:s');

            foreach ($data as $colonne => $valeur) {
                $champs[] = $colonne;
                $valeurs[] = ":" . $colonne;
                $params[":" . $colonne] = $valeur;
            }

            $sql = "INSERT INTO employes (" . implode(", ", $champs) . ") VALUES (" . implode(", ", $valeurs) . ")";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $id = $this->db->lastInsertId();
            
            Logger::log("Création employé", "creation", "employes", $id, "Matricule: " . $data['matricule']);
            
            return ['success' => true, 'id_employe' => $id, 'matricule' => $data['matricule']];
        } catch (PDOException $e) {
            error_log("Erreur création employé : " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur BDD: " . $e->getMessage()];
        }
    }
}
