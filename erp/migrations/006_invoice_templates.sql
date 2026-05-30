-- Migration pour les modèles de factures  
-- Date: 2026-02-20

-- Note: Vérifier le nom de la base de données (webitech ou webitech_crm)
-- USE webitech_crm;

-- Table pour stocker les préférences de modèles de factures
CREATE TABLE IF NOT EXISTS erp_invoice_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    template_name VARCHAR(50) NOT NULL DEFAULT 'modern',
    primary_color VARCHAR(7) DEFAULT '#667eea',
    secondary_color VARCHAR(7) DEFAULT '#764ba2',
    logo_url VARCHAR(255),
    header_text TEXT,
    footer_text TEXT,
    show_logo BOOLEAN DEFAULT TRUE,
    show_qr_code BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les valeurs par défaut pour les clients existants
INSERT IGNORE INTO erp_invoice_settings (customer_id, template_name)
SELECT id, 'modern' FROM customers;

-- Index pour optimiser les recherches
CREATE INDEX idx_template_name ON erp_invoice_settings(template_name);
CREATE INDEX idx_customer_template ON erp_invoice_settings(customer_id, template_name);
