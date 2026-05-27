<?php
/**
 * Classe Notification
 * Gestion des notifications internes au système.
 */
class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crée une nouvelle notification
     */
    public static function create($id_destinataire, $titre, $message, $categorie = 'general', $type = 'info', $lien = null, $id_expediteur = null) {
        try {
            $db = Database::getInstance()->getConnection();
            $icones = [
                'conge' => 'fa-umbrella-beach',
                'paie' => 'fa-money-check-alt',
                'evaluation' => 'fa-star',
                'contrat' => 'fa-file-signature',
                'formation' => 'fa-graduation-cap',
                'general' => 'fa-info-circle',
                'systeme' => 'fa-server'
            ];
            
            $couleurs = [
                'info' => 'primary',
                'succes' => 'success',
                'alerte' => 'warning',
                'urgent' => 'danger',
                'systeme' => 'secondary'
            ];

            $icone = $icones[$categorie] ?? 'fa-bell';
            $couleur = $couleurs[$type] ?? 'primary';

            $stmt = $db->prepare("INSERT INTO notifications (id_destinataire, id_expediteur, titre, message, type_notification, categorie, icone, couleur, lien_action, est_lu, est_archive, est_supprime, date_creation) VALUES (:dest, :exp, :titre, :msg, :type, :cat, :icon, :color, :lien, 0, 0, 0, NOW())");
            
            $stmt->execute([
                ':dest' => $id_destinataire,
                ':exp' => $id_expediteur,
                ':titre' => $titre,
                ':msg' => $message,
                ':type' => $type,
                ':cat' => $categorie,
                ':icon' => $icone,
                ':color' => $couleur,
                ':lien' => $lien
            ]);

            return true;
        } catch (PDOException $e) {
            error_log('Erreur création notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les notifications non lues d'un utilisateur
     */
    public function getUnread($id_utilisateur) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE id_destinataire = :user AND est_lu = 0 AND est_supprime = 0 ORDER BY date_creation DESC");
        $stmt->execute([':user' => $id_utilisateur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compte le nombre de notifications non lues d'un utilisateur
     */
    public function countUnread($id_utilisateur) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE id_destinataire = :user AND est_lu = 0 AND est_supprime = 0");
        $stmt->execute([':user' => $id_utilisateur]);
        return $stmt->fetchColumn();
    }

    /**
     * Récupère toutes les notifications d'un utilisateur
     */
    public function getAll($id_utilisateur, $limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE id_destinataire = :user AND est_supprime = 0 ORDER BY date_creation DESC LIMIT :limit");
        $stmt->bindValue(':user', $id_utilisateur, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead($id_notification) {
        $stmt = $this->db->prepare("UPDATE notifications SET est_lu = 1, date_lecture = NOW() WHERE id_notification = :id");
        return $stmt->execute([':id' => $id_notification]);
    }

    /**
     * Marque toutes les notifications comme lues pour un utilisateur
     */
    public function markAllAsRead($id_utilisateur) {
        $stmt = $this->db->prepare("UPDATE notifications SET est_lu = 1, date_lecture = NOW() WHERE id_destinataire = :user AND est_lu = 0");
        return $stmt->execute([':user' => $id_utilisateur]);
    }
}
