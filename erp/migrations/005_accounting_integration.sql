-- Migration 005: Intégration comptabilité automatique
-- Date: 20 février 2026
-- Description: Synchronisation automatique des factures avec la comptabilité

USE webitech_crm;

-- ============================================
-- Trigger: Créer une transaction bancaire lors du paiement d'une facture
-- ============================================
DELIMITER $$

DROP TRIGGER IF EXISTS after_invoice_paid$$

CREATE TRIGGER after_invoice_paid
AFTER UPDATE ON erp_invoices
FOR EACH ROW
BEGIN
    -- Si le statut de paiement passe à 'paid'
    IF NEW.payment_status = 'paid' AND OLD.payment_status != 'paid' THEN
        
        -- Créer une transaction bancaire de crédit (entrée d'argent)
        INSERT INTO erp_bank_transactions (
            account_id,
            transaction_date,
            description,
            amount,
            category,
            transaction_type,
            reference_number,
            vendor_name,
            is_reconciled,
            metadata
        )
        SELECT 
            ba.id as account_id,
            NEW.paid_date as transaction_date,
            CONCAT('Facture ', NEW.invoice_number, ' - ', NEW.client_name) as description,
            NEW.total_ttc as amount,
            'Ventes de produits' as category,
            'credit' as transaction_type,
            NEW.invoice_number as reference_number,
            NEW.client_name as vendor_name,
            TRUE as is_reconciled,
            JSON_OBJECT(
                'invoice_id', NEW.id,
                'invoice_number', NEW.invoice_number,
                'total_ht', NEW.total_ht,
                'total_tva', NEW.total_tva,
                'total_ttc', NEW.total_ttc,
                'payment_method', NEW.payment_method
            ) as metadata
        FROM erp_bank_accounts ba
        WHERE ba.customer_id = NEW.customer_id
          AND ba.account_type = 'business'
          AND ba.is_active = TRUE
        LIMIT 1;
        
    END IF;
END$$

DELIMITER ;

-- ============================================
-- Procédure stockée: Synchroniser toutes les factures payées
-- ============================================
DELIMITER $$

DROP PROCEDURE IF EXISTS sync_paid_invoices_to_accounting$$

CREATE PROCEDURE sync_paid_invoices_to_accounting(IN p_customer_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_invoice_id INT;
    DECLARE v_invoice_number VARCHAR(50);
    DECLARE v_client_name VARCHAR(255);
    DECLARE v_paid_date DATE;
    DECLARE v_total_ttc DECIMAL(15,2);
    DECLARE v_total_ht DECIMAL(15,2);
    DECLARE v_total_tva DECIMAL(15,2);
    DECLARE v_payment_method VARCHAR(50);
    DECLARE v_account_id INT;
    
    -- Curseur pour les factures payées non synchronisées
    DECLARE cur CURSOR FOR
        SELECT i.id, i.invoice_number, i.client_name, i.paid_date,
               i.total_ttc, i.total_ht, i.total_tva, i.payment_method
        FROM erp_invoices i
        WHERE i.customer_id = p_customer_id
          AND i.payment_status = 'paid'
          AND i.status != 'cancelled'
          AND NOT EXISTS (
              SELECT 1 FROM erp_bank_transactions t
              WHERE t.reference_number = i.invoice_number
          );
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Récupérer le compte bancaire principal
    SELECT id INTO v_account_id
    FROM erp_bank_accounts
    WHERE customer_id = p_customer_id
      AND account_type = 'business'
      AND is_active = TRUE
    LIMIT 1;
    
    IF v_account_id IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Aucun compte bancaire actif trouvé';
    END IF;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_invoice_id, v_invoice_number, v_client_name, v_paid_date,
                       v_total_ttc, v_total_ht, v_total_tva, v_payment_method;
        
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Créer la transaction bancaire
        INSERT INTO erp_bank_transactions (
            account_id,
            transaction_date,
            description,
            amount,
            category,
            transaction_type,
            reference_number,
            vendor_name,
            is_reconciled,
            metadata
        ) VALUES (
            v_account_id,
            v_paid_date,
            CONCAT('Facture ', v_invoice_number, ' - ', v_client_name),
            v_total_ttc,
            'Ventes de produits',
            'credit',
            v_invoice_number,
            v_client_name,
            TRUE,
            JSON_OBJECT(
                'invoice_id', v_invoice_id,
                'invoice_number', v_invoice_number,
                'total_ht', v_total_ht,
                'total_tva', v_total_tva,
                'total_ttc', v_total_ttc,
                'payment_method', v_payment_method
            )
        );
        
    END LOOP;
    
    CLOSE cur;
    
    SELECT ROW_COUNT() as transactions_created;
END$$

DELIMITER ;

-- ============================================
-- Vue: Chiffre d'affaires par période (pour comptabilité)
-- ============================================
CREATE OR REPLACE VIEW erp_accounting_revenue AS
SELECT 
    i.customer_id,
    DATE_FORMAT(i.paid_date, '%Y-%m') as period,
    DATE_FORMAT(i.paid_date, '%Y') as fiscal_year,
    QUARTER(i.paid_date) as fiscal_quarter,
    COUNT(*) as invoice_count,
    SUM(i.total_ht) as total_revenue_ht,
    SUM(i.total_tva) as total_vat_collected,
    SUM(i.total_ttc) as total_revenue_ttc,
    AVG(i.total_ttc) as average_invoice_amount,
    GROUP_CONCAT(DISTINCT i.invoice_number SEPARATOR ', ') as invoice_numbers
FROM erp_invoices i
WHERE i.payment_status = 'paid'
  AND i.status != 'cancelled'
GROUP BY i.customer_id, 
         DATE_FORMAT(i.paid_date, '%Y-%m'),
         DATE_FORMAT(i.paid_date, '%Y'),
         QUARTER(i.paid_date);

-- ============================================
-- Vue: TVA collectée par taux (déclaration TVA)
-- ============================================
CREATE OR REPLACE VIEW erp_vat_declaration AS
SELECT 
    i.customer_id,
    DATE_FORMAT(i.paid_date, '%Y-%m') as period,
    JSON_UNQUOTE(JSON_EXTRACT(vat_detail, '$.rate')) as vat_rate,
    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(vat_detail, '$.base_ht')) AS DECIMAL(15,2))) as total_base_ht,
    SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(vat_detail, '$.amount')) AS DECIMAL(15,2))) as total_vat_amount,
    COUNT(DISTINCT i.id) as invoice_count
FROM erp_invoices i
CROSS JOIN JSON_TABLE(
    i.vat_details,
    '$[*]' COLUMNS(
        vat_detail JSON PATH '$'
    )
) as vat_breakdown
WHERE i.payment_status = 'paid'
  AND i.status != 'cancelled'
  AND i.paid_date IS NOT NULL
GROUP BY i.customer_id, 
         DATE_FORMAT(i.paid_date, '%Y-%m'),
         JSON_UNQUOTE(JSON_EXTRACT(vat_detail, '$.rate'));

-- ============================================
-- Fonction: Calculer le chiffre d'affaires annuel
-- ============================================
DELIMITER $$

DROP FUNCTION IF EXISTS get_annual_revenue$$

CREATE FUNCTION get_annual_revenue(p_customer_id INT, p_year INT)
RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_revenue DECIMAL(15,2);
    
    SELECT COALESCE(SUM(total_ttc), 0) INTO v_revenue
    FROM erp_invoices
    WHERE customer_id = p_customer_id
      AND YEAR(paid_date) = p_year
      AND payment_status = 'paid'
      AND status != 'cancelled';
    
    RETURN v_revenue;
END$$

DELIMITER ;

-- ============================================
-- Fonction: Calculer la TVA collectée sur une période
-- ============================================
DELIMITER $$

DROP FUNCTION IF EXISTS get_vat_collected$$

CREATE FUNCTION get_vat_collected(p_customer_id INT, p_start_date DATE, p_end_date DATE)
RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_vat DECIMAL(15,2);
    
    SELECT COALESCE(SUM(total_tva), 0) INTO v_vat
    FROM erp_invoices
    WHERE customer_id = p_customer_id
      AND paid_date BETWEEN p_start_date AND p_end_date
      AND payment_status = 'paid'
      AND status != 'cancelled';
    
    RETURN v_vat;
END$$

DELIMITER ;

-- ============================================
-- Table de liaison: Factures <-> Documents scannés
-- ============================================
CREATE TABLE IF NOT EXISTS erp_invoice_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL COMMENT 'Référence à la facture',
    document_id INT NOT NULL COMMENT 'Référence au document scanné',
    document_type ENUM('proof_of_delivery', 'purchase_order', 'contract', 'other') DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_invoice_document (invoice_id, document_id),
    INDEX idx_invoice (invoice_id),
    INDEX idx_document (document_id),
    
    FOREIGN KEY (invoice_id) REFERENCES erp_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES erp_scanned_documents(id) ON DELETE CASCADE
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Liaison entre factures et documents justificatifs';

-- ============================================
-- Index pour améliorer les performances des requêtes comptables
-- ============================================
ALTER TABLE erp_invoices 
    ADD INDEX idx_paid_date (paid_date),
    ADD INDEX idx_customer_paid (customer_id, paid_date, payment_status);

ALTER TABLE erp_bank_transactions
    ADD INDEX idx_reference (reference_number),
    ADD INDEX idx_category_date (category, transaction_date);

-- ============================================
-- Événement: Mettre à jour les factures en retard (exécution quotidienne)
-- ============================================
SET GLOBAL event_scheduler = ON;

DELIMITER $$

DROP EVENT IF EXISTS update_overdue_invoices_daily$$

CREATE EVENT update_overdue_invoices_daily
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    -- Marquer les factures en retard
    UPDATE erp_invoices
    SET payment_status = 'overdue'
    WHERE payment_status IN ('unpaid', 'partial')
      AND due_date < CURDATE()
      AND status NOT IN ('cancelled', 'archived');
      
    -- Logger dans une table d'audit si souhaité
    INSERT INTO erp_invoice_audit (invoice_id, action, old_values, new_values)
    SELECT 
        id,
        'updated',
        JSON_OBJECT('payment_status', 'unpaid'),
        JSON_OBJECT('payment_status', 'overdue', 'updated_by', 'system_cron')
    FROM erp_invoices
    WHERE payment_status = 'overdue'
      AND updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);
END$$

DELIMITER ;

-- ============================================
-- Exemples de requêtes comptables utiles
-- ============================================

-- Chiffre d'affaires mensuel
-- SELECT * FROM erp_accounting_revenue WHERE customer_id = 1 ORDER BY period DESC;

-- Déclaration de TVA
-- SELECT * FROM erp_vat_declaration WHERE customer_id = 1 AND period = '2026-02';

-- Chiffre d'affaires annuel
-- SELECT get_annual_revenue(1, 2026) as ca_2026;

-- TVA collectée sur le trimestre
-- SELECT get_vat_collected(1, '2026-01-01', '2026-03-31') as tva_q1;

-- Factures en attente de paiement
-- SELECT invoice_number, client_name, total_ttc, due_date,
--        DATEDIFF(CURDATE(), due_date) as days_overdue
-- FROM erp_invoices
-- WHERE customer_id = 1
--   AND payment_status IN ('unpaid', 'partial', 'overdue')
--   AND status != 'cancelled'
-- ORDER BY due_date;

-- Top 10 clients par CA
-- SELECT client_name, 
--        COUNT(*) as invoice_count,
--        SUM(total_ttc) as total_revenue
-- FROM erp_invoices
-- WHERE customer_id = 1
--   AND payment_status = 'paid'
--   AND YEAR(paid_date) = YEAR(CURDATE())
-- GROUP BY client_name
-- ORDER BY total_revenue DESC
-- LIMIT 10;

-- ============================================
-- Fin de la migration
-- ============================================

SELECT 
    'Migration 005 terminée' AS status,
    'Intégration comptabilité automatique activée' AS message,
    (SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = 'webitech_crm' AND TRIGGER_NAME LIKE '%invoice%') AS triggers_count,
    (SELECT COUNT(*) FROM information_schema VIEWS WHERE TABLE_SCHEMA = 'webitech_crm' AND TABLE_NAME LIKE 'erp_%accounting%') AS views_count;
