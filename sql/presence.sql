CREATE TABLE IF NOT EXISTS presence (
    id_presence INT AUTO_INCREMENT PRIMARY KEY,
    id_employe INT NOT NULL,
    date_presence DATE NOT NULL,
    heure_arrivee TIME NULL,
    heure_depart TIME NULL,
    cree_par INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    est_supprime TINYINT(1) DEFAULT 0,
    FOREIGN KEY (id_employe) REFERENCES employes(id_employe)
) ENGINE=InnoDB;
