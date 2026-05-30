-- Migration ERP v2.0 - Synchronisation avec CRM
-- Date: Février 2026
-- Description: Mise à jour de la base de données pour supporter la synchronisation ERP ↔ CRM

-- ============================================
-- 1. Vérifier et créer les tables manquantes
-- ============================================

-- Table des shifts (si elle n'existe pas déjà)
CREATE TABLE IF NOT EXISTS `erp_shifts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employee_id` INT(11) DEFAULT NULL,
  `company_id` INT(11) DEFAULT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL,
  `role` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_start_time` (`start_time`),
  CONSTRAINT `fk_shifts_employee` FOREIGN KEY (`employee_id`) REFERENCES `erp_employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_shifts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des ventes (si elle n'existe pas déjà)
CREATE TABLE IF NOT EXISTS `erp_sales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sale_date` DATE NOT NULL,
  `customer_name` VARCHAR(200) NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sale_date` (`sale_date`),
  KEY `idx_customer` (`customer_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. Ajouter des colonnes de synchronisation
-- ============================================

-- Ajouter colonne sync_source pour tracer l'origine des données
ALTER TABLE `erp_shifts` 
ADD COLUMN IF NOT EXISTS `sync_source` ENUM('erp', 'crm', 'manual') DEFAULT 'manual' AFTER `notes`,
ADD COLUMN IF NOT EXISTS `mission_id` INT(11) DEFAULT NULL AFTER `sync_source`,
ADD KEY IF NOT EXISTS `idx_mission` (`mission_id`);

-- Ajouter colonne sync_status pour le statut de synchronisation
ALTER TABLE `erp_shifts` 
ADD COLUMN IF NOT EXISTS `sync_status` ENUM('pending', 'synced', 'error') DEFAULT 'synced' AFTER `sync_source`,
ADD COLUMN IF NOT EXISTS `last_sync_at` TIMESTAMP NULL DEFAULT NULL AFTER `sync_status`;

-- ============================================
-- 3. Vue consolidée Missions + Shifts
-- ============================================

-- Vue pour voir missions et shifts ensemble
CREATE OR REPLACE VIEW `v_erp_planning_consolidated` AS
SELECT 
    'shift' AS type,
    s.id,
    s.employee_id,
    s.company_id,
    s.start_time AS datetime,
    s.end_time,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    c.name AS company_name,
    s.role,
    s.notes,
    s.sync_source,
    s.mission_id,
    NULL AS departure,
    NULL AS arrival,
    NULL AS driver,
    NULL AS vehicle,
    NULL AS status_name,
    s.created_at,
    s.updated_at
FROM erp_shifts s
LEFT JOIN erp_employees e ON s.employee_id = e.id
LEFT JOIN companies c ON s.company_id = c.id

UNION ALL

SELECT 
    'mission' AS type,
    m.id,
    NULL AS employee_id,
    f.company_id,
    m.datetime,
    NULL AS end_time,
    NULL AS employee_name,
    co.name AS company_name,
    NULL AS role,
    m.notes,
    'crm' AS sync_source,
    m.id AS mission_id,
    m.departure,
    m.arrival,
    m.driver,
    m.vehicle,
    st.name AS status_name,
    m.created_at,
    m.updated_at
FROM missions m
INNER JOIN folders f ON m.folder_id = f.id
INNER JOIN companies co ON f.company_id = co.id
LEFT JOIN statuses st ON m.status_id = st.id;

-- ============================================
-- 4. Table de logs de synchronisation
-- ============================================

CREATE TABLE IF NOT EXISTS `erp_sync_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `sync_type` ENUM('missions', 'shifts', 'companies', 'sales', 'stats') NOT NULL,
  `direction` ENUM('crm_to_erp', 'erp_to_crm', 'bidirectional') NOT NULL,
  `customer_id` INT(11) NOT NULL,
  `records_affected` INT(11) DEFAULT 0,
  `status` ENUM('success', 'partial', 'failed') DEFAULT 'success',
  `error_message` TEXT,
  `execution_time_ms` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_sync_type` (`sync_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. Trigger pour auto-sync lors de création mission
-- ============================================

-- Trigger pour logger les nouvelles missions
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS `trg_mission_created` 
AFTER INSERT ON `missions`
FOR EACH ROW
BEGIN
    -- Logger la création pour sync
    INSERT INTO erp_sync_logs (sync_type, direction, customer_id, records_affected, status)
    SELECT 'missions', 'crm_to_erp', c.customer_id, 1, 'success'
    FROM folders f
    INNER JOIN companies c ON f.company_id = c.id
    WHERE f.id = NEW.folder_id;
END$$

CREATE TRIGGER IF NOT EXISTS `trg_shift_created` 
AFTER INSERT ON `erp_shifts`
FOR EACH ROW
BEGIN
    -- Logger la création pour sync
    INSERT INTO erp_sync_logs (sync_type, direction, customer_id, records_affected, status)
    VALUES ('shifts', 'erp_to_crm', 
        IFNULL((SELECT customer_id FROM companies WHERE id = NEW.company_id), 0), 
        1, 'success');
END$$

DELIMITER ;

-- ============================================
-- 6. Procédures stockées utiles
-- ============================================

-- Procédure pour obtenir les stats de synchronisation
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS `sp_get_sync_stats`(
    IN p_customer_id INT,
    IN p_days INT
)
BEGIN
    SELECT 
        sync_type,
        direction,
        COUNT(*) AS total_syncs,
        SUM(records_affected) AS total_records,
        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS successful_syncs,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_syncs,
        AVG(execution_time_ms) AS avg_execution_time_ms,
        MAX(created_at) AS last_sync_at
    FROM erp_sync_logs
    WHERE customer_id = p_customer_id
        AND created_at >= DATE_SUB(NOW(), INTERVAL p_days DAY)
    GROUP BY sync_type, direction
    ORDER BY last_sync_at DESC;
END$$

-- Procédure pour créer un shift depuis une mission
CREATE PROCEDURE IF NOT EXISTS `sp_create_shift_from_mission`(
    IN p_mission_id INT,
    IN p_employee_id INT,
    OUT p_shift_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_company_id INT;
    DECLARE v_datetime DATETIME;
    DECLARE v_departure VARCHAR(255);
    DECLARE v_arrival VARCHAR(255);
    DECLARE v_notes TEXT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_success = FALSE;
        SET p_message = 'Erreur lors de la création du shift';
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Récupérer les infos de la mission
    SELECT f.company_id, m.datetime, m.departure, m.arrival
    INTO v_company_id, v_datetime, v_departure, v_arrival
    FROM missions m
    INNER JOIN folders f ON m.folder_id = f.id
    WHERE m.id = p_mission_id;
    
    -- Vérifier que la mission existe
    IF v_company_id IS NULL THEN
        SET p_success = FALSE;
        SET p_message = 'Mission non trouvée';
        ROLLBACK;
    ELSE
        -- Créer les notes
        SET v_notes = CONCAT('Mission CRM #', p_mission_id, ': ', v_departure, ' → ', v_arrival);
        
        -- Insérer le shift
        INSERT INTO erp_shifts (
            employee_id, 
            company_id, 
            start_time, 
            end_time, 
            notes,
            sync_source,
            mission_id
        ) VALUES (
            p_employee_id,
            v_company_id,
            v_datetime,
            DATE_ADD(v_datetime, INTERVAL 8 HOUR),
            v_notes,
            'crm',
            p_mission_id
        );
        
        SET p_shift_id = LAST_INSERT_ID();
        SET p_success = TRUE;
        SET p_message = CONCAT('Shift #', p_shift_id, ' créé avec succès');
        COMMIT;
    END IF;
END$$

DELIMITER ;

-- ============================================
-- 7. Index de performance
-- ============================================

-- Ajouter des index pour améliorer les performances des requêtes de sync
ALTER TABLE `missions` 
ADD INDEX IF NOT EXISTS `idx_datetime` (`datetime`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`),
ADD INDEX IF NOT EXISTS `idx_updated_at` (`updated_at`);

ALTER TABLE `erp_employees`
ADD INDEX IF NOT EXISTS `idx_status` (`status`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`hire_date`);

ALTER TABLE `companies`
ADD INDEX IF NOT EXISTS `idx_customer_created` (`customer_id`, `created_at`);

-- ============================================
-- 8. Données de test (optionnel)
-- ============================================

-- Insérer quelques données de test si les tables sont vides
-- Décommenter si nécessaire pour les tests

/*
-- Test: Créer un shift de test
INSERT IGNORE INTO erp_shifts (employee_id, company_id, start_time, end_time, role, notes, sync_source)
SELECT 
    1 AS employee_id,
    1 AS company_id,
    NOW() AS start_time,
    DATE_ADD(NOW(), INTERVAL 8 HOUR) AS end_time,
    'Test' AS role,
    'Shift de test pour synchronisation' AS notes,
    'manual' AS sync_source
WHERE NOT EXISTS (SELECT 1 FROM erp_shifts LIMIT 1);

-- Test: Logger une synchronisation
INSERT INTO erp_sync_logs (sync_type, direction, customer_id, records_affected, status, execution_time_ms)
VALUES ('missions', 'crm_to_erp', 1, 0, 'success', 125);
*/

-- ============================================
-- 9. Nettoyer les anciennes données (maintenance)
-- ============================================

-- Créer un événement pour nettoyer les logs de sync > 90 jours
-- (Décommenter si le scheduler MySQL est activé)

/*
CREATE EVENT IF NOT EXISTS `evt_cleanup_sync_logs`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
    DELETE FROM erp_sync_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
*/

-- ============================================
-- FIN DE LA MIGRATION
-- ============================================

-- Vérification finale
SELECT 
    'Migration ERP v2.0 terminée avec succès!' AS message,
    NOW() AS executed_at,
    (SELECT COUNT(*) FROM erp_shifts) AS total_shifts,
    (SELECT COUNT(*) FROM missions) AS total_missions,
    (SELECT COUNT(*) FROM erp_sync_logs) AS total_sync_logs;
