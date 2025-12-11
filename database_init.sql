-- ====================================
-- Script SQL d'initialisation
-- Bureau de Change - Symfony Edition
-- ====================================

-- Créer la base de données (si elle n'existe pas)
CREATE DATABASE IF NOT EXISTS bureau_change CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bureau_change;

-- ====================================
-- 1. TABLE AGENCES
-- ====================================
CREATE TABLE IF NOT EXISTS agences (
    id_agence INT AUTO_INCREMENT PRIMARY KEY,
    nom_agence VARCHAR(255) NOT NULL,
    adresse VARCHAR(255),
    telephone VARCHAR(20),
    email VARCHAR(100),
    statut VARCHAR(20) DEFAULT 'actif',
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des agences
INSERT INTO agences (nom_agence, adresse, telephone, email, statut) VALUES
('Agence Principale - Kinshasa', 'Avenue Kasa-Vubu, Kinshasa', '+243 81 123 4567', 'kinshasa@bureau.cd', 'actif'),
('Agence Lubumbashi', 'Avenue Lumumba, Lubumbashi', '+243 82 234 5678', 'lubumbashi@bureau.cd', 'actif'),
('Agence Goma', 'Avenue du Rond-Point, Goma', '+243 83 345 6789', 'goma@bureau.cd', 'actif');

-- ====================================
-- 2. TABLE TYPES D'IDENTITÉ
-- ====================================
CREATE TABLE IF NOT EXISTS types_identite (
    id_identite INT AUTO_INCREMENT PRIMARY KEY,
    libelle_identite VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des types d'identité
INSERT INTO types_identite (libelle_identite) VALUES
('Carte d\'identité nationale'),
('Passeport'),
('Permis de conduire'),
('Carte d\'électeur'),
('Carte professionnelle');

-- ====================================
-- 3. TABLE DEVISES
-- ====================================
CREATE TABLE IF NOT EXISTS devise (
    id_devise INT AUTO_INCREMENT PRIMARY KEY,
    sigle VARCHAR(10) NOT NULL UNIQUE,
    libelle VARCHAR(100) NOT NULL,
    taux_achat DECIMAL(15, 4) NOT NULL,
    taux_vente DECIMAL(15, 4) NOT NULL,
    statut VARCHAR(20) DEFAULT 'actif',
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des devises principales
INSERT INTO devise (sigle, libelle, taux_achat, taux_vente, statut) VALUES
('CDF', 'Franc Congolais', 1.0000, 1.0000, 'actif'),
('USD', 'Dollar Américain', 2800.0000, 2850.0000, 'actif'),
('EUR', 'Euro', 3100.0000, 3180.0000, 'actif'),
('GBP', 'Livre Sterling', 3650.0000, 3730.0000, 'actif'),
('ZAR', 'Rand Sud-Africain', 155.0000, 162.0000, 'actif');

-- ====================================
-- 4. TABLE UTILISATEURS
-- ====================================
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    roles JSON NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    statut VARCHAR(20) DEFAULT 'actif',
    agence_id INT,
    FOREIGN KEY (agence_id) REFERENCES agences(id_agence) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des utilisateurs (mot de passe: "admin123" pour tous)
-- Hash généré avec: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO utilisateurs (nom, email, roles, mot_de_passe, statut, agence_id) VALUES
('Administrateur Système', 'admin@bureau.cd', '["ROLE_SUPER_ADMIN"]', '$2y$13$xKXJYGdC9jZKvJpPpHbMUeHrZsJLVZH8qN.ZqYB7qKQwL.mW8fZTy', 'actif', 1),
('Jean Kabongo', 'jean.kabongo@bureau.cd', '["ROLE_ADMIN"]', '$2y$13$xKXJYGdC9jZKvJpPpHbMUeHrZsJLVZH8qN.ZqYB7qKQwL.mW8fZTy', 'actif', 1),
('Marie Tshala', 'marie.tshala@bureau.cd', '["ROLE_USER"]', '$2y$13$xKXJYGdC9jZKvJpPpHbMUeHrZsJLVZH8qN.ZqYB7qKQwL.mW8fZTy', 'actif', 1),
('Pierre Mwamba', 'pierre.mwamba@bureau.cd', '["ROLE_USER"]', '$2y$13$xKXJYGdC9jZKvJpPpHbMUeHrZsJLVZH8qN.ZqYB7qKQwL.mW8fZTy', 'actif', 2);

-- ====================================
-- 5. TABLE FONDS DE DÉPART
-- ====================================
CREATE TABLE IF NOT EXISTS fonds_depart (
    id_fonds_depart INT AUTO_INCREMENT PRIMARY KEY,
    date_jour DATE NOT NULL,
    agence_id INT NOT NULL,
    statut VARCHAR(20) DEFAULT 'ouvert',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agence_id) REFERENCES agences(id_agence) ON DELETE CASCADE,
    INDEX idx_date_agence (date_jour, agence_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des fonds de départ
INSERT INTO fonds_depart (date_jour, agence_id, statut, created_at) VALUES
(CURDATE(), 1, 'ouvert', NOW()),
(CURDATE(), 2, 'ouvert', NOW()),
(CURDATE(), 3, 'ouvert', NOW());

-- ====================================
-- 6. TABLE DÉTAILS FONDS DE DÉPART
-- ====================================
CREATE TABLE IF NOT EXISTS details_fonds_depart (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_fonds_depart INT NOT NULL,
    id_devise INT NOT NULL,
    montant DECIMAL(15, 2) DEFAULT 0.00,
    agence_id INT NOT NULL,
    FOREIGN KEY (id_fonds_depart) REFERENCES fonds_depart(id_fonds_depart) ON DELETE CASCADE,
    FOREIGN KEY (id_devise) REFERENCES devise(id_devise) ON DELETE CASCADE,
    FOREIGN KEY (agence_id) REFERENCES agences(id_agence) ON DELETE CASCADE,
    INDEX idx_fonds_devise (id_fonds_depart, id_devise),
    INDEX idx_agence_devise (agence_id, id_devise)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des soldes initiaux pour l'agence 1
INSERT INTO details_fonds_depart (id_fonds_depart, id_devise, montant, agence_id) VALUES
(1, 1, 5000000.00, 1),  -- 5M CDF
(1, 2, 50000.00, 1),    -- 50K USD
(1, 3, 30000.00, 1),    -- 30K EUR
(1, 4, 10000.00, 1),    -- 10K GBP
(1, 5, 100000.00, 1);   -- 100K ZAR

-- Pour l'agence 2
INSERT INTO details_fonds_depart (id_fonds_depart, id_devise, montant, agence_id) VALUES
(2, 1, 3000000.00, 2),
(2, 2, 30000.00, 2),
(2, 3, 20000.00, 2);

-- ====================================
-- 7. TABLE TRANSACTIONS
-- ====================================
CREATE TABLE IF NOT EXISTS transactions (
    id_transaction INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(255) NOT NULL,
    identite_id INT NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    telephone VARCHAR(50) NOT NULL,
    nature_operation VARCHAR(20) NOT NULL,
    date_transaction DATE NOT NULL,
    utilisateur_id INT NOT NULL,
    agence_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (identite_id) REFERENCES types_identite(id_identite),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (agence_id) REFERENCES agences(id_agence),
    INDEX idx_reference (reference),
    INDEX idx_date (date_transaction),
    INDEX idx_agence (agence_id),
    INDEX idx_nature (nature_operation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- 8. TABLE DÉTAILS TRANSACTIONS
-- ====================================
CREATE TABLE IF NOT EXISTS details_transaction (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_transaction INT NOT NULL,
    devise_id_input INT NOT NULL,
    devise_id_output INT NOT NULL,
    montant DECIMAL(15, 2) NOT NULL,
    taux DECIMAL(15, 4) NOT NULL,
    contre_valeur DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (id_transaction) REFERENCES transactions(id_transaction) ON DELETE CASCADE,
    FOREIGN KEY (devise_id_input) REFERENCES devise(id_devise),
    FOREIGN KEY (devise_id_output) REFERENCES devise(id_devise),
    INDEX idx_transaction (id_transaction)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- DONNÉES DE TEST - TRANSACTIONS
-- ====================================

-- Transaction 1 : Achat de USD
INSERT INTO transactions (reference, nom, identite_id, adresse, telephone, nature_operation, date_transaction, utilisateur_id, agence_id, created_at) 
VALUES ('TX' || DATE_FORMAT(NOW(), '%y%m%d') || 'A001', 'Jean Mutombo', 1, 'Kinshasa, Lemba', '+243 81 111 1111', 'achat', CURDATE(), 2, 1, NOW());

INSERT INTO details_transaction (id_transaction, devise_id_input, devise_id_output, montant, taux, contre_valeur) 
VALUES (LAST_INSERT_ID(), 2, 1, 500.00, 2800.0000, 1400000.00);

-- Mise à jour des fonds après transaction
UPDATE details_fonds_depart SET montant = montant - 500.00 WHERE agence_id = 1 AND id_devise = 2;
UPDATE details_fonds_depart SET montant = montant + 1400000.00 WHERE agence_id = 1 AND id_devise = 1;

-- Transaction 2 : Vente de EUR
INSERT INTO transactions (reference, nom, identite_id, adresse, telephone, nature_operation, date_transaction, utilisateur_id, agence_id, created_at) 
VALUES (CONCAT('TX', DATE_FORMAT(NOW(), '%y%m%d'), 'V002'), 'Marie Kalala', 2, 'Kinshasa, Gombe', '+243 82 222 2222', 'vente', CURDATE(), 3, 1, NOW());

INSERT INTO details_transaction (id_transaction, devise_id_input, devise_id_output, montant, taux, contre_valeur) 
VALUES (LAST_INSERT_ID(), 1, 3, 3180000.00, 3180.0000, 1000.00);

-- Mise à jour des fonds après transaction
UPDATE details_fonds_depart SET montant = montant - 1000.00 WHERE agence_id = 1 AND id_devise = 3;
UPDATE details_fonds_depart SET montant = montant + 3180000.00 WHERE agence_id = 1 AND id_devise = 1;

-- ====================================
-- VUE POUR LES STATISTIQUES
-- ====================================
CREATE OR REPLACE VIEW v_stats_transactions AS
SELECT 
    DATE(t.date_transaction) AS date_trans,
    t.agence_id,
    a.nom_agence,
    t.nature_operation,
    COUNT(*) AS nb_transactions,
    SUM(dt.contre_valeur) AS total_montant
FROM transactions t
INNER JOIN details_transaction dt ON t.id_transaction = dt.id_transaction
INNER JOIN agences a ON t.agence_id = a.id_agence
GROUP BY DATE(t.date_transaction), t.agence_id, a.nom_agence, t.nature_operation;

-- ====================================
-- PROCÉDURES STOCKÉES
-- ====================================

DELIMITER //

-- Procédure pour obtenir les soldes d'une agence
CREATE PROCEDURE IF NOT EXISTS sp_get_soldes_agence(IN p_agence_id INT)
BEGIN
    SELECT 
        d.sigle,
        d.libelle,
        COALESCE(SUM(dfd.montant), 0) AS solde_disponible,
        d.taux_achat,
        d.taux_vente
    FROM devise d
    LEFT JOIN details_fonds_depart dfd ON d.id_devise = dfd.id_devise AND dfd.agence_id = p_agence_id
    WHERE d.statut = 'actif'
    GROUP BY d.id_devise, d.sigle, d.libelle, d.taux_achat, d.taux_vente
    ORDER BY d.sigle;
END //

-- Procédure pour générer une référence unique de transaction
CREATE PROCEDURE IF NOT EXISTS sp_generate_transaction_ref(OUT p_reference VARCHAR(50))
BEGIN
    DECLARE v_exists INT DEFAULT 1;
    DECLARE v_ref VARCHAR(50);
    
    WHILE v_exists > 0 DO
        SET v_ref = CONCAT('TX', DATE_FORMAT(NOW(), '%y%m%d'), LPAD(FLOOR(RAND() * 1000000), 6, '0'));
        SELECT COUNT(*) INTO v_exists FROM transactions WHERE reference = v_ref;
    END WHILE;
    
    SET p_reference = v_ref;
END //

DELIMITER ;

-- ====================================
-- INDEX ADDITIONNELS POUR PERFORMANCES
-- ====================================
CREATE INDEX idx_transactions_date_agence ON transactions(date_transaction, agence_id);
CREATE INDEX idx_details_transaction_devise ON details_transaction(devise_id_input, devise_id_output);

-- ====================================
-- SCRIPT TERMINÉ
-- ====================================
SELECT 'Base de données initialisée avec succès!' AS message;
SELECT COUNT(*) AS nb_agences FROM agences;
SELECT COUNT(*) AS nb_utilisateurs FROM utilisateurs;
SELECT COUNT(*) AS nb_devises FROM devise;
SELECT COUNT(*) AS nb_transactions FROM transactions;
