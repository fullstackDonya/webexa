-- =====================================================
-- MIGRATION: Add WhatsApp Business Integration
-- Version: 1.0.0
-- Date: 2026-02-16
-- Description: WhatsApp Business Cloud API multi-tenant support
-- =====================================================

-- Table pour les configurations WhatsApp par entreprise
CREATE TABLE IF NOT EXISTS whatsapp_configurations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT,
    phone_number_id VARCHAR(100) NOT NULL COMMENT 'Meta Phone Number ID',
    business_account_id VARCHAR(100) NOT NULL COMMENT 'Meta Business Account ID',
    display_phone_number VARCHAR(20) COMMENT 'Format international: +33612345678',
    verified TINYINT(1) DEFAULT 0 COMMENT 'Numéro vérifié par Meta',
    access_token LONGTEXT NOT NULL COMMENT 'Encrypted Meta access token (permanent)',
    webhook_verify_token VARCHAR(255) COMMENT 'Token pour vérifier webhooks',
    quality_rating ENUM('GREEN', 'YELLOW', 'RED', 'UNKNOWN') DEFAULT 'UNKNOWN',
    messaging_limit ENUM('TIER_50', 'TIER_250', 'TIER_1K', 'TIER_10K', 'TIER_100K', 'TIER_UNLIMITED') DEFAULT 'TIER_50',
    is_active TINYINT(1) DEFAULT 1,
    last_error TEXT DEFAULT NULL,
    error_count INT DEFAULT 0,
    last_error_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_phone (customer_id, phone_number_id),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_phone_number_id (phone_number_id),
    INDEX idx_quality_rating (quality_rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les messages WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    config_id INT NOT NULL,
    message_id VARCHAR(255) UNIQUE COMMENT 'Meta message ID (wamid.xxx)',
    conversation_id VARCHAR(255) COMMENT 'Pour grouper les messages',
    direction ENUM('inbound', 'outbound') NOT NULL,
    from_phone VARCHAR(20) NOT NULL,
    to_phone VARCHAR(20) NOT NULL,
    contact_name VARCHAR(255),
    lead_id INT DEFAULT NULL COMMENT 'Lead associé',
    message_type ENUM('text', 'image', 'document', 'audio', 'video', 'location', 'template', 'interactive') DEFAULT 'text',
    content LONGTEXT COMMENT 'Message text or JSON payload',
    media_url VARCHAR(500) COMMENT 'URL du média si applicable',
    template_name VARCHAR(255) COMMENT 'Nom du template Meta si utilisé',
    status ENUM('sent', 'delivered', 'read', 'failed', 'pending') DEFAULT 'pending',
    status_timestamp DATETIME,
    error_code VARCHAR(50),
    error_message TEXT,
    cost DECIMAL(10, 4) DEFAULT 0 COMMENT 'Coût Meta en USD',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (config_id) REFERENCES whatsapp_configurations(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_conversation (conversation_id),
    INDEX idx_direction (direction),
    INDEX idx_status (status),
    INDEX idx_from_phone (from_phone),
    INDEX idx_created (created_at),
    FULLTEXT INDEX ft_content (content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les templates WhatsApp (approuvés par Meta)
CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    config_id INT NOT NULL,
    template_name VARCHAR(255) NOT NULL COMMENT 'Nom unique du template',
    language VARCHAR(10) DEFAULT 'fr' COMMENT 'Code langue ISO (fr, en, es...)',
    category ENUM('MARKETING', 'UTILITY', 'AUTHENTICATION') NOT NULL,
    status ENUM('PENDING', 'APPROVED', 'REJECTED', 'DISABLED') DEFAULT 'PENDING',
    header_type ENUM('NONE', 'TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT') DEFAULT 'NONE',
    header_content TEXT COMMENT 'Texte ou URL média header',
    body_text TEXT NOT NULL COMMENT 'Corps du message avec {{1}} variables',
    footer_text VARCHAR(60) COMMENT 'Texte footer optionnel',
    buttons JSON COMMENT 'Boutons: [{"type":"QUICK_REPLY","text":"Oui"}]',
    meta_template_id VARCHAR(100) COMMENT 'ID Meta du template approuvé',
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_template (customer_id, template_name, language),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (config_id) REFERENCES whatsapp_configurations(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les campagnes WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    user_id INT,
    config_id INT NOT NULL,
    template_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled') DEFAULT 'draft',
    scheduled_at DATETIME,
    sent_at DATETIME,
    completed_at DATETIME,
    recipients_count INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    read_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    replied_count INT DEFAULT 0,
    total_cost DECIMAL(10, 2) DEFAULT 0 COMMENT 'Coût total Meta en USD',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (config_id) REFERENCES whatsapp_configurations(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES whatsapp_templates(id) ON DELETE RESTRICT,
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les destinataires des campagnes WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_campaign_recipients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    lead_id INT DEFAULT NULL,
    phone_number VARCHAR(20) NOT NULL,
    contact_name VARCHAR(255),
    variables JSON COMMENT 'Variables pour template: {"1":"John","2":"Paris"}',
    status ENUM('pending', 'sent', 'delivered', 'read', 'failed', 'replied') DEFAULT 'pending',
    message_id VARCHAR(255) COMMENT 'Meta message ID',
    sent_at DATETIME,
    delivered_at DATETIME,
    read_at DATETIME,
    replied_at DATETIME,
    error_code VARCHAR(50),
    error_message TEXT,
    cost DECIMAL(10, 4) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES whatsapp_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_status (status),
    INDEX idx_phone (phone_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les webhooks WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_webhook_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_id INT DEFAULT NULL,
    event_type VARCHAR(100) NOT NULL COMMENT 'messages, message_status, account_update',
    payload LONGTEXT NOT NULL COMMENT 'JSON brut Meta',
    message_id VARCHAR(255),
    processed TINYINT(1) DEFAULT 0,
    processed_at DATETIME,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (config_id) REFERENCES whatsapp_configurations(id) ON DELETE SET NULL,
    INDEX idx_processed (processed),
    INDEX idx_event_type (event_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les conversations WhatsApp (analytique)
CREATE TABLE IF NOT EXISTS whatsapp_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    config_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    contact_name VARCHAR(255),
    lead_id INT DEFAULT NULL,
    last_message_at DATETIME,
    last_message_direction ENUM('inbound', 'outbound'),
    message_count INT DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (config_id) REFERENCES whatsapp_configurations(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_phone (phone_number),
    INDEX idx_last_message (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commentaires pour documentation
ALTER TABLE whatsapp_configurations 
COMMENT = 'WhatsApp Business configurations per customer using Meta Cloud API';

ALTER TABLE whatsapp_messages 
COMMENT = 'All WhatsApp messages sent and received with full conversation history';

ALTER TABLE whatsapp_templates 
COMMENT = 'Meta-approved message templates required for outbound messages';

ALTER TABLE whatsapp_campaigns 
COMMENT = 'WhatsApp marketing campaigns sent to multiple recipients';
