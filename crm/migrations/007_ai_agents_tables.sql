-- Migration 007: Tables pour les agents IA
-- Date: 2026-02-25

-- Table des logs des agents
CREATE TABLE IF NOT EXISTS agent_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_name VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    input_data JSON,
    output_data JSON,
    status ENUM('success', 'error', 'warning') DEFAULT 'success',
    error_message TEXT,
    customer_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agent_name (agent_name),
    INDEX idx_customer_id (customer_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des actions proposées/exécutées par les agents
CREATE TABLE IF NOT EXISTS agent_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_name VARCHAR(50) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id INT NOT NULL,
    data JSON,
    status ENUM('pending', 'approved', 'rejected', 'executed', 'failed') DEFAULT 'pending',
    result JSON,
    customer_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    executed_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_customer_id (customer_id),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des permissions des agents
CREATE TABLE IF NOT EXISTS agent_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    agent_name VARCHAR(50) NOT NULL,
    can_read BOOLEAN DEFAULT TRUE,
    can_suggest BOOLEAN DEFAULT TRUE,
    can_execute BOOLEAN DEFAULT FALSE,
    automation_mode ENUM('assisted', 'semi-auto', 'autonomous') DEFAULT 'assisted',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_customer_agent (customer_id, agent_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table de mémoire contextuelle pour les agents
CREATE TABLE IF NOT EXISTS agent_memory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_name VARCHAR(50) NOT NULL,
    context_type VARCHAR(50) NOT NULL,
    context_id INT NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    value TEXT,
    customer_id INT,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_agent_context (agent_name, context_type, context_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des tâches quotidiennes
CREATE TABLE IF NOT EXISTS daily_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    assigned_to INT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    due_date DATE,
    created_by_agent VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ajouter des colonnes à la table emails pour l'analyse IA
ALTER TABLE emails 
ADD COLUMN IF NOT EXISTS ai_analyzed BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS ai_summary TEXT,
ADD COLUMN IF NOT EXISTS ai_type VARCHAR(50),
ADD COLUMN IF NOT EXISTS ai_priority VARCHAR(20),
ADD COLUMN IF NOT EXISTS ai_sentiment VARCHAR(20),
ADD COLUMN IF NOT EXISTS ai_action_id INT,
ADD INDEX IF NOT EXISTS idx_ai_analyzed (ai_analyzed);

-- Ajouter des colonnes à la table leads pour le scoring IA
ALTER TABLE leads 
ADD COLUMN IF NOT EXISTS score INT DEFAULT 50,
ADD COLUMN IF NOT EXISTS score_category ENUM('chaud', 'tiede', 'froid', 'mort') DEFAULT 'tiede',
ADD COLUMN IF NOT EXISTS score_reasoning TEXT,
ADD COLUMN IF NOT EXISTS score_updated_at TIMESTAMP NULL,
ADD INDEX IF NOT EXISTS idx_score (score),
ADD INDEX IF NOT EXISTS idx_score_category (score_category);

-- Insérer des permissions par défaut pour les agents existants
INSERT INTO agent_permissions (customer_id, agent_name, automation_mode)
SELECT DISTINCT id, 'inbox_agent', 'assisted' FROM customers
WHERE NOT EXISTS (
    SELECT 1 FROM agent_permissions 
    WHERE agent_permissions.customer_id = customers.id 
    AND agent_permissions.agent_name = 'inbox_agent'
);

INSERT INTO agent_permissions (customer_id, agent_name, automation_mode)
SELECT DISTINCT id, 'lead_analyst_agent', 'assisted' FROM customers
WHERE NOT EXISTS (
    SELECT 1 FROM agent_permissions 
    WHERE agent_permissions.customer_id = customers.id 
    AND agent_permissions.agent_name = 'lead_analyst_agent'
);

-- Vue pour les statistiques des agents
CREATE OR REPLACE VIEW agent_stats AS
SELECT 
    agent_name,
    customer_id,
    DATE(created_at) as date,
    COUNT(*) as total_actions,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
    AVG(CASE WHEN status = 'success' THEN 1 ELSE 0 END) * 100 as success_rate
FROM agent_logs
GROUP BY agent_name, customer_id, DATE(created_at);

-- Données de test (optionnel)
-- INSERT INTO agent_logs (agent_name, action, input_data, output_data, status) 
-- VALUES ('inbox_agent', 'test', '{}', '{"message": "Test successful"}', 'success');
