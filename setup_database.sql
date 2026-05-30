-- ================================================
-- SETUP DATABASE & USER POUR WEBEXA
-- ================================================
-- Exécutez ce script avec un utilisateur administrateur (root)
-- 
-- IMPORTANT: Ce script doit être exécuté par root ou un utilisateur avec 
-- les permissions GRANT, CREATE DATABASE, etc.

-- 1. Créer la base de données webexa
CREATE DATABASE IF NOT EXISTS webexa 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- 2. Créer l'utilisateur webexa_user (si n'existe pas)
-- Remplacer 'secure_password_here' par un mot de passe fort!
CREATE USER IF NOT EXISTS 'webexa_user'@'localhost' IDENTIFIED BY 'secure_password_here';

-- 3. Accorder tous les privilèges sur la base webexa
GRANT ALL PRIVILEGES ON webexa.* TO 'webexa_user'@'localhost';

-- 4. Appliquer les changements
FLUSH PRIVILEGES;

-- 5. Vérifier les permissions
SHOW GRANTS FOR 'webexa_user'@'localhost';

-- ================================================
-- Afficher le résumé
-- ================================================
SELECT 'Base de données créée: webexa' AS status;
SELECT 'Utilisateur créé: webexa_user@localhost' AS status;
SELECT 'Permissions accordées: ALL sur webexa.*' AS status;
