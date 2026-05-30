-- =====================================================
-- MIGRATION: Link Campaigns to Email/WhatsApp Configurations
-- Version: 1.0.0
-- Date: 2026-02-18
-- Description: Ajoute les colonnes pour lier les campaigns aux configurations email/whatsapp
--              Permet de définir automatiquement l'expéditeur basé sur la configuration active
-- =====================================================

-- Ajouter les colonnes pour lier aux configurations
ALTER TABLE `campaigns` 
ADD COLUMN `email_config_id` INT DEFAULT NULL COMMENT 'ID de la configuration email à utiliser' AFTER `customer_id`,
ADD COLUMN `whatsapp_config_id` INT DEFAULT NULL COMMENT 'ID de la configuration WhatsApp à utiliser' AFTER `email_config_id`,
ADD COLUMN `channel` VARCHAR(20) DEFAULT 'email' COMMENT 'Canal de communication: email, whatsapp, sms' AFTER `whatsapp_config_id`;

-- Ajouter les clés étrangères
ALTER TABLE `campaigns` 
ADD CONSTRAINT `fk_campaigns_email_config` 
    FOREIGN KEY (`email_config_id`) REFERENCES `email_configurations`(`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_campaigns_whatsapp_config` 
    FOREIGN KEY (`whatsapp_config_id`) REFERENCES `whatsapp_configurations`(`id`) ON DELETE SET NULL;

-- Créer les index pour les performances
CREATE INDEX `idx_campaigns_email_config` ON `campaigns`(`email_config_id`);
CREATE INDEX `idx_campaigns_whatsapp_config` ON `campaigns`(`whatsapp_config_id`);
CREATE INDEX `idx_campaigns_channel` ON `campaigns`(`channel`);

-- Migration des données existantes : mettre à jour les campagnes existantes
-- pour utiliser la première configuration email active du client
UPDATE `campaigns` c
INNER JOIN (
    SELECT customer_id, MIN(id) as config_id 
    FROM `email_configurations` 
    WHERE is_active = 1 
    GROUP BY customer_id
) ec ON c.customer_id = ec.customer_id
SET c.email_config_id = ec.config_id,
    c.channel = 'email'
WHERE c.email_config_id IS NULL 
  AND c.channel IS NULL;

-- Mettre à jour les campagnes sans configuration avec 'email' par défaut
UPDATE `campaigns` 
SET channel = 'email' 
WHERE channel IS NULL;

CREATE TABLE IF NOT EXISTS migration_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(150) NOT NULL UNIQUE,
    executed_at DATETIME NOT NULL,
    description TEXT NULL
);

-- Log de la migration
INSERT INTO migration_log (migration_name, executed_at, description) 
VALUES (
    '003_link_campaigns_to_configs',
    NOW(),
    'Ajout des colonnes email_config_id, whatsapp_config_id et channel dans campaigns'
) ON DUPLICATE KEY UPDATE executed_at = NOW();

-- Créer la table migration_log si elle n'existe pas
CREATE TABLE IF NOT EXISTS migration_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    migration_name VARCHAR(255) UNIQUE NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT,
    INDEX idx_migration_name (migration_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
