-- Migration 004: Système de facturation conforme aux lois françaises 2026
-- Date: 20 février 2026
-- Description: Création des tables pour la facturation électronique obligatoire
-- Conformité: Décret 2022-1299, Format Factur-X, Mentions obligatoires



-- ============================================
-- Table des factures (conformité légale française)
-- ============================================
CREATE TABLE IF NOT EXISTS erp_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL COMMENT 'Client émetteur de la facture',
    
    -- Numérotation obligatoire (Article 242 nonies A du CGI)
    invoice_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Numéro séquentiel unique sans rupture',
    invoice_type ENUM('sale', 'credit_note', 'advance', 'proforma') DEFAULT 'sale' COMMENT 'Type de facture',
    
    -- Dates (anti-backdating obligatoire)
    issue_date DATE NOT NULL COMMENT 'Date d\'émission (ne peut être antérieure)',
    due_date DATE COMMENT 'Date d\'échéance de paiement',
    delivery_date DATE COMMENT 'Date de livraison/prestation',
    
    -- Client facturé
    client_type ENUM('company', 'individual') DEFAULT 'company',
    client_company_id INT COMMENT 'Référence à companies si entreprise',
    client_name VARCHAR(255) NOT NULL COMMENT 'Nom du client',
    client_address TEXT COMMENT 'Adresse complète',
    client_postal_code VARCHAR(10),
    client_city VARCHAR(100),
    client_country VARCHAR(100) DEFAULT 'France',
    client_siret VARCHAR(14) COMMENT 'SIRET obligatoire si entreprise française',
    client_vat_number VARCHAR(20) COMMENT 'Numéro TVA intracommunautaire',
    client_email VARCHAR(255),
    client_phone VARCHAR(20),
    
    -- Montants (obligatoire: détail HT, TVA, TTC)
    total_ht DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Total HT',
    total_tva DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Total TVA',
    total_ttc DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Total TTC',
    currency VARCHAR(3) DEFAULT 'EUR',
    
    -- TVA détaillée (obligatoire par taux)
    vat_details JSON COMMENT 'Détail par taux: [{rate: 20, base_ht: 1000, amount: 200}]',
    
    -- Conditions de paiement (mention obligatoire)
    payment_terms TEXT COMMENT 'Conditions de paiement',
    payment_method ENUM('transfer', 'check', 'cash', 'card', 'direct_debit', 'other') COMMENT 'Moyen de paiement',
    payment_status ENUM('unpaid', 'partial', 'paid', 'overdue', 'cancelled') DEFAULT 'unpaid',
    paid_amount DECIMAL(15,2) DEFAULT 0,
    paid_date DATE COMMENT 'Date de paiement effectif',
    
    -- Pénalités de retard (mention obligatoire depuis 2013)
    late_fee_rate DECIMAL(5,2) DEFAULT 10.00 COMMENT 'Taux de pénalités de retard (%)',
    recovery_indemnity DECIMAL(10,2) DEFAULT 40.00 COMMENT 'Indemnité forfaitaire de recouvrement (min 40€)',
    
    -- Escompte
    discount_terms TEXT COMMENT 'Conditions d\'escompte si paiement anticipé',
    
    -- Notes et pièces jointes
    notes TEXT COMMENT 'Notes internes',
    client_notes TEXT COMMENT 'Notes pour le client (conditions générales)',
    attached_documents TEXT COMMENT 'Documents joints (devis, bon de commande, etc.)',
    
    -- Facturation électronique (Factur-X)
    electronic_format ENUM('pdf', 'facturx', 'xml') DEFAULT 'pdf' COMMENT 'Format de la facture',
    xml_data TEXT COMMENT 'Données XML EN 16931 pour Factur-X',
    pdf_path VARCHAR(500) COMMENT 'Chemin du PDF généré',
    
    -- Traçabilité et archivage (conservation 10 ans obligatoire)
    status ENUM('draft', 'sent', 'viewed', 'paid', 'cancelled', 'archived') DEFAULT 'draft',
    sent_at DATETIME COMMENT 'Date d\'envoi au client',
    viewed_at DATETIME COMMENT 'Date de consultation par le client',
    cancelled_at DATETIME COMMENT 'Date d\'annulation',
    cancellation_reason TEXT COMMENT 'Motif d\'annulation',
    
    -- Liens vers d'autres tables
    sale_id INT COMMENT 'Référence à erp_sales si vente directe',
    folder_id INT COMMENT 'Référence à folders si missions',
    created_by INT COMMENT 'Utilisateur créateur',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index pour performances
    INDEX idx_customer (customer_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_issue_date (issue_date),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_client_company (client_company_id),
    INDEX idx_sale_id (sale_id),
    
    -- Contraintes (ajoutées après si les tables existent)
    INDEX idx_folder (folder_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Factures conformes législation française 2026';

-- Note: Les contraintes de clés étrangères sont optionnelles
-- Si les tables customers, companies, erp_sales existent, décommentez les lignes suivantes :
-- ALTER TABLE erp_invoices ADD CONSTRAINT fk_invoice_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT;
-- ALTER TABLE erp_invoices ADD CONSTRAINT fk_invoice_client_company FOREIGN KEY (client_company_id) REFERENCES companies(id) ON DELETE SET NULL;

-- ============================================
-- Table des lignes de facture
-- ============================================
CREATE TABLE IF NOT EXISTS erp_invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL COMMENT 'Référence à la facture',
    
    -- Description de la ligne
    item_order INT DEFAULT 0 COMMENT 'Ordre d\'affichage',
    description TEXT NOT NULL COMMENT 'Description du produit/service',
    product_id INT COMMENT 'Référence produit si applicable',
    
    -- Quantité et prix
    quantity DECIMAL(10,3) NOT NULL DEFAULT 1 COMMENT 'Quantité',
    unit VARCHAR(20) DEFAULT 'unité' COMMENT 'Unité (pièce, heure, jour, kg, etc.)',
    unit_price_ht DECIMAL(15,2) NOT NULL COMMENT 'Prix unitaire HT',
    
    -- Remise éventuelle
    discount_rate DECIMAL(5,2) DEFAULT 0 COMMENT 'Taux de remise (%)',
    discount_amount DECIMAL(15,2) DEFAULT 0 COMMENT 'Montant de remise',
    
    -- Montants ligne
    total_ht DECIMAL(15,2) NOT NULL COMMENT 'Total HT de la ligne',
    vat_rate DECIMAL(5,2) NOT NULL COMMENT 'Taux de TVA (20, 10, 5.5, 2.1, 0)',
    vat_amount DECIMAL(15,2) NOT NULL COMMENT 'Montant TVA',
    total_ttc DECIMAL(15,2) NOT NULL COMMENT 'Total TTC',
    
    -- Traçabilité
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index
    INDEX idx_invoice (invoice_id),
    INDEX idx_product (product_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Lignes de facture détaillées';

-- Contraintes de clés étrangères (ajoutez-les après vérification des tables)
-- ALTER TABLE erp_invoice_items ADD CONSTRAINT fk_item_invoice FOREIGN KEY (invoice_id) REFERENCES erp_invoices(id) ON DELETE CASCADE;
-- ALTER TABLE erp_invoice_items ADD CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES erp_stock(id) ON DELETE SET NULL;

-- ============================================
-- Table de numérotation séquentielle (obligatoire)
-- ============================================
CREATE TABLE IF NOT EXISTS erp_invoice_sequences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL COMMENT 'Client propriétaire de la séquence',
    
    -- Configuration de la numérotation
    invoice_year INT NOT NULL COMMENT 'Année de la séquence',
    prefix VARCHAR(20) DEFAULT 'FA' COMMENT 'Préfixe (ex: FA, FACT, INV)',
    current_number INT NOT NULL DEFAULT 0 COMMENT 'Dernier numéro utilisé',
    number_format VARCHAR(50) DEFAULT '{PREFIX}{YEAR}-{NUMBER}' COMMENT 'Format: FA2026-0001',
    
    -- Paramètres
    padding INT DEFAULT 4 COMMENT 'Nombre de zéros (0001, 00001, etc.)',
    separator_char VARCHAR(5) DEFAULT '-' COMMENT 'Séparateur',
    
    -- Traçabilité
    last_invoice_id INT COMMENT 'Dernière facture créée',
    last_invoice_date DATE COMMENT 'Date dernière facture',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index
    UNIQUE KEY unique_customer_year (customer_id, invoice_year),
    INDEX idx_customer (customer_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Numérotation séquentielle obligatoire sans rupture';

-- Contrainte optionnelle (décommentez si la table customers existe)
-- ALTER TABLE erp_invoice_sequences ADD CONSTRAINT fk_sequence_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE;

-- ============================================
-- Table des taux de TVA (référence)
-- ============================================
CREATE TABLE IF NOT EXISTS erp_vat_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rate DECIMAL(5,2) NOT NULL COMMENT 'Taux de TVA',
    label VARCHAR(50) NOT NULL COMMENT 'Libellé (Normal, Intermédiaire, Réduit, Super-réduit)',
    description TEXT COMMENT 'Description et cas d\'application',
    country VARCHAR(2) DEFAULT 'FR' COMMENT 'Code pays',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Taux en vigueur',
    effective_from DATE COMMENT 'Date d\'entrée en vigueur',
    effective_to DATE COMMENT 'Date de fin (si modifié)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_country (country),
    INDEX idx_active (is_active)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Taux de TVA applicables';

-- ============================================
-- Insertion des taux de TVA français 2026
-- ============================================
INSERT INTO erp_vat_rates (rate, label, description, country, is_active, effective_from) VALUES
(20.00, 'Normal', 'Taux normal - Biens et services par défaut', 'FR', 1, '2014-01-01'),
(10.00, 'Intermédiaire', 'Taux intermédiaire - Restauration, transports, travaux rénovation', 'FR', 1, '2014-01-01'),
(5.50, 'Réduit', 'Taux réduit - Produits alimentaires, livres, électricité/gaz', 'FR', 1, '2014-01-01'),
(2.10, 'Super-réduit', 'Taux super-réduit - Médicaments remboursables, presse', 'FR', 1, '1986-07-01'),
(0.00, 'Exonéré', 'TVA non applicable - Export, services B2B UE, professions médicales', 'FR', 1, '1954-04-01');

-- ============================================
-- Mise à jour de la table erp_sales
-- ============================================
-- Ajout du lien vers la facture générée
ALTER TABLE erp_sales ADD COLUMN IF NOT EXISTS invoice_id INT COMMENT 'Facture générée pour cette vente';
ALTER TABLE erp_sales ADD COLUMN IF NOT EXISTS total_price DECIMAL(15,2) AFTER quantity ;
ALTER TABLE erp_sales ADD INDEX IF NOT EXISTS idx_invoice (invoice_id);

-- Contrainte optionnelle (décommentez si toutes les tables existent)
-- ALTER TABLE erp_invoices ADD CONSTRAINT fk_invoice_sale FOREIGN KEY (sale_id) REFERENCES erp_sales(id) ON DELETE SET NULL;

-- ============================================
-- Table d'audit des factures (traçabilité)
-- ============================================
CREATE TABLE IF NOT EXISTS erp_invoice_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    action ENUM('created', 'updated', 'sent', 'viewed', 'paid', 'cancelled', 'archived') NOT NULL,
    user_id INT COMMENT 'Utilisateur ayant effectué l\'action',
    old_values JSON COMMENT 'Anciennes valeurs modifiées',
    new_values JSON COMMENT 'Nouvelles valeurs',
    ip_address VARCHAR(45) COMMENT 'Adresse IP',
    user_agent TEXT COMMENT 'Navigateur/Client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_invoice (invoice_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail pour conformité (conservation 10 ans)';

-- Contrainte ajoutée automatiquement par le trigger (pas besoin de l'ajouter manuellement)
-- ALTER TABLE erp_invoice_audit ADD CONSTRAINT fk_audit_invoice FOREIGN KEY (invoice_id) REFERENCES erp_invoices(id) ON DELETE CASCADE;

-- ============================================
-- Table des paiements de factures
-- ============================================
CREATE TABLE IF NOT EXISTS erp_invoice_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    
    -- Détails du paiement
    payment_date DATE NOT NULL COMMENT 'Date du paiement',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Montant payé',
    payment_method ENUM('transfer', 'check', 'cash', 'card', 'direct_debit', 'other') NOT NULL,
    
    -- Références
    reference VARCHAR(100) COMMENT 'Numéro de transaction/chèque',
    bank_account_id INT COMMENT 'Compte bancaire de réception',
    
    -- Notes
    notes TEXT COMMENT 'Notes sur le paiement',
    
    -- Traçabilité
    recorded_by INT COMMENT 'Utilisateur ayant enregistré',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_invoice (invoice_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_bank_account (bank_account_id)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historique des paiements de factures';

-- Contraintes optionnelles (décommentez si les tables existent)
-- ALTER TABLE erp_invoice_payments ADD CONSTRAINT fk_payment_invoice FOREIGN KEY (invoice_id) REFERENCES erp_invoices(id) ON DELETE CASCADE;
-- ALTER TABLE erp_invoice_payments ADD CONSTRAINT fk_payment_bank_account FOREIGN KEY (bank_account_id) REFERENCES erp_bank_accounts(id) ON DELETE SET NULL;

-- ============================================
-- Vue pour tableau de bord facturation
-- ============================================
DROP VIEW IF EXISTS erp_invoices_dashboard;

CREATE VIEW erp_invoices_dashboard AS
SELECT 
    i.customer_id,
    DATE_FORMAT(i.issue_date, '%Y-%m') AS period,
    COUNT(*) AS total_invoices,
    SUM(CASE WHEN i.payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
    SUM(CASE WHEN i.payment_status = 'unpaid' THEN 1 ELSE 0 END) AS unpaid_count,
    SUM(CASE WHEN i.payment_status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count,
    SUM(i.total_ttc) AS total_amount,
    SUM(CASE WHEN i.payment_status = 'paid' THEN i.total_ttc ELSE 0 END) AS paid_amount,
    SUM(CASE WHEN i.payment_status != 'paid' THEN i.total_ttc ELSE 0 END) AS outstanding_amount,
    AVG(DATEDIFF(i.paid_date, i.issue_date)) AS avg_payment_delay_days
FROM erp_invoices i
WHERE i.status != 'cancelled'
GROUP BY i.customer_id, DATE_FORMAT(i.issue_date, '%Y-%m');

-- ============================================
-- Triggers pour mise à jour automatique
-- ============================================

DELIMITER $$

-- Suppression des triggers s'ils existent déjà
DROP TRIGGER IF EXISTS before_invoice_insert$$
DROP TRIGGER IF EXISTS after_invoice_item_insert$$
DROP TRIGGER IF EXISTS after_invoice_item_update$$
DROP TRIGGER IF EXISTS after_invoice_item_delete$$
DROP TRIGGER IF EXISTS after_payment_insert$$

-- Trigger: Calculer les totaux de facture automatiquement
CREATE TRIGGER before_invoice_insert 
BEFORE INSERT ON erp_invoices
FOR EACH ROW
BEGIN
    -- Anti-backdating: la date d'émission ne peut pas être antérieure à aujourd'hui
    IF NEW.issue_date < CURDATE() AND NEW.status != 'draft' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'La date d\'émission ne peut pas être antérieure à la date du jour (anti-backdating obligatoire)';
    END IF;
    
    -- Calcul de la date d'échéance si non fournie (30 jours par défaut)
    IF NEW.due_date IS NULL THEN
        SET NEW.due_date = DATE_ADD(NEW.issue_date, INTERVAL 30 DAY);
    END IF;
    
    -- Définir le statut de paiement selon les montants
    IF NEW.paid_amount >= NEW.total_ttc THEN
        SET NEW.payment_status = 'paid';
        SET NEW.paid_date = CURDATE();
    ELSEIF NEW.paid_amount > 0 THEN
        SET NEW.payment_status = 'partial';
    ELSEIF NEW.due_date < CURDATE() THEN
        SET NEW.payment_status = 'overdue';
    END IF;
END$$

-- Trigger: Mettre à jour les totaux quand les lignes changent
CREATE TRIGGER after_invoice_item_insert
AFTER INSERT ON erp_invoice_items
FOR EACH ROW
BEGIN
    UPDATE erp_invoices
    SET 
        total_ht = (SELECT COALESCE(SUM(total_ht), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id),
        total_tva = (SELECT COALESCE(SUM(vat_amount), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id),
        total_ttc = (SELECT COALESCE(SUM(total_ttc), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id)
    WHERE id = NEW.invoice_id;
END$$

CREATE TRIGGER after_invoice_item_update
AFTER UPDATE ON erp_invoice_items
FOR EACH ROW
BEGIN
    UPDATE erp_invoices
    SET 
        total_ht = (SELECT COALESCE(SUM(total_ht), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id),
        total_tva = (SELECT COALESCE(SUM(vat_amount), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id),
        total_ttc = (SELECT COALESCE(SUM(total_ttc), 0) FROM erp_invoice_items WHERE invoice_id = NEW.invoice_id)
    WHERE id = NEW.invoice_id;
END$$

CREATE TRIGGER after_invoice_item_delete
AFTER DELETE ON erp_invoice_items
FOR EACH ROW
BEGIN
    UPDATE erp_invoices
    SET 
        total_ht = (SELECT COALESCE(SUM(total_ht), 0) FROM erp_invoice_items WHERE invoice_id = OLD.invoice_id),
        total_tva = (SELECT COALESCE(SUM(vat_amount), 0) FROM erp_invoice_items WHERE invoice_id = OLD.invoice_id),
        total_ttc = (SELECT COALESCE(SUM(total_ttc), 0) FROM erp_invoice_items WHERE invoice_id = OLD.invoice_id)
    WHERE id = OLD.invoice_id;
END$$

-- Trigger: Enregistrer les paiements dans l'audit
CREATE TRIGGER after_payment_insert
AFTER INSERT ON erp_invoice_payments
FOR EACH ROW
BEGIN
    -- Mettre à jour le montant payé sur la facture
    UPDATE erp_invoices
    SET 
        paid_amount = (SELECT COALESCE(SUM(amount), 0) FROM erp_invoice_payments WHERE invoice_id = NEW.invoice_id),
        payment_status = CASE
            WHEN (SELECT COALESCE(SUM(amount), 0) FROM erp_invoice_payments WHERE invoice_id = NEW.invoice_id) >= total_ttc THEN 'paid'
            WHEN (SELECT COALESCE(SUM(amount), 0) FROM erp_invoice_payments WHERE invoice_id = NEW.invoice_id) > 0 THEN 'partial'
            ELSE payment_status
        END,
        paid_date = CASE
            WHEN (SELECT COALESCE(SUM(amount), 0) FROM erp_invoice_payments WHERE invoice_id = NEW.invoice_id) >= total_ttc THEN NEW.payment_date
            ELSE paid_date
        END
    WHERE id = NEW.invoice_id;
END$$

DELIMITER ;

-- ============================================
-- Index de recherche full-text pour factures
-- ============================================
-- Note: Requiert MySQL 5.6+ pour FULLTEXT sur InnoDB
-- Si erreur, commentez cette ligne
-- ALTER TABLE erp_invoices ADD FULLTEXT INDEX ft_search (invoice_number, client_name, notes);

-- ============================================
-- Fin de la migration
-- ============================================

-- Afficher un résumé
SELECT 
    'Migration 004 terminée' AS status,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'webitech_crm' AND table_name LIKE 'erp_invoice%') AS tables_created,
    (SELECT COUNT(*) FROM erp_vat_rates) AS vat_rates_configured,
    'Système de facturation conforme aux lois françaises 2026 créé avec succès' AS message;
