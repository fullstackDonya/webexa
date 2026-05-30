-- =====================================================
-- EMAIL CONFIGURATION TABLES
-- ===================================================

-- Table pour stocker les configurations email
CREATE TABLE IF NOT EXISTS email_configurations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT,
    provider VARCHAR(50) NOT NULL COMMENT 'gmail, outlook, hostinger, custom',
    email VARCHAR(255) NOT NULL,
    password LONGTEXT NOT NULL COMMENT 'Chiffré avec AES-256-CBC',
    imap_server VARCHAR(255),
    imap_port INT DEFAULT 993,
    smtp_server VARCHAR(255),
    smtp_port INT DEFAULT 587,
    sync_leads BOOLEAN DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    last_sync DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_email` (customer_id, email),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_provider (provider)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour stocker les emails synchronisés
CREATE TABLE IF NOT EXISTS emails (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    config_id INT NOT NULL,
    email_id VARCHAR(255) UNIQUE,
    from_address VARCHAR(255),
    from_name VARCHAR(255),
    to_address VARCHAR(255),
    subject TEXT,
    body LONGTEXT,
    email_date DATETIME,
    is_read BOOLEAN DEFAULT 0,
    has_attachments BOOLEAN DEFAULT 0,
    is_spam BOOLEAN DEFAULT 0,
    extracted_lead_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (config_id) REFERENCES email_configurations(id) ON DELETE CASCADE,
    FOREIGN KEY (extracted_lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_from_address (from_address),
    INDEX idx_email_date (email_date),
    FULLTEXT INDEX ft_subject_body (subject, body)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE emails
ADD COLUMN imap_uid BIGINT NULL AFTER id,
ADD COLUMN mailbox VARCHAR(255) NULL DEFAULT 'INBOX' AFTER imap_uid,
ADD INDEX idx_imap_uid (imap_uid);


ALTER TABLE emails
ADD COLUMN message_id_header VARCHAR(512) NULL AFTER imap_uid,
ADD COLUMN in_reply_to VARCHAR(512) NULL AFTER message_id_header,
ADD COLUMN references_header TEXT NULL AFTER in_reply_to,
ADD INDEX idx_message_id_header (message_id_header);

-- Table pour les campaigns email
CREATE TABLE IF NOT EXISTS email_campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT,
    template_id INT,
    status ENUM('draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled') DEFAULT 'draft',
    scheduled_at DATETIME,
    sent_at DATETIME,
    recipients_count INT DEFAULT 0,
    opened_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    bounced_count INT DEFAULT 0,
    unsubscribed_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les destinataires de campaigns
CREATE TABLE IF NOT EXISTS email_campaign_recipients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    recipient_email VARCHAR(255),
    recipient_id INT,
    status ENUM('pending', 'sent', 'opened', 'clicked', 'bounced', 'unsubscribed', 'failed') DEFAULT 'pending',
    opened_at DATETIME,
    clicked_at DATETIME,
    bounced_at DATETIME,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les email templates
CREATE TABLE IF NOT EXISTS email_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    body LONGTEXT,
    category VARCHAR(100),
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour le log des syncs email
CREATE TABLE IF NOT EXISTS email_sync_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_id INT NOT NULL,
    customer_id INT NOT NULL,
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    emails_fetched INT DEFAULT 0,
    leads_extracted INT DEFAULT 0,
    error_message TEXT,
    started_at DATETIME,
    completed_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (config_id) REFERENCES email_configurations(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_config_id (config_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE email_configurations
  ADD COLUMN smtp_host VARCHAR(255) DEFAULT NULL,
  ADD COLUMN smtp_port INT DEFAULT NULL,
  ADD COLUMN smtp_user VARCHAR(255) DEFAULT NULL,
  ADD COLUMN smtp_pass TEXT DEFAULT NULL,
  ADD COLUMN smtp_from VARCHAR(255) DEFAULT NULL;


  ALTER TABLE email_configurations
  ADD COLUMN last_imap_uid BIGINT(20) NULL AFTER last_sync,
  ADD COLUMN mailbox VARCHAR(255) NULL AFTER imap_port,
  ADD COLUMN use_ssl TINYINT(1) NOT NULL DEFAULT 1 AFTER imap_port,
  ADD COLUMN webhook_subscription_id VARCHAR(255) NULL AFTER webhook_channel,
  ADD COLUMN labels_to_sync VARCHAR(500) NULL AFTER webhook_channel,
  ADD COLUMN last_successful_sync_at DATETIME NULL AFTER last_sync,
  ADD COLUMN retention_days INT(11) NULL DEFAULT 0 AFTER labels_to_sync,
  ADD INDEX idx_last_imap_uid (last_imap_uid),
  ADD INDEX idx_oauth_token_expires_at (oauth_token_expires_at);

-- Table pour les emails envoyés
CREATE TABLE IF NOT EXISTS emails_sent (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    config_id INT NOT NULL,
    user_id INT,
    to_address TEXT NOT NULL,
    cc_address TEXT,
    bcc_address TEXT,
    subject VARCHAR(500),
    body LONGTEXT,
    sent_at DATETIME NOT NULL,
    status ENUM('sent', 'failed', 'bounced') DEFAULT 'sent',
    error_message TEXT,
    message_id_header VARCHAR(512),
    in_reply_to VARCHAR(512),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (config_id) REFERENCES email_configurations(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_sent_at (sent_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Améliorer la table email_configurations pour OAuth2
ALTER TABLE email_configurations
  ADD COLUMN IF NOT EXISTS oauth_provider VARCHAR(50) DEFAULT 'none' COMMENT 'gmail, outlook, none',
  ADD COLUMN IF NOT EXISTS oauth_access_token TEXT,
  ADD COLUMN IF NOT EXISTS oauth_refresh_token TEXT,
  ADD COLUMN IF NOT EXISTS oauth_token_expires_at DATETIME,
  ADD COLUMN IF NOT EXISTS connection_method ENUM('password', 'app_password', 'oauth') DEFAULT 'password',
  ADD COLUMN IF NOT EXISTS webhook_url VARCHAR(500),
  ADD COLUMN IF NOT EXISTS webhook_channel VARCHAR(255);

-- Table pour les pièces jointes
CREATE TABLE IF NOT EXISTS email_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filesize INT NOT NULL,
    mime_type VARCHAR(100),
    file_path VARCHAR(500),
    is_inline BOOLEAN DEFAULT 0,
    content_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE,
    INDEX idx_email_id (email_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE emails
ADD COLUMN email_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE emails
ADD COLUMN has_attachments TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE campaigns
  ADD COLUMN sent_count INT UNSIGNED NULL DEFAULT 0 AFTER sent_at,
  ADD INDEX idx_campaigns_sent_count (sent_count);
