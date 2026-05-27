CREATE TABLE IF NOT EXISTS conges (
    id_conge INT AUTO_INCREMENT PRIMARY KEY,
    id_employe INT NOT NULL,
    type_conge VARCHAR(50) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    statut VARCHAR(20) DEFAULT 'en_attente',
    cree_par INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    est_supprime TINYINT(1) DEFAULT 0,
    FOREIGN KEY (id_employe) REFERENCES employes(id_employe)
) ENGINE=InnoDB;
