-- Migration: Ajouter les colonnes manquantes à la table emails
-- Date: 2026-02-19
-- Description: Cette migration ajoute customer_id et config_id si nécessaires

-- =====================================================
-- ÉTAPE 1: Ajouter config_id si elle n'existe pas
-- =====================================================
SET @config_id_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'emails' 
    AND COLUMN_NAME = 'config_id'
);

SET @query = IF(@config_id_exists = 0,
    'ALTER TABLE emails ADD COLUMN config_id INT NULL AFTER id',
    'SELECT "La colonne config_id existe déjà" as info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ÉTAPE 2: Ajouter customer_id si elle n'existe pas
-- =====================================================
SET @customer_id_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'emails' 
    AND COLUMN_NAME = 'customer_id'
);

SET @query = IF(@customer_id_exists = 0,
    'ALTER TABLE emails ADD COLUMN customer_id INT NULL AFTER config_id',
    'SELECT "La colonne customer_id existe déjà" as info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ÉTAPE 3: Remplir les colonnes depuis email_configurations (si elles existent)
-- =====================================================

-- D'abord remplir config_id si la table a une colonne qui peut l'identifier
-- On suppose que les emails ont un email_id unique ou from_address qui peut être lié
-- Note: Cette partie dépend de votre structure actuelle. Adaptez si nécessaire.

-- Si vous avez déjà des données, vous devrez peut-être les remplir manuellement
-- ou adapter cette requête selon votre schéma actuel

-- Remplir customer_id depuis config_id (si config_id existe et est déjà rempli)
UPDATE emails e
INNER JOIN email_configurations ec ON e.config_id = ec.id
SET e.customer_id = ec.customer_id
WHERE e.config_id IS NOT NULL 
AND (e.customer_id IS NULL OR e.customer_id = 0);

-- =====================================================
-- ÉTAPE 4: Rendre les colonnes NOT NULL (uniquement si elles contiennent des données)
-- =====================================================

-- Vérifier s'il y a des NULL dans config_id
SET @null_config = (SELECT COUNT(*) FROM emails WHERE config_id IS NULL);

-- Si pas de NULL, rendre NOT NULL
SET @query = IF(@null_config = 0 AND @config_id_exists = 0,
    'ALTER TABLE emails MODIFY COLUMN config_id INT NOT NULL',
    'SELECT "config_id ne peut pas être NOT NULL (contient des NULL ou existe déjà)" as info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier s'il y a des NULL dans customer_id
SET @null_customer = (SELECT COUNT(*) FROM emails WHERE customer_id IS NULL);

-- Si pas de NULL, rendre NOT NULL  
SET @query = IF(@null_customer = 0 AND @customer_id_exists = 0,
    'ALTER TABLE emails MODIFY COLUMN customer_id INT NOT NULL',
    'SELECT "customer_id ne peut pas être NOT NULL (contient des NULL ou existe déjà)" as info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ÉTAPE 5: Ajouter les index
-- =====================================================

-- Index pour config_id
SET @idx_config_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'emails' 
    AND INDEX_NAME = 'idx_emails_config_id'
);

SET @query = IF(@idx_config_exists = 0,
    'ALTER TABLE emails ADD INDEX idx_emails_config_id (config_id)',
    'SELECT "L\'index idx_emails_config_id existe déjà" as info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index pour customer_id
SET @idx_customer_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'emails' 
    AND INDEX_NAME = 'idx_emails_customer_id'
);

SET @query = IF(@idx_customer_exists = 0,
    'ALTER TABLE emails ADD INDEX idx_emails_customer_id (customer_id)',
    'SELECT "L\'index idx_emails_customer_id existe déjà" as info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- ÉTAPE 6: Ajouter les foreign keys
-- =====================================================

-- Foreign key pour config_id
SET @fk_config_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'emails' 
    AND CONSTRAINT_NAME = 'emails_ibfk_1'
    AND COLUMN_NAME = 'config_id'
);

SET @query = IF(@fk_config_exists = 0 AND @null_config = 0,
    'ALTER TABLE emails ADD CONSTRAINT emails_ibfk_1 FOREIGN KEY (config_id) REFERENCES email_configurations(id) ON DELETE CASCADE',
    'SELECT "La foreign key pour config_id existe déjà ou contient des NULL" as info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Foreign key pour customer_id
SET @fk_customer_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'emails' 
    AND CONSTRAINT_NAME = 'fk_emails_customer'
);

SET @query = IF(@fk_customer_exists = 0 AND @null_customer = 0,
    'ALTER TABLE emails ADD CONSTRAINT fk_emails_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE',
    'SELECT "La foreign key fk_emails_customer existe déjà ou contient des NULL" as info'
);
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- RÉSUMÉ DE LA MIGRATION
-- =====================================================
SELECT 
    'Migration terminée' as Statut,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'emails' AND COLUMN_NAME = 'config_id') as config_id_existe,
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'emails' AND COLUMN_NAME = 'customer_id') as customer_id_existe,
    (SELECT COUNT(*) FROM emails WHERE config_id IS NULL) as emails_sans_config_id,
    (SELECT COUNT(*) FROM emails WHERE customer_id IS NULL) as emails_sans_customer_id;
