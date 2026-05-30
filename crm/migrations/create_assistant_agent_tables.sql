-- =============================================
-- Migration pour l'Assistant Agent
-- Adaptation aux tables existantes (emails, whatsapp_messages)
-- Date: 2026-02-27
-- =============================================

-- =============================================
-- Ajout de colonnes manquantes aux tables emails existantes
-- =============================================
-- Note: Si erreur "Duplicate column name", c'est que la colonne existe déjà (normal, ignorez l'erreur)

ALTER TABLE emails ADD COLUMN email_id VARCHAR(255) UNIQUE AFTER config_id;
ALTER TABLE emails ADD COLUMN user_id INT NULL AFTER customer_id;
ALTER TABLE emails ADD COLUMN priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal' AFTER is_read;
ALTER TABLE emails ADD COLUMN status ENUM('new', 'inbox', 'archive', 'trash', 'spam') DEFAULT 'inbox' AFTER priority;
ALTER TABLE emails ADD COLUMN is_replied TINYINT(1) DEFAULT 0 AFTER is_read;

-- Créer les index (ignorer si erreur "Duplicate key name")
CREATE INDEX idx_emails_priority ON emails(priority);
CREATE INDEX idx_emails_status ON emails(status);
CREATE INDEX idx_emails_user_id ON emails(user_id);
CREATE INDEX idx_emails_email_id ON emails(email_id);

-- =============================================
-- Ajout de colonnes manquantes à whatsapp_messages
-- =============================================
ALTER TABLE whatsapp_messages
ADD COLUMN user_id INT NULL AFTER customer_id,
ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER status,
ADD COLUMN is_replied TINYINT(1) DEFAULT 0 AFTER is_read;

CREATE INDEX idx_whatsapp_is_read ON whatsapp_messages(is_read);

-- =============================================
-- Table des tâches / Tasks
-- =============================================
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    user_id INT NULL,
    assigned_to INT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    due_date DATETIME NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('todo', 'in_progress', 'completed', 'cancelled') DEFAULT 'todo',
    type VARCHAR(50) DEFAULT 'general',
    related_type VARCHAR(50) NULL COMMENT 'lead, customer, deal, etc.',
    related_id INT NULL,
    tags JSON NULL,
    checklist JSON NULL,
    attachments JSON NULL,
    completed_at TIMESTAMP NULL,
    completed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_user (user_id),
    INDEX idx_assigned (assigned_to),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_related (related_type, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Table des rappels d'appels
-- =============================================
CREATE TABLE IF NOT EXISTS call_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    user_id INT NULL,
    contact_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    contact_type ENUM('lead', 'customer', 'prospect', 'other') DEFAULT 'lead',
    contact_id INT NULL,
    scheduled_time DATETIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    notes TEXT NULL,
    call_type ENUM('follow_up', 'demo', 'support', 'sales', 'other') DEFAULT 'follow_up',
    status ENUM('pending', 'completed', 'cancelled', 'missed') DEFAULT 'pending',
    reminder_sent TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP NULL,
    outcome TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_user (user_id),
    INDEX idx_scheduled (scheduled_time),
    INDEX idx_status (status),
    INDEX idx_contact (contact_type, contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- Note: Utilisation de erp_shifts au lieu de calendar_events
-- La table erp_shifts existe déjà dans l'ERP et gère les horaires
-- L'agent surveillera cette table pour les shifts à venir
-- =============================================

-- =============================================
-- Données de démonstration
-- =============================================

-- Quelques emails de test (utilise la table emails existante)
-- Note: email_id = identifiant unique du message (Message-ID header), différent de id (clé primaire auto)
INSERT IGNORE INTO emails (id, customer_id, config_id, email_id, from_address, from_name, to_address, subject, body, email_date, is_read, priority, status) VALUES
(1, 22, 1, '<CADtest001@example.com>', 'contact@example.com', 'Jean Dupont', 'info@votreentreprise.com', 'Demande de devis', 'Bonjour, je souhaiterais obtenir un devis pour vos services...', DATE_SUB(NOW(), INTERVAL 2 HOUR), 0, 'high', 'new'),
(2, 22, 1, '<CADtest002@client.fr>', 'marie@client.fr', 'Marie Martin', 'info@votreentreprise.com', 'Question urgente', 'J\'ai besoin d\'assistance rapidement...', DATE_SUB(NOW(), INTERVAL 5 HOUR), 0, 'urgent', 'new'),
(3, 22, 1, '<CADtest003@prospect.com>', 'contact@prospect.com', 'Pierre Durand', 'info@votreentreprise.com', 'Intéressé par vos solutions', 'Suite à notre discussion...', DATE_SUB(NOW(), INTERVAL 1 DAY), 1, 'normal', 'inbox');

-- Quelques tâches de test
INSERT IGNORE INTO tasks (id, customer_id, title, description, due_date, priority, status) VALUES
(1, 22, 'Appeler Jean Dupont', 'Rappeler pour discuter du devis', DATE_ADD(NOW(), INTERVAL 2 HOUR), 'high', 'todo'),
(2, 22, 'Préparer proposition commerciale', 'Pour le client ACME Corp', CURDATE(), 'high', 'in_progress'),
(3, 22, 'Relancer lead chaud', 'Marie Martin - Projet CRM', DATE_SUB(NOW(), INTERVAL 1 DAY), 'urgent', 'todo'),
(4, 22, 'Réunion d\'équipe', 'Point hebdomadaire', DATE_ADD(NOW(), INTERVAL 1 DAY), 'medium', 'todo');

-- Quelques rappels d'appels
INSERT IGNORE INTO call_reminders (id, customer_id, contact_name, phone, scheduled_time, call_type, status, notes) VALUES
(1, 22, 'Jean Dupont', '+33612345678', DATE_ADD(NOW(), INTERVAL 3 HOUR), 'follow_up', 'pending', 'Discuter du devis'),
(2, 22, 'Marie Martin', '+33687654321', CURDATE() + INTERVAL 14 HOUR, 'demo', 'pending', 'Démonstration produit'),
(3, 22, 'Pierre Durand', '+33698765432', DATE_SUB(NOW(), INTERVAL 2 HOUR), 'sales', 'pending', 'Discuter proposition');

-- =============================================
-- Note sur les shifts : Utiliser la table erp_shifts de l'ERP
-- Exemple de requête pour surveiller les shifts à venir :
-- SELECT * FROM erp_shifts WHERE customer_id = 22 AND start_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
-- =============================================

-- Quelques messages WhatsApp de test (utilise la table whatsapp_messages existante)
INSERT IGNORE INTO whatsapp_messages (id, customer_id, config_id, message_id, direction, from_phone, to_phone, contact_name, content, message_type, status, is_read, created_at) VALUES
(1, 22, 1, 'wamid.test1', 'inbound', '+33612345678', '+33700000000', 'Jean Dupont', 'Bonjour, avez-vous reçu ma demande de devis ?', 'text', 'delivered', 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 22, 1, 'wamid.test2', 'inbound', '+33687654321', '+33700000000', 'Marie Martin', 'Quand pouvons-nous organiser une démo ?', 'text', 'delivered', 0, DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- =============================================
-- Vérifier que l'intégration WhatsApp existe
-- =============================================
INSERT IGNORE INTO integrations (customer_id, integration_type, name, description, icon, color, is_active, last_sync, sync_status)
VALUES (22, 'whatsapp', 'WhatsApp Business', 'Messagerie WhatsApp pour le support client', 'fa-whatsapp', 'success', 1, NOW(), 'success');

INSERT IGNORE INTO integrations (customer_id, integration_type, name, description, icon, color, is_active, last_sync, sync_status)
VALUES (22, 'email', 'Email IMAP', 'Synchronisation emails via IMAP', 'fa-envelope', 'primary', 1, NOW(), 'success');

INSERT IGNORE INTO integrations (customer_id, integration_type, name, description, icon, color, is_active, last_sync, sync_status)
VALUES (22, 'calendar', 'Google Calendar', 'Synchronisation du calendrier Google', 'fa-calendar', 'warning', 1, NOW(), 'success');

-- =============================================
-- Fin de la migration
-- =============================================
