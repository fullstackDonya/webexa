-- Migration pour le système de notifications et d'alertes
-- Date: 2026-02-26

-- Table des notifications CRM (différente de la table notifications existante)
CREATE TABLE IF NOT EXISTS crm_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    user_id INT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-bell',
    color VARCHAR(20) DEFAULT 'primary',
    link VARCHAR(500) NULL,
    is_read TINYINT(1) DEFAULT 0,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des intégrations disponibles
CREATE TABLE IF NOT EXISTS integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    integration_type VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(100) DEFAULT 'fa-plug',
    color VARCHAR(20) DEFAULT 'primary',
    is_active TINYINT(1) DEFAULT 0,
    config JSON NULL,
    api_key VARCHAR(500) NULL,
    api_secret VARCHAR(500) NULL,
    webhook_url VARCHAR(500) NULL,
    last_sync TIMESTAMP NULL,
    sync_status ENUM('never', 'success', 'error', 'syncing') DEFAULT 'never',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customers (customer_id),
    INDEX idx_type (integration_type),
    INDEX idx_active (is_active),
    UNIQUE KEY unique_customer_integration (customer_id, integration_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des logs d'intégration
CREATE TABLE IF NOT EXISTS integration_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    status ENUM('success', 'error', 'warning') NOT NULL,
    message TEXT NULL,
    data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_integration (integration_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des intégrations disponibles se fera via l'interface PHP
-- Note: Les insertions de notifications de démonstration seront faites directement sans vérification

-- Quelques notifications de démonstration pour le customer_id 22
INSERT IGNORE INTO crm_notifications (customer_id, user_id, type, title, message, icon, color, link, priority) VALUES
(22, NULL, 'lead_new', 'Nouveau lead qualifié', 'Un nouveau lead "chaud" a été détecté par l\'IA', 'fa-user-plus', 'success', 'leads-view.php?id=4', 'high'),
(22, NULL, 'campaign_completed', 'Campagne terminée', 'Votre campagne "Promo Février" est terminée avec un taux d\'ouverture de 45%', 'fa-envelope', 'info', 'campaigns-stats.php?id=1', 'medium'),
(22, NULL, 'task_reminder', 'Rappel de tâche', 'N\'oubliez pas de contacter le lead Jean Dupont', 'fa-clock', 'warning', 'tasks.php', 'high'),
(22, NULL, 'integration_error', 'Erreur d\'intégration', 'La synchronisation avec Google Calendar a échoué', 'fa-exclamation-triangle', 'danger', 'integrations.php', 'urgent');
