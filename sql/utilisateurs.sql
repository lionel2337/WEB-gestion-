CREATE TABLE IF NOT EXISTS utilisateurs (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    id_employe INT NOT NULL,
    nom_utilisateur VARCHAR(50) NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','rh','comptable','employe') NOT NULL,
    statut ENUM('actif','inactif') DEFAULT 'actif',
    compte_verrouille TINYINT(1) DEFAULT 0,
    tentatives_echec INT DEFAULT 0,
    token_csrf VARCHAR(255) NULL,
    cree_par INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    est_supprime TINYINT(1) DEFAULT 0,
    FOREIGN KEY (id_employe) REFERENCES employes(id_employe)
) ENGINE=InnoDB;
