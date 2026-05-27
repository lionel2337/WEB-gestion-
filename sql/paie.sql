CREATE TABLE IF NOT EXISTS paie (
    id_paie INT AUTO_INCREMENT PRIMARY KEY,
    id_employe INT NOT NULL,
    mois VARCHAR(7) NOT NULL, -- format YYYY-MM
    salaire_brut DECIMAL(12,2) NOT NULL,
    salaire_net DECIMAL(12,2) NOT NULL,
    cree_par INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    est_supprime TINYINT(1) DEFAULT 0,
    FOREIGN KEY (id_employe) REFERENCES employes(id_employe)
) ENGINE=InnoDB;
