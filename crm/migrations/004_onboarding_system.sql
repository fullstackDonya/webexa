-- Migration pour le système d'onboarding
-- Date: 2026-02-28

-- Ajouter la colonne onboarding_completed à la table users
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS onboarding_completed TINYINT(1) DEFAULT 0 AFTER email,
ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER email,
ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER first_name,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Créer la table user_settings si elle n'existe pas
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_setting (user_id, setting_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter des colonnes manquantes à la table customers
ALTER TABLE customers 
ADD COLUMN IF NOT EXISTS position VARCHAR(100) AFTER email,
ADD COLUMN IF NOT EXISTS address VARCHAR(255) AFTER phone,
ADD COLUMN IF NOT EXISTS city VARCHAR(100) AFTER address,
ADD COLUMN IF NOT EXISTS postal_code VARCHAR(20) AFTER city,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Ajouter des colonnes manquantes à la table companies
ALTER TABLE companies 
ADD COLUMN IF NOT EXISTS website VARCHAR(255) AFTER phone,
ADD COLUMN IF NOT EXISTS industry VARCHAR(100) AFTER website,
ADD COLUMN IF NOT EXISTS siret VARCHAR(50) AFTER industry,
ADD COLUMN IF NOT EXISTS vat_number VARCHAR(50) AFTER siret,
ADD COLUMN IF NOT EXISTS annual_revenue VARCHAR(50) AFTER vat_number,
ADD COLUMN IF NOT EXISTS address VARCHAR(255) AFTER annual_revenue,
ADD COLUMN IF NOT EXISTS city VARCHAR(100) AFTER address,
ADD COLUMN IF NOT EXISTS postal_code VARCHAR(20) AFTER city,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Modifier employee_count et annual_revenue pour accepter des chaînes
ALTER TABLE companies 
MODIFY COLUMN employee_count VARCHAR(20) AFTER vat_number,
MODIFY COLUMN annual_revenue VARCHAR(50) AFTER employee_count;

-- Index pour optimiser les requêtes
CREATE INDEX IF NOT EXISTS idx_users_customer_id ON users(customer_id);
CREATE INDEX IF NOT EXISTS idx_companies_customer_id ON companies(customer_id);
CREATE INDEX IF NOT EXISTS idx_user_settings_user_id ON user_settings(user_id);

-- Mettre à jour les utilisateurs existants sans customer_id
-- (les forcer à passer par l'onboarding)
UPDATE users SET onboarding_completed = 0 WHERE customer_id IS NULL OR customer_id = 0;

-- Marquer comme complété ceux qui ont déjà un customer_id
UPDATE users SET onboarding_completed = 1 WHERE customer_id IS NOT NULL AND customer_id > 0;

-- Ajouter le module CRM par défaut pour les utilisateurs existants
INSERT IGNORE INTO user_settings (user_id, setting_key, setting_value, created_at, updated_at)
SELECT id, 'enabled_modules', '["crm"]', NOW(), NOW()
FROM users
WHERE onboarding_completed = 1;
