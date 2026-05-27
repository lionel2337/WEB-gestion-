CREATE TABLE IF NOT EXISTS administration (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    cle_parametre VARCHAR(100) NOT NULL,
    valeur VARCHAR(255) NOT NULL,
    description TEXT NULL,
    cree_par INT NOT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    est_supprime TINYINT(1) DEFAULT 0,
    UNIQUE KEY uniq_cle (cle_parametre)
) ENGINE=InnoDB;
