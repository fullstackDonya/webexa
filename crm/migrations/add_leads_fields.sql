-- Migration: Ajout des champs étendus pour l'import de leads
-- Date: 2026-07-29
-- Description: Ajoute les colonnes pour secteur, multiples téléphones, horaires, adresse, URL, tags, description

-- Vérifier et ajouter les colonnes manquantes à la table leads
ALTER TABLE leads ADD COLUMN IF NOT EXISTS phone2 VARCHAR(20) AFTER phone;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS phone3 VARCHAR(20) AFTER phone2;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS sector VARCHAR(100) AFTER company_id;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS opening_hours VARCHAR(255) AFTER sector;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS website VARCHAR(255) AFTER opening_hours;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS address VARCHAR(500) AFTER website;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS tags VARCHAR(255) AFTER address;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS description LONGTEXT AFTER tags;

-- Ajouter colonnes à la table companies
ALTER TABLE companies ADD COLUMN IF NOT EXISTS sector VARCHAR(100) AFTER name;
ALTER TABLE companies ADD COLUMN IF NOT EXISTS display_name VARCHAR(255) AFTER name;
ALTER TABLE companies ADD COLUMN IF NOT EXISTS tags VARCHAR(255) AFTER sector;

-- Créer index pour améliorer la recherche
ALTER TABLE leads ADD INDEX IF NOT EXISTS idx_email (email);
ALTER TABLE leads ADD INDEX IF NOT EXISTS idx_phone (phone);
ALTER TABLE leads ADD INDEX IF NOT EXISTS idx_sector (sector);
ALTER TABLE leads ADD INDEX IF NOT EXISTS idx_customer_id (customer_id);

-- Vérification
SELECT 'Migration complétée avec succès!' as status;
