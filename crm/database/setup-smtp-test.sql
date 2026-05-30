-- Script pour configurer une configuration SMTP de test
-- IMPORTANT: Remplacez les valeurs par vos vrais credentials SMTP

-- Pour Gmail (nécessite un mot de passe d'application)
INSERT INTO email_configurations (
    customer_id, 
    email, 
    smtp_host, 
    smtp_port, 
    smtp_user, 
    smtp_pass,
    smtp_from,
    is_active,
    created_at
) VALUES (
    1, -- Remplacez par votre customer_id (vérifier dans la table customers)
    'votre-email@gmail.com',
    'smtp.gmail.com',
    587,
    'votre-email@gmail.com',
    'votre-mot-de-passe-application', -- Créer un mot de passe d'application dans Gmail
    'votre-email@gmail.com',
    1,
    NOW()
);

-- Pour vérifier votre customer_id:
SELECT id, company_name FROM customers ORDER BY id DESC LIMIT 5;

-- Pour vérifier la config après insertion:
SELECT id, customer_id, email, smtp_host, is_active 
FROM email_configurations 
WHERE customer_id = 1;
