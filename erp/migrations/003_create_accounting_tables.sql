-- Migration 003: Tables de comptabilité et scanner de documents
-- Date: 15 février 2026
-- Description: Création des tables pour la gestion comptable, connexion bancaire et scanner de documents

USE webitech_crm;

-- ============================================
-- Table des comptes bancaires
-- ============================================
CREATE TABLE IF NOT EXISTS erp_bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    bank_name VARCHAR(255) NOT NULL COMMENT 'Nom de la banque',
    account_number VARCHAR(100) NOT NULL COMMENT 'Numéro de compte (IBAN)',
    account_type ENUM('checking', 'savings', 'business') DEFAULT 'checking' COMMENT 'Type de compte',
    balance DECIMAL(15,2) DEFAULT 0 COMMENT 'Solde actuel',
    currency VARCHAR(3) DEFAULT 'EUR' COMMENT 'Devise',
    connection_status ENUM('connected', 'disconnected', 'error') DEFAULT 'disconnected' COMMENT 'Statut de connexion API',
    api_credentials TEXT COMMENT 'Credentials chiffrés pour API bancaire',
    last_sync DATETIME COMMENT 'Dernière synchronisation',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_status (connection_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des transactions bancaires
-- ============================================
CREATE TABLE IF NOT EXISTS erp_bank_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL COMMENT 'Référence au compte bancaire',
    transaction_date DATE NOT NULL COMMENT 'Date de la transaction',
    description TEXT COMMENT 'Libellé de la transaction',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Montant (positif = crédit, négatif = débit)',
    balance_after DECIMAL(15,2) COMMENT 'Solde après transaction',
    category VARCHAR(100) COMMENT 'Catégorie comptable',
    transaction_type ENUM('debit', 'credit', 'transfer') NOT NULL COMMENT 'Type de transaction',
    reference_number VARCHAR(100) COMMENT 'Numéro de référence bancaire',
    vendor_name VARCHAR(255) COMMENT 'Nom du fournisseur/client',
    is_reconciled BOOLEAN DEFAULT FALSE COMMENT 'Transaction rapprochée',
    metadata JSON COMMENT 'Données supplémentaires',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES erp_bank_accounts(id) ON DELETE CASCADE,
    INDEX idx_account_date (account_id, transaction_date),
    INDEX idx_category (category),
    INDEX idx_date (transaction_date),
    INDEX idx_reconciled (is_reconciled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des documents scannés (OCR/AI)
-- ============================================
CREATE TABLE IF NOT EXISTS erp_scanned_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL COMMENT 'Nom du fichier original',
    file_path VARCHAR(500) NOT NULL COMMENT 'Chemin de stockage',
    file_type VARCHAR(50) COMMENT 'Extension (pdf, jpg, xlsx, etc.)',
    file_size INT COMMENT 'Taille en octets',
    document_type ENUM('invoice', 'receipt', 'bank_statement', 'payslip', 'contract', 'other') DEFAULT 'other' COMMENT 'Type de document',
    document_date DATE COMMENT 'Date du document',
    amount DECIMAL(15,2) COMMENT 'Montant extrait',
    currency VARCHAR(3) DEFAULT 'EUR',
    vendor_name VARCHAR(255) COMMENT 'Fournisseur/Client extrait',
    invoice_number VARCHAR(100) COMMENT 'Numéro de facture extrait',
    extracted_data JSON COMMENT 'Toutes les données extraites par OCR/AI',
    status ENUM('pending', 'processing', 'processed', 'error') DEFAULT 'pending' COMMENT 'Statut du traitement',
    confidence_score DECIMAL(5,2) COMMENT 'Score de confiance OCR (0-100)',
    error_message TEXT COMMENT 'Message d\'erreur si échec',
    processed_at DATETIME COMMENT 'Date de traitement',
    linked_transaction_id INT COMMENT 'Transaction bancaire liée',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_type (document_type),
    INDEX idx_date (document_date),
    INDEX idx_vendor (vendor_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des catégories comptables
-- ============================================
CREATE TABLE IF NOT EXISTS erp_accounting_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    name VARCHAR(100) NOT NULL COMMENT 'Nom de la catégorie',
    category_type ENUM('income', 'expense', 'asset', 'liability', 'equity') NOT NULL COMMENT 'Type comptable',
    code VARCHAR(20) COMMENT 'Code comptable (ex: 60110)',
    parent_id INT COMMENT 'Catégorie parente pour hiérarchie',
    description TEXT COMMENT 'Description de la catégorie',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Catégorie active',
    color VARCHAR(7) COMMENT 'Couleur hex pour affichage',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_type (category_type),
    INDEX idx_active (is_active),
    FOREIGN KEY (parent_id) REFERENCES erp_accounting_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des bilans comptables générés
-- ============================================
CREATE TABLE IF NOT EXISTS erp_financial_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    report_type ENUM('balance_sheet', 'income_statement', 'cash_flow', 'trial_balance', 'custom') NOT NULL COMMENT 'Type de bilan',
    period_start DATE NOT NULL COMMENT 'Début de période',
    period_end DATE NOT NULL COMMENT 'Fin de période',
    title VARCHAR(255) COMMENT 'Titre du rapport',
    file_path VARCHAR(500) COMMENT 'Chemin du PDF généré',
    data JSON COMMENT 'Données du rapport',
    total_income DECIMAL(15,2) COMMENT 'Total revenus',
    total_expenses DECIMAL(15,2) COMMENT 'Total dépenses',
    net_result DECIMAL(15,2) COMMENT 'Résultat net',
    generated_by INT COMMENT 'ID utilisateur',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_type (report_type),
    INDEX idx_period (period_start, period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insertion de catégories comptables par défaut
-- ============================================
-- Note: Ces catégories seront insérées pour chaque customer_id
-- Vous devrez adapter le customer_id selon votre besoin

-- Catégories de revenus
INSERT INTO erp_accounting_categories (customer_id, name, category_type, code, description, color) VALUES
(1, 'Ventes de produits', 'income', '70100', 'Ventes de biens et produits', '#10b981'),
(1, 'Prestations de services', 'income', '70600', 'Services rendus', '#10b981'),
(1, 'Subventions', 'income', '74000', 'Subventions d\'exploitation', '#10b981'),
(1, 'Autres revenus', 'income', '75000', 'Autres produits de gestion courante', '#10b981');

-- Catégories de dépenses
INSERT INTO erp_accounting_categories (customer_id, name, category_type, code, description, color) VALUES
(1, 'Achats de marchandises', 'expense', '60100', 'Achats de biens pour revente', '#ef4444'),
(1, 'Achats de matières', 'expense', '60200', 'Matières premières et fournitures', '#ef4444'),
(1, 'Sous-traitance', 'expense', '60400', 'Achats d\'études et prestations', '#ef4444'),
(1, 'Fournitures', 'expense', '60600', 'Fournitures non stockées', '#ef4444'),
(1, 'Loyer', 'expense', '61300', 'Locations immobilières', '#ef4444'),
(1, 'Entretien et réparations', 'expense', '61500', 'Entretien et réparations', '#ef4444'),
(1, 'Assurances', 'expense', '61600', 'Primes d\'assurance', '#ef4444'),
(1, 'Documentation', 'expense', '61800', 'Documentation générale', '#ef4444'),
(1, 'Personnel - Salaires', 'expense', '64100', 'Rémunérations du personnel', '#ef4444'),
(1, 'Personnel - Charges sociales', 'expense', '64500', 'Charges de sécurité sociale', '#ef4444'),
(1, 'Honoraires', 'expense', '62200', 'Rémunérations d\'intermédiaires', '#ef4444'),
(1, 'Publicité', 'expense', '62300', 'Publicité et communication', '#ef4444'),
(1, 'Télécommunications', 'expense', '62600', 'Frais postaux et télécommunications', '#ef4444'),
(1, 'Services bancaires', 'expense', '62700', 'Services bancaires et assimilés', '#ef4444'),
(1, 'Impôts et taxes', 'expense', '63500', 'Impôts, taxes et versements assimilés', '#ef4444');

-- Catégories d'actifs
INSERT INTO erp_accounting_categories (customer_id, name, category_type, code, description, color) VALUES
(1, 'Matériel informatique', 'asset', '21830', 'Matériel informatique', '#3b82f6'),
(1, 'Mobilier', 'asset', '21840', 'Mobilier de bureau', '#3b82f6'),
(1, 'Véhicules', 'asset', '21820', 'Matériel de transport', '#3b82f6'),
(1, 'Clients', 'asset', '41100', 'Créances clients', '#3b82f6'),
(1, 'Banque', 'asset', '51200', 'Comptes bancaires', '#3b82f6'),
(1, 'Caisse', 'asset', '53000', 'Caisse', '#3b82f6');

-- Catégories de passifs
INSERT INTO erp_accounting_categories (customer_id, name, category_type, code, description, color) VALUES
(1, 'Fournisseurs', 'liability', '40100', 'Dettes fournisseurs', '#f59e0b'),
(1, 'Charges sociales à payer', 'liability', '43000', 'Sécurité sociale et autres', '#f59e0b'),
(1, 'TVA à payer', 'liability', '44571', 'TVA collectée', '#f59e0b'),
(1, 'Emprunts bancaires', 'liability', '16400', 'Emprunts auprès des établissements de crédit', '#f59e0b');

-- Catégories de capitaux propres
INSERT INTO erp_accounting_categories (customer_id, name, category_type, code, description, color) VALUES
(1, 'Capital social', 'equity', '10100', 'Capital', '#8b5cf6'),
(1, 'Résultat de l\'exercice', 'equity', '12000', 'Résultat de l\'exercice', '#8b5cf6');

-- ============================================
-- Fin de la migration
-- ============================================

SELECT 'Migration 003 terminée - Tables de comptabilité créées avec succès' AS status;
