<?php
/**
 * Classe Auth
 * Gère l'authentification et les autorisations
 */
class Auth {
    
    /**
     * Tente de connecter un utilisateur
     */
    public static function login($username, $password) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // On cherche l'utilisateur actif
            $stmt = $db->prepare("SELECT id_utilisateur, id_employe, nom_utilisateur, mot_de_passe, role, statut, compte_verrouille, tentatives_echec FROM utilisateurs WHERE nom_utilisateur = :username AND est_supprime = 0");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                Logger::log("Connexion échouée", "authentification", "utilisateurs", null, "Utilisateur introuvable : $username", null, null, "echec");
                return ['success' => false, 'message' => "Identifiants incorrects."];
            }
            
            if ($user['statut'] !== 'actif') {
                return ['success' => false, 'message' => "Ce compte est inactif ou suspendu."];
            }
            
            if ($user['compte_verrouille'] == 1) {
                return ['success' => false, 'message' => "Ce compte est verrouillé suite à de trop nombreuses tentatives."];
            }
            
            // Vérification du mot de passe
            if (password_verify($password, $user['mot_de_passe'])) {
                // Succès : réinitialiser les tentatives
                $update = $db->prepare("UPDATE utilisateurs SET tentatives_echec = 0, derniere_connexion = NOW(), adresse_ip_derniere = :ip, navigateur_dernier = :nav WHERE id_utilisateur = :id");
                $update->execute([
                    ':ip' => $_SERVER['REMOTE_ADDR'],
                    ':nav' => substr($_SERVER['HTTP_USER_AGENT'], 0, 255),
                    ':id' => $user['id_utilisateur']
                ]);
                
                // Créer la session
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['employe_id'] = $user['id_employe'];
                $_SESSION['username'] = $user['nom_utilisateur'];
                $_SESSION['role'] = $user['role'];
                
                // Journaliser
                Logger::log("Connexion réussie", "authentification", "utilisateurs", $user['id_utilisateur']);
                
                return ['success' => true];
            } else {
                // Échec : incrémenter les tentatives
                $tentatives = $user['tentatives_echec'] + 1;
                $verrouille = ($tentatives >= 5) ? 1 : 0;
                
                $update = $db->prepare("UPDATE utilisateurs SET tentatives_echec = :tentatives, compte_verrouille = :verrouille WHERE id_utilisateur = :id");
                $update->execute([
                    ':tentatives' => $tentatives,
                    ':verrouille' => $verrouille,
                    ':id' => $user['id_utilisateur']
                ]);
                
                Logger::log("Connexion échouée", "authentification", "utilisateurs", $user['id_utilisateur'], "Mot de passe incorrect", null, null, "echec");
                
                if ($verrouille) {
                    return ['success' => false, 'message' => "Compte verrouillé après 5 tentatives échouées."];
                }
                return ['success' => false, 'message' => "Identifiants incorrects."];
            }
        } catch (PDOException $e) {
            error_log("Erreur de login : " . $e->getMessage());
            return ['success' => false, 'message' => "Erreur système lors de la connexion."];
        }
    }
    
    /**
     * Déconnecte l'utilisateur
     */
    public static function logout() {
        if (isset($_SESSION['user_id'])) {
            Logger::log("Déconnexion", "authentification", "utilisateurs", $_SESSION['user_id']);
        }
        session_unset();
        session_destroy();
    }
    
    /**
     * Vérifie si l'utilisateur possède un rôle spécifique ou un niveau supérieur
     * @param string $role
     * @return bool
     */
    public static function hasRole($role) {
        if (!isset($_SESSION['role'])) {
            return false;
        }
        
        $current_role = $_SESSION['role'];
        
        // Si c'est le rôle exact
        if ($current_role === $role) {
            return true;
        }
        
        // Les rôles admin et super_admin ont tous les droits par défaut
        if ($current_role === 'super_admin') {
            return true;
        }
        if ($current_role === 'admin' && $role !== 'super_admin') {
            return true;
        }
        
        // Si le rôle demandé est 'employe', tout le monde l'est
        if ($role === 'employe') {
            return true;
        }
        
        return false;
    }


    /**
     * Vérifie si l'utilisateur possède la permission requise pour une action sur un module
     * @param string $action   e.g. 'create', 'edit', 'delete', 'view'
     * @param string $module   nom du module (ex: 'contrats', 'paie', 'conges', 'presence', 'administration', 'utilisateurs')
     * @return bool
     */
    public static function hasPermission($action, $module) {
        if (!isset($_SESSION['role'])) return false;
        $role = $_SESSION['role'];
        // Définir la matrice de permissions (simplifiée)
        $matrix = [
            'admin' => ['*' => ['create','edit','delete','view']],
            'manager' => [
                'departements' => ['view','edit'],
                'postes' => ['view','edit','create','delete'],
                'contrats' => ['view','create','edit','delete'],
                'paie' => ['view','create','edit','delete'],
                'conges' => ['view','create','edit','delete'],
                'presence' => ['view','create','edit','delete']
            ],
            'rh' => [
                'contrats' => ['view','create','edit'],
                'conges' => ['view','edit']
            ],
            'comptable' => [
                'paie' => ['view','edit','create']
            ],
            'employe' => [
                'contrats' => ['view'],
                'paie' => ['view'],
                'conges' => ['view','create']
            ]
        ];
        if (!isset($matrix[$role])) return false;
        // wildcard for admin or full access
        if (isset($matrix[$role]['*'])) {
            return in_array($action, $matrix[$role]['*']);
        }
        if (!isset($matrix[$role][$module])) return false;
        return in_array($action, $matrix[$role][$module]);
    }

}
