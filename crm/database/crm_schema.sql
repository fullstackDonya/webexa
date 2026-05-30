
-- =============================================
-- CRM INTELLIGENT - SCHEMA COMPLET
-- Base de données: webitech
-- Date de création: 20 juillet 2025
-- =============================================

USE webitech;

-- =============================================
-- TABLES UTILISATEURS ET AUTHENTIFICATION
-- =============================================

-- Mise à jour de la table users existante (ajout des colonnes manquantes)
-- Note: Exécutez ces commandes une par une si certaines colonnes existent déjà

-- Ajout des colonnes pour les informations personnelles
ALTER TABLE users ADD COLUMN first_name VARCHAR(50) DEFAULT '' AFTER password;
ALTER TABLE users ADD COLUMN last_name VARCHAR(50) DEFAULT '' AFTER first_name;

-- Ajout des colonnes pour les rôles et permissions
ALTER TABLE users ADD COLUMN role ENUM('admin', 'manager', 'sales', 'support') DEFAULT 'sales' AFTER last_name;

-- Ajout des colonnes pour le profil utilisateur
ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER role;
ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER avatar;
ALTER TABLE users ADD COLUMN department VARCHAR(50) DEFAULT NULL AFTER phone;

-- Ajout des colonnes pour le statut et sécurité
ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER department;
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER is_active;
ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL AFTER last_login;

-- Ajout des colonnes de timestamps
ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER email_verified_at;
ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;


-- Table des sessions utilisateur
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- TABLES CLIENTS ET CONTACTS
-- =============================================

-- Table des entreprises clientes
CREATE TABLE IF NOT EXISTS companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    industry VARCHAR(50) DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(50) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    country VARCHAR(50) DEFAULT 'France',
    employee_count INT DEFAULT NULL,
    annual_revenue DECIMAL(15,2) DEFAULT NULL,
    status ENUM('prospect', 'client', 'partner', 'inactive') DEFAULT 'prospect',
    source VARCHAR(50) DEFAULT NULL,
    assigned_to INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des contacts
CREATE TABLE IF NOT EXISTS contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT DEFAULT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    mobile VARCHAR(20) DEFAULT NULL,
    position VARCHAR(100) DEFAULT NULL,
    department VARCHAR(50) DEFAULT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    linkedin_url VARCHAR(255) DEFAULT NULL,
    birthday DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    status ENUM('active', 'inactive', 'do_not_contact') DEFAULT 'active',
    assigned_to INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- TABLES OPPORTUNITÉS ET VENTES
-- =============================================

-- Table des opportunités
CREATE TABLE IF NOT EXISTS opportunities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    company_id INT DEFAULT NULL,
    contact_id INT DEFAULT NULL,
    assigned_to INT NOT NULL,
    stage ENUM('qualification', 'needs_analysis', 'proposal', 'negotiation', 'closed_won', 'closed_lost') DEFAULT 'qualification',
    probability INT DEFAULT 0 CHECK (probability BETWEEN 0 AND 100),
    amount DECIMAL(15,2) DEFAULT 0.00,
    expected_close_date DATE DEFAULT NULL,
    actual_close_date DATE DEFAULT NULL,
    source VARCHAR(50) DEFAULT NULL,
    competitor VARCHAR(100) DEFAULT NULL,
    loss_reason TEXT DEFAULT NULL,
    next_action TEXT DEFAULT NULL,
    next_action_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des produits/services
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    sku VARCHAR(50) UNIQUE DEFAULT NULL,
    category VARCHAR(50) DEFAULT NULL,
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    cost_price DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des devis
CREATE TABLE IF NOT EXISTS quotes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quote_number VARCHAR(50) UNIQUE NOT NULL,
    opportunity_id INT DEFAULT NULL,
    company_id INT NOT NULL,
    contact_id INT DEFAULT NULL,
    assigned_to INT NOT NULL,
    status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired') DEFAULT 'draft',
    total_amount DECIMAL(15,2) DEFAULT 0.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    valid_until DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    terms_conditions TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des lignes de devis
CREATE TABLE IF NOT EXISTS quote_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quote_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    description TEXT NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1.00,
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    discount_percent DECIMAL(5,2) DEFAULT 0.00,
    total_price DECIMAL(15,2) DEFAULT 0.00,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- =============================================
-- TABLES ACTIVITÉS ET COMMUNICATIONS
-- =============================================

-- Table des activités
CREATE TABLE IF NOT EXISTS activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('call', 'email', 'meeting', 'task', 'note', 'demo', 'follow_up') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    company_id INT DEFAULT NULL,
    contact_id INT DEFAULT NULL,
    opportunity_id INT DEFAULT NULL,
    assigned_to INT NOT NULL,
    due_date DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'overdue') DEFAULT 'scheduled',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    duration_minutes INT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    outcome TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des emails
CREATE TABLE IF NOT EXISTS emails (
    id INT PRIMARY KEY AUTO_INCREMENT,
    activity_id INT DEFAULT NULL,
    from_email VARCHAR(255) NOT NULL,
    to_email VARCHAR(255) NOT NULL,
    cc_email TEXT DEFAULT NULL,
    bcc_email TEXT DEFAULT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT DEFAULT NULL,
    body_text TEXT DEFAULT NULL,
    message_id VARCHAR(255) DEFAULT NULL,
    thread_id VARCHAR(255) DEFAULT NULL,
    is_outbound BOOLEAN DEFAULT TRUE,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE SET NULL
);

-- Table des pièces jointes
CREATE TABLE IF NOT EXISTS attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    related_type ENUM('email', 'activity', 'quote', 'company', 'contact', 'opportunity') NOT NULL,
    related_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- TABLES PIPELINE ET RAPPORTS
-- =============================================

-- Table des étapes de pipeline
CREATE TABLE IF NOT EXISTS pipeline_stages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    order_position INT NOT NULL,
    probability_default INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    color_code VARCHAR(7) DEFAULT '#007bff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des objectifs
CREATE TABLE IF NOT EXISTS targets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('revenue', 'deals', 'calls', 'meetings') NOT NULL,
    period ENUM('monthly', 'quarterly', 'yearly') NOT NULL,
    target_value DECIMAL(15,2) NOT NULL,
    achieved_value DECIMAL(15,2) DEFAULT 0.00,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- TABLES POWER BI ET ANALYTICS
-- =============================================

-- Table des rapports Power BI
CREATE TABLE IF NOT EXISTS powerbi_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    report_id VARCHAR(255) NOT NULL,
    workspace_id VARCHAR(255) NOT NULL,
    embed_url TEXT NOT NULL,
    access_token TEXT DEFAULT NULL,
    token_expires_at DATETIME DEFAULT NULL,
    category VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    allowed_roles JSON DEFAULT NULL,
    refresh_schedule VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des métriques analytics
CREATE TABLE IF NOT EXISTS analytics_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15,2) NOT NULL,
    metric_date DATE NOT NULL,
    user_id INT DEFAULT NULL,
    company_id INT DEFAULT NULL,
    opportunity_id INT DEFAULT NULL,
    category VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE SET NULL
);

-- =============================================
-- TABLES CONFIGURATION ET LOGS
-- =============================================

-- Table des paramètres système
CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT NULL,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT DEFAULT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des logs d'activité
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) DEFAULT NULL,
    record_id INT DEFAULT NULL,
    old_values JSON DEFAULT NULL,
    new_values JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT DEFAULT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    related_type VARCHAR(50) DEFAULT NULL,
    related_id INT DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- INSERTION DES DONNÉES INITIALES
-- =============================================

-- Insertion des étapes de pipeline par défaut (seulement si n'existent pas déjà)
INSERT IGNORE INTO pipeline_stages (name, order_position, probability_default, color_code) VALUES
('Qualification', 1, 10, '#6c757d'),
('Analyse des besoins', 2, 25, '#17a2b8'),
('Proposition', 3, 50, '#ffc107'),
('Négociation', 4, 75, '#fd7e14'),
('Fermé gagné', 5, 100, '#28a745'),
('Fermé perdu', 6, 0, '#dc3545');

-- Insertion des paramètres système par défaut (seulement si n'existent pas déjà)
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('company_name', 'Webitech CRM', 'string', 'Nom de l\'entreprise', TRUE),
('company_email', 'contact@webitech.com', 'string', 'Email principal de l\'entreprise', TRUE),
('company_phone', '+33 1 23 45 67 89', 'string', 'Téléphone de l\'entreprise', TRUE),
('default_currency', 'EUR', 'string', 'Devise par défaut', TRUE),
('timezone', 'Europe/Paris', 'string', 'Fuseau horaire par défaut', TRUE),
('powerbi_auto_refresh', '1', 'boolean', 'Rafraîchissement automatique Power BI', FALSE),
('powerbi_refresh_interval', '30', 'integer', 'Intervalle de rafraîchissement en minutes', FALSE),
('email_notifications', '1', 'boolean', 'Notifications par email activées', TRUE),
('max_file_upload_size', '10485760', 'integer', 'Taille max upload en bytes (10MB)', FALSE);

-- Insertion d'un utilisateur admin par défaut (seulement si n'existe pas déjà)
INSERT IGNORE INTO users (username, email, password, first_name, last_name, role, phone, department, is_active) VALUES
('admin', 'admin@webitech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'System', 'admin', NULL, 'IT', TRUE);

-- Insertion de quelques rapports Power BI par défaut (seulement si n'existent pas déjà)
INSERT IGNORE INTO powerbi_reports (name, report_id, workspace_id, embed_url, category, description) VALUES
('Dashboard Ventes', 'sample-report-1', 'sample-workspace-1', 'https://app.powerbi.com/reportEmbed', 'sales', 'Tableau de bord principal des ventes'),
('Analyse Clients', 'sample-report-2', 'sample-workspace-1', 'https://app.powerbi.com/reportEmbed', 'customers', 'Analyse détaillée de la base clients'),
('Performance Équipe', 'sample-report-3', 'sample-workspace-1', 'https://app.powerbi.com/reportEmbed', 'team', 'Suivi des performances de l\'équipe commerciale'),
('Prévisions', 'sample-report-4', 'sample-workspace-1', 'https://app.powerbi.com/reportEmbed', 'forecasting', 'Prévisions de ventes et tendances');

-- =============================================
-- INDEX POUR OPTIMISER LES PERFORMANCES
-- =============================================

-- Index sur les colonnes fréquemment utilisées (seulement si les tables existent)
-- Note: Exécutez ces commandes une par une après avoir créé toutes les tables

-- Index pour la table companies
CREATE INDEX IF NOT EXISTS idx_companies_status ON companies(status);
CREATE INDEX IF NOT EXISTS idx_companies_assigned_to ON companies(assigned_to);

-- Index pour la table contacts
CREATE INDEX IF NOT EXISTS idx_contacts_company_id ON contacts(id);
CREATE INDEX IF NOT EXISTS idx_contacts_email ON contacts(email);

-- Index pour la table opportunities
CREATE INDEX IF NOT EXISTS idx_opportunities_stage ON opportunities(stage);
CREATE INDEX IF NOT EXISTS idx_opportunities_assigned_to ON opportunities(assigned_to);
CREATE INDEX IF NOT EXISTS idx_opportunities_close_date ON opportunities(expected_close_date);

-- Index pour la table activities
CREATE INDEX IF NOT EXISTS idx_activities_assigned_to ON activities(assigned_to);
CREATE INDEX IF NOT EXISTS idx_activities_due_date ON activities(due_date);
CREATE INDEX IF NOT EXISTS idx_activities_status ON activities(status);

-- Index pour la table activity_logs
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs(created_at);

-- Index pour la table analytics_metrics
CREATE INDEX IF NOT EXISTS idx_analytics_metric_date ON analytics_metrics(metric_date);

-- Index pour la table notifications
CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read);
