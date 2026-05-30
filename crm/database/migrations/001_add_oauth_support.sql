-- =====================================================
-- MIGRATION: Add OAuth2 Support to Email Integration
-- Version: 1.0.0
-- Date: 2026-02-16
-- Description: Add OAuth2 fields for Gmail/Microsoft authentication
-- =====================================================

-- Ajouter les colonnes OAuth à email_configurations
ALTER TABLE email_configurations
ADD COLUMN oauth_provider VARCHAR(50) DEFAULT NULL COMMENT 'google, microsoft, none',
ADD COLUMN oauth_access_token LONGTEXT DEFAULT NULL COMMENT 'Encrypted OAuth access token',
ADD COLUMN oauth_refresh_token LONGTEXT DEFAULT NULL COMMENT 'Encrypted OAuth refresh token',
ADD COLUMN oauth_token_expires_at DATETIME DEFAULT NULL COMMENT 'When the access token expires',
ADD COLUMN oauth_scope VARCHAR(500) DEFAULT NULL COMMENT 'Granted OAuth scopes',
ADD COLUMN token_encrypted TINYINT(1) DEFAULT 1 COMMENT 'Whether tokens are encrypted',
ADD COLUMN last_error TEXT DEFAULT NULL COMMENT 'Last authentication/sync error',
ADD COLUMN error_count INT DEFAULT 0 COMMENT 'Consecutive error count for backoff',
ADD COLUMN last_error_at DATETIME DEFAULT NULL COMMENT 'When last error occurred',
ADD COLUMN webhook_channel VARCHAR(255) DEFAULT NULL COMMENT 'Webhook channel ID (Gmail Pub/Sub or Microsoft subscription)',
ADD COLUMN webhook_expires_at DATETIME DEFAULT NULL COMMENT 'When webhook subscription expires',
ADD COLUMN sync_enabled TINYINT(1) DEFAULT 1 COMMENT 'Enable/disable sync for this account',
ADD COLUMN connection_method ENUM('oauth', 'password', 'app_password') DEFAULT 'password' COMMENT 'How user authenticated';

-- Modifier la colonne password pour la rendre optionnelle (NULL si OAuth)
ALTER TABLE email_configurations
MODIFY COLUMN password LONGTEXT DEFAULT NULL COMMENT 'Encrypted password (NULL if OAuth)';

-- Index pour améliorer les performances (compatible MySQL 5.7+)
SET @dbname = DATABASE();
SET @tablename_config = 'email_configurations';

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename_config AND INDEX_NAME='idx_oauth_provider') > 0,
    'SELECT 1',
    'CREATE INDEX idx_oauth_provider ON email_configurations(oauth_provider)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename_config AND INDEX_NAME='idx_token_expires') > 0,
    'SELECT 1',
    'CREATE INDEX idx_token_expires ON email_configurations(oauth_token_expires_at)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename_config AND INDEX_NAME='idx_webhook_expires') > 0,
    'SELECT 1',
    'CREATE INDEX idx_webhook_expires ON email_configurations(webhook_expires_at)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename_config AND INDEX_NAME='idx_sync_enabled') > 0,
    'SELECT 1',
    'CREATE INDEX idx_sync_enabled ON email_configurations(sync_enabled)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Table pour gérer la queue de synchronisation (Redis alternative en DB)
CREATE TABLE IF NOT EXISTS email_sync_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_id INT NOT NULL,
    customer_id INT NOT NULL,
    priority ENUM('high', 'normal', 'low') DEFAULT 'normal',
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (config_id) REFERENCES email_configurations(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_config (config_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter les colonnes manquantes si email_sync_queue existe déjà
SET @dbname = DATABASE();
SET @tablename = 'email_sync_queue';

-- Ajouter priority si elle n'existe pas
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='priority') > 0,
    'SELECT 1',
    'ALTER TABLE email_sync_queue ADD COLUMN priority ENUM(''high'', ''normal'', ''low'') DEFAULT ''normal'' AFTER customer_id'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter status si elle n'existe pas
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='status') > 0,
    'SELECT 1',
    'ALTER TABLE email_sync_queue ADD COLUMN status ENUM(''pending'', ''processing'', ''completed'', ''failed'') DEFAULT ''pending'' AFTER priority'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter retry_count si elle n'existe pas
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='retry_count') > 0,
    'SELECT 1',
    'ALTER TABLE email_sync_queue ADD COLUMN retry_count INT DEFAULT 0 AFTER status'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter max_retries si elle n'existe pas
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='max_retries') > 0,
    'SELECT 1',
    'ALTER TABLE email_sync_queue ADD COLUMN max_retries INT DEFAULT 3 AFTER retry_count'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter scheduled_at si elle n'existe pas
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='scheduled_at') > 0,
    'SELECT 1',
    'ALTER TABLE email_sync_queue ADD COLUMN scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER max_retries'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter started_at si elle n'existe pas
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='started_at') > 0,
    'SELECT 1',
    'ALTER TABLE email_sync_queue ADD COLUMN started_at DATETIME DEFAULT NULL AFTER scheduled_at'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter completed_at si elle n'existe pas
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='completed_at') > 0,
    'SELECT 1',
    'ALTER TABLE email_sync_queue ADD COLUMN completed_at DATETIME DEFAULT NULL AFTER started_at'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter error_message si elle n'existe pas
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='error_message') > 0,
    'SELECT 1',
    'ALTER TABLE email_sync_queue ADD COLUMN error_message TEXT DEFAULT NULL AFTER completed_at'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter les index s'ils n'existent pas (compatible MySQL 5.7+)
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND INDEX_NAME='idx_status') > 0,
    'SELECT 1',
    'CREATE INDEX idx_status ON email_sync_queue(status)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND INDEX_NAME='idx_priority') > 0,
    'SELECT 1',
    'CREATE INDEX idx_priority ON email_sync_queue(priority)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND INDEX_NAME='idx_scheduled') > 0,
    'SELECT 1',
    'CREATE INDEX idx_scheduled ON email_sync_queue(scheduled_at)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND INDEX_NAME='idx_config') > 0,
    'SELECT 1',
    'CREATE INDEX idx_config ON email_sync_queue(config_id)'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Table pour les webhooks entrants (logs)
CREATE TABLE IF NOT EXISTS email_webhook_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_id INT DEFAULT NULL,
    provider VARCHAR(50) NOT NULL COMMENT 'google, microsoft',
    event_type VARCHAR(100) NOT NULL COMMENT 'message_received, etc.',
    payload LONGTEXT NOT NULL COMMENT 'Raw webhook payload (JSON)',
    processed TINYINT(1) DEFAULT 0,
    processed_at DATETIME DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (config_id) REFERENCES email_configurations(id) ON DELETE SET NULL,
    INDEX idx_processed (processed),
    INDEX idx_provider (provider),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour stocker les métriques de rate limiting
CREATE TABLE IF NOT EXISTS email_rate_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_id INT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    endpoint VARCHAR(255) NOT NULL COMMENT 'API endpoint or action',
    request_count INT DEFAULT 0,
    window_start DATETIME NOT NULL,
    window_end DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (config_id) REFERENCES email_configurations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_rate_limit (config_id, endpoint, window_start),
    INDEX idx_window (window_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mettre à jour les configurations existantes
UPDATE email_configurations 
SET connection_method = 'password', 
    oauth_provider = 'none'
WHERE oauth_provider IS NULL;

-- Commentaires pour documentation
ALTER TABLE email_configurations 
COMMENT = 'Email account configurations with OAuth2 support for Gmail/Microsoft and IMAP/SMTP for others';
