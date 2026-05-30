-- Script pour corriger la configuration email incomplète
-- À exécuter après avoir remplacé les valeurs par les vraies

-- 1. Vérifier la configuration actuelle
SELECT id, email, imap_server, imap_port, smtp_host, smtp_port 
FROM email_configurations 
WHERE email = 'contact@webitech.fr' AND customer_id = 2;

-- 2. Mettre à jour avec les bons paramètres
-- IMPORTANT: Remplacez les valeurs par vos vrais paramètres

-- Pour Gmail:
UPDATE email_configurations 
SET 
    imap_server = 'imap.gmail.com',
    imap_port = 993,
    smtp_host = 'smtp.gmail.com',
    smtp_port = 587,
    provider = 'gmail'
WHERE email = 'contact@webitech.fr' AND customer_id = 2;

-- Pour Outlook/Office365:
-- UPDATE email_configurations 
-- SET 
--     imap_server = 'outlook.office365.com',
--     imap_port = 993,
--     smtp_host = 'smtp.office365.com',
--     smtp_port = 587,
--     provider = 'outlook'
-- WHERE email = 'contact@webitech.fr' AND customer_id = 2;

-- Pour OVH:
-- UPDATE email_configurations 
-- SET 
--     imap_server = 'ssl0.ovh.net',
--     imap_port = 993,
--     smtp_host = 'ssl0.ovh.net',
--     smtp_port = 587,
--     provider = 'custom'
-- WHERE email = 'contact@webitech.fr' AND customer_id = 2;

-- Pour un serveur custom (mail.votredomaine.fr):
-- UPDATE email_configurations 
-- SET 
--     imap_server = 'mail.webitech.fr',
--     imap_port = 993,
--     smtp_host = 'mail.webitech.fr',
--     smtp_port = 587,
--     provider = 'custom'
-- WHERE email = 'contact@webitech.fr' AND customer_id = 2;

-- 3. Vérifier que la mise à jour a fonctionné
SELECT id, email, imap_server, imap_port, smtp_host, smtp_port, provider
FROM email_configurations 
WHERE email = 'contact@webitech.fr' AND customer_id = 2;
