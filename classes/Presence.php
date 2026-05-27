<?php
/**
 * Classe Presence
 * Gestion du pointage (Arrivée/Départ), de l'historique et des statistiques.
 */
class Presence {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Enregistre l'arrivée d'un employé (Clock In)
     */
    public function clockIn($id_employe, $latitude = null, $longitude = null) {
        try {
            $today = date('Y-m-d');
            $time = date('H:i:s');
            
            // Vérifier s'il a déjà pointé aujourd'hui
            $stmt_check = $this->db->prepare("SELECT id_presence FROM presences WHERE id_employe = :id_emp AND date_presence = :date AND est_supprime = 0");
            $stmt_check->execute([':id_emp' => $id_employe, ':date' => $today]);
            if ($stmt_check->fetch()) {
                return ['success' => false, 'message' => 'Déjà pointé à l\'arrivée aujourd\'hui.'];
            }

            // Calcul du retard (si après 08:00)
            $heure_debut_travail = "08:00:00";
            $est_en_retard = 0;
            $retard_minutes = 0;
            if (strtotime($time) > strtotime($heure_debut_travail)) {
                $est_en_retard = 1;
                $retard_minutes = round((strtotime($time) - strtotime($heure_debut_travail)) / 60);
            }

            $statut = $est_en_retard ? 'retard' : 'present';

            $stmt = $this->db->prepare("INSERT INTO presences (id_employe, date_presence, heure_arrivee, est_en_retard, retard_minutes, statut, latitude_arrivee, longitude_arrivee, est_supprime, enregistre_par, date_creation) VALUES (:id_emp, :date, :time, :retard, :retard_min, :statut, :lat, :lng, 0, :user, NOW())");
            
            $stmt->execute([
                ':id_emp' => $id_employe,
                ':date' => $today,
                ':time' => $time,
                ':retard' => $est_en_retard,
                ':retard_min' => $retard_minutes,
                ':statut' => $statut,
                ':lat' => $latitude,
                ':lng' => $longitude,
                ':user' => $_SESSION['user_id'] ?? null
            ]);

            $id = $this->db->lastInsertId();
            Logger::log('Pointage arrivée effectué', 'creation', 'presences', $id, "Heure: $time");
            
            return ['success' => true, 'heure' => $time, 'retard' => $est_en_retard];
        } catch (PDOException $e) {
            error_log('Erreur pointage arrivée: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()];
        }
    }

    /**
     * Enregistre le départ d'un employé (Clock Out)
     */
    public function clockOut($id_employe) {
        try {
            $today = date('Y-m-d');
            $time = date('H:i:s');

            // Récupérer le pointage de ce jour
            $stmt_check = $this->db->prepare("SELECT * FROM presences WHERE id_employe = :id_emp AND date_presence = :date AND est_supprime = 0");
            $stmt_check->execute([':id_emp' => $id_employe, ':date' => $today]);
            $presence = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if (!$presence) {
                return ['success' => false, 'message' => 'Veuillez d\'abord pointer votre arrivée aujourd\'hui.'];
            }
            if ($presence['heure_depart'] !== null) {
                return ['success' => false, 'message' => 'Déjà pointé au départ aujourd\'hui.'];
            }

            // Calcul des heures travaillées
            $arrivee = strtotime($presence['heure_arrivee']);
            $depart = strtotime($time);
            $heures_travaillees = round(($depart - $arrivee) / 3600, 2);

            // Heures supplémentaires (si plus de 8 heures travaillées)
            $sup = 0;
            if ($heures_travaillees > 8) {
                $sup = $heures_travaillees - 8;
            }

            $stmt = $this->db->prepare("UPDATE presences SET heure_depart = :time, heures_travaillees = :h_trav, heures_supplementaires = :h_sup, date_modification = NOW() WHERE id_presence = :id");
            $stmt->execute([
                ':time' => $time,
                ':h_trav' => $heures_travaillees,
                ':h_sup' => $sup,
                ':id' => $presence['id_presence']
            ]);

            Logger::log('Pointage départ effectué', 'modification', 'presences', $presence['id_presence'], "Heure: $time, Heures travaillées: $heures_travaillees");

            return ['success' => true, 'heure' => $time, 'heures_travaillees' => $heures_travaillees];
        } catch (PDOException $e) {
            error_log('Erreur pointage départ: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur BDD : ' . $e->getMessage()];
        }
    }

    /**
     * Récupère les présences d'un employé ou de tout le personnel pour un mois donné
     */
    public function getPresences($id_employe = null, $mois = null, $annee = null) {
        if (!$mois) $mois = date('m');
        if (!$annee) $annee = date('Y');

        $sql = "SELECT p.*, e.nom, e.prenom, e.matricule, d.nom_departement 
                FROM presences p
                JOIN employes e ON p.id_employe = e.id_employe
                LEFT JOIN departements d ON e.id_departement = d.id_departement
                WHERE MONTH(p.date_presence) = :mois AND YEAR(p.date_presence) = :annee AND p.est_supprime = 0";
        $params = [':mois' => $mois, ':annee' => $annee];

        if ($id_employe) {
            $sql .= " AND p.id_employe = :id_emp";
            $params[':id_emp'] = $id_employe;
        }

        $sql .= " ORDER BY p.date_presence DESC, p.heure_arrivee ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir l'état de pointage actuel de l'employé aujourd'hui
     */
    public function getTodayState($id_employe) {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("SELECT * FROM presences WHERE id_employe = :id_emp AND date_presence = :date AND est_supprime = 0");
        $stmt->execute([':id_emp' => $id_employe, ':date' => $today]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
