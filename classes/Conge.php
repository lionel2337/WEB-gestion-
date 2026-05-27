<?php
/**
 * Classe Conge
 * Gestion des demandes de congé, des soldes et du workflow d'approbation.
 */
class Conge {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Génère une référence de congé unique : CONG-YYYY-XXXXX
     */
    public function genererReference() {
        $annee = date('Y');
        $stmt = $this->db->prepare("SELECT reference_conge FROM conges WHERE reference_conge LIKE :pattern ORDER BY id_conge DESC LIMIT 1");
        $stmt->execute([':pattern' => "CONG-$annee-%"]);
        $dernier = $stmt->fetchColumn();
        
        if ($dernier) {
            $parts = explode('-', $dernier);
            $numero = (int)end($parts);
            $nouveau_numero = $numero + 1;
        } else {
            $nouveau_numero = 1;
        }
        
        return "CONG-" . $annee . "-" . str_pad($nouveau_numero, 5, "0", STR_PAD_LEFT);
    }

    /**
     * Récupère tous les types de congés
     */
    public function getTypesConges() {
        $stmt = $this->db->query("SELECT * FROM types_conges WHERE statut = 'actif'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère le solde de congés d'un employé pour une année
     */
    public function getSolde($id_employe, $id_type_conge, $annee = null) {
        if (!$annee) $annee = date('Y');
        $stmt = $this->db->prepare("SELECT * FROM solde_conges WHERE id_employe = :id_emp AND id_type_conge = :id_type AND annee = :annee");
        $stmt->execute([
            ':id_emp' => $id_employe,
            ':id_type' => $id_type_conge,
            ':annee' => $annee
        ]);
        $solde = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si aucun solde n'existe pour cette année, on le crée à partir du type de congé
        if (!$solde) {
            $stmt_type = $this->db->prepare("SELECT nombre_jours_max FROM types_conges WHERE id_type_conge = :id_type");
            $stmt_type->execute([':id_type' => $id_type_conge]);
            $jours_acquis = $stmt_type->fetchColumn() ?: 30;

            $stmt_insert = $this->db->prepare("INSERT INTO solde_conges (id_employe, id_type_conge, annee, jours_acquis, jours_reportes, jours_supplementaires, jours_pris, jours_planifies, jours_restants, derniere_mise_a_jour) VALUES (:id_emp, :id_type, :annee, :acquis, 0, 0, 0, 0, :restants, NOW())");
            $stmt_insert->execute([
                ':id_emp' => $id_employe,
                ':id_type' => $id_type_conge,
                ':annee' => $annee,
                ':acquis' => $jours_acquis,
                ':restants' => $jours_acquis
            ]);

            return $this->getSolde($id_employe, $id_type_conge, $annee);
        }

        return $solde;
    }

    /**
     * Crée une demande de congé
     */
    public function create($data) {
        try {
            $ref = $this->genererReference();
            $stmt = $this->db->prepare("INSERT INTO conges (reference_conge, id_employe, id_type_conge, date_debut, date_fin, nombre_jours, demi_journee, motif, adresse_pendant_conge, telephone_pendant_conge, id_interim, statut_manager, statut_rh, statut_direction, statut, est_supprime, date_creation) VALUES (:ref, :id_emp, :id_type, :debut, :fin, :jours, :demi, :motif, :adresse, :tel, :interim, 'en_attente', 'en_attente', 'non_requis', 'soumis', 0, NOW())");
            
            $stmt->execute([
                ':ref' => $ref,
                ':id_emp' => $data['id_employe'],
                ':id_type' => $data['id_type_conge'],
                ':debut' => $data['date_debut'],
                ':fin' => $data['date_fin'],
                ':jours' => $data['nombre_jours'],
                ':demi' => $data['demi_journee'] ?? 0,
                ':motif' => $data['motif'] ?? '',
                ':adresse' => $data['adresse_pendant_conge'] ?? '',
                ':tel' => $data['telephone_pendant_conge'] ?? '',
                ':interim' => $data['id_interim'] ?? null
            ]);

            $id = $this->db->lastInsertId();
            Logger::log('Demande de congé créée', 'creation', 'conges', $id, "Ref: $ref");

            // Notification pour le manager de l'employé
            $stmt_emp = $this->db->prepare("SELECT nom, prenom, id_departement FROM employes WHERE id_employe = :id");
            $stmt_emp->execute([':id' => $data['id_employe']]);
            $emp = $stmt_emp->fetch(PDO::FETCH_ASSOC);

            if ($emp && $emp['id_departement']) {
                $stmt_dept = $this->db->prepare("SELECT id_responsable FROM departements WHERE id_departement = :id");
                $stmt_dept->execute([':id' => $emp['id_departement']]);
                $id_resp = $stmt_dept->fetchColumn();

                if ($id_resp) {
                    $stmt_user = $this->db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE id_employe = :id_emp");
                    $stmt_user->execute([':id_emp' => $id_resp]);
                    $id_user_resp = $stmt_user->fetchColumn();

                    if ($id_user_resp) {
                        Notification::create($id_user_resp, "Nouvelle demande de congé", "L'employé {$emp['prenom']} {$emp['nom']} a soumis une demande de congé (Ref: $ref).", 'conge', 'info', BASE_URL . "/pages/conges/approve.php");
                    }
                }
            }

            return ['success' => true, 'id' => $id, 'reference' => $ref];
        } catch (PDOException $e) {
            error_log('Erreur création congé: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()];
        }
    }

    /**
     * Récupère la liste des demandes de congés
     */
    public function getConges($id_employe = null, $statut = null) {
        $sql = "SELECT c.*, tc.nom_type, tc.couleur, e.nom, e.prenom, e.matricule, d.nom_departement 
                FROM conges c
                JOIN types_conges tc ON c.id_type_conge = tc.id_type_conge
                JOIN employes e ON c.id_employe = e.id_employe
                LEFT JOIN departements d ON e.id_departement = d.id_departement
                WHERE c.est_supprime = 0";
        $params = [];

        if ($id_employe) {
            $sql .= " AND c.id_employe = :id_emp";
            $params[':id_emp'] = $id_employe;
        }

        if ($statut) {
            $sql .= " AND c.statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY c.date_creation DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une demande par son ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT c.*, tc.nom_type, tc.couleur, e.nom, e.prenom, e.matricule, d.nom_departement FROM conges c JOIN types_conges tc ON c.id_type_conge = tc.id_type_conge JOIN employes e ON c.id_employe = e.id_employe LEFT JOIN departements d ON e.id_departement = d.id_departement WHERE c.id_conge = :id AND c.est_supprime = 0");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Décide d'une approbation de congé (Workflow Manager -> RH -> Direction)
     */
    public function approve($id_conge, $role, $decision, $commentaire, $user_id) {
        try {
            $conge = $this->getById($id_conge);
            if (!$conge) return ['success' => false, 'message' => 'Demande introuvable'];

            $fields = [];
            $params = [':id' => $id_conge, ':user' => $user_id, ':decision' => $decision, ':comm' => $commentaire];

            if ($role === 'manager') {
                $fields[] = "statut_manager = :decision";
                $fields[] = "id_manager = :user";
                $fields[] = "date_decision_manager = NOW()";
                $fields[] = "commentaire_manager = :comm";
                
                if ($decision === 'approuve') {
                    $fields[] = "statut = 'approuve_manager'";
                    $fields[] = "statut_rh = 'en_attente'";
                } else {
                    $fields[] = "statut = 'rejete'";
                }
            } elseif ($role === 'rh') {
                $fields[] = "statut_rh = :decision";
                $fields[] = "id_rh = :user";
                $fields[] = "date_decision_rh = NOW()";
                $fields[] = "commentaire_rh = :comm";

                if ($decision === 'approuve') {
                    // Si congé long ou sensible, demande direction
                    if ($conge['nombre_jours'] > 14) {
                        $fields[] = "statut = 'approuve_rh'";
                        $fields[] = "statut_direction = 'en_attente'";
                    } else {
                        $fields[] = "statut = 'approuve_final'";
                        $this->deduireSolde($conge['id_employe'], $conge['id_type_conge'], date('Y', strtotime($conge['date_debut'])), $conge['nombre_jours']);
                    }
                } else {
                    $fields[] = "statut = 'rejete'";
                }
            } elseif ($role === 'super_admin' || $role === 'admin' || $role === 'directeur') {
                $fields[] = "statut_direction = :decision";
                $fields[] = "id_directeur = :user";
                $fields[] = "date_decision_direction = NOW()";
                $fields[] = "commentaire_direction = :comm";

                if ($decision === 'approuve') {
                    $fields[] = "statut = 'approuve_final'";
                    $this->deduireSolde($conge['id_employe'], $conge['id_type_conge'], date('Y', strtotime($conge['date_debut'])), $conge['nombre_jours']);
                } else {
                    $fields[] = "statut = 'rejete'";
                }
            }

            if (empty($fields)) return ['success' => false, 'message' => 'Rôle non autorisé'];

            $sql = "UPDATE conges SET " . implode(", ", $fields) . ", date_modification = NOW() WHERE id_conge = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            Logger::log('Décision congé prise', 'approbation', 'conges', $id_conge, "Rôle: $role, Décision: $decision");

            // Notification pour l'employé
            $stmt_u = $this->db->prepare("SELECT id_utilisateur FROM utilisateurs WHERE id_employe = :id_emp");
            $stmt_u->execute([':id_emp' => $conge['id_employe']]);
            $u_id = $stmt_u->fetchColumn();
            
            if ($u_id) {
                $texte_decision = ($decision === 'approuve') ? "approuvée par le $role" : "rejetée par le $role";
                Notification::create($u_id, "Décision sur votre demande de congé", "Votre demande de congé (Ref: {$conge['reference_conge']}) a été $texte_decision.", 'conge', ($decision === 'approuve' ? 'succes' : 'alerte'), BASE_URL . "/pages/conges/");
            }

            return ['success' => true];
        } catch (PDOException $e) {
            error_log('Erreur décision congé: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()];
        }
    }

    /**
     * Déduit les jours pris du solde de l'employé
     */
    private function deduireSolde($id_employe, $id_type_conge, $annee, $jours) {
        $solde = $this->getSolde($id_employe, $id_type_conge, $annee);
        if ($solde) {
            $pris = $solde['jours_pris'] + $jours;
            $restants = $solde['jours_restants'] - $jours;
            
            $stmt = $this->db->prepare("UPDATE solde_conges SET jours_pris = :pris, jours_restants = :restants, derniere_mise_a_jour = NOW() WHERE id_solde = :id_solde");
            $stmt->execute([
                ':pris' => $pris,
                ':restants' => $restants,
                ':id_solde' => $solde['id_solde']
            ]);
        }
    }
}
