┌─────────────────────────┐
│  Assistant Agent (IA)   │
│  assistant_agent.py     │
└───────────┬─────────────┘
            │ _create_notification()
            │ INSERT INTO crm_notifications
            ▼
┌─────────────────────────────────────────┐
│  Table: crm_notifications                │
│  ┌───────────────────────────────────┐  │
│  │ id, customer_id, type, title,     │  │
│  │ message, icon, color, link,       │  │
│  │ priority, is_read, created_at     │  │
│  └───────────────────────────────────┘  │
└────────────┬────────────────────────────┘
             │
    ┌────────┴─────────┐
    │                  │
    ▼                  ▼
┌──────────────┐  ┌──────────────┐
│notifications │  │ api/         │
│    .php      │  │notifications │
│              │  │    .php      │
│ SELECT *     │  │ GET/POST/    │
│ WHERE        │  │ DELETE       │
│ customer_id  │  └──────────────┘
└──────────────┘
    Affichage
    utilisateur




1
Bien sûr ! Vérifions comment notifications.php affiche les notifications pour s'assurer que l'agent crée les bonnes données :

Parfait ! ✅ Les notifications de l'agent IA sont déjà configurées pour apparaître dans notifications.php. Voici le flux complet :

🔄 Flux des notifications
📋 Structure de la table crm_notifications
Définie dans create_notifications_system.sql :

CREATE TABLE crm_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    user_id INT NULL,
    type VARCHAR(50),           -- email_new, whatsapp_new, task_due, shift_reminder...
    title VARCHAR(255),          -- "📧 Nouvel email important"
    message TEXT,                -- "De: Jean Dupont - Demande de devis..."
    icon VARCHAR(50),            -- "fa-envelope", "fa-clock"
    color VARCHAR(20),           -- "info", "warning", "danger"
    link VARCHAR(500),           -- "emails-view.php?id=123", "../erp/shifts.php?id=5"
    is_read TINYINT(1),         -- 0 = non lu, 1 = lu
    priority ENUM,               -- 'low', 'medium', 'high', 'urgent'
    created_at TIMESTAMP,
    read_at TIMESTAMP
)


✅ Vérification complète
1. L'agent crée bien dans crm_notifications ✅

<?php
// api/notifications.php, ligne 25
SELECT * FROM crm_notifications WHERE customer_id = ?

3. Types de notifications créées par l'agent ✅

📧 email_new → Nouveaux emails importants
💬 whatsapp_new → Nouveaux messages WhatsApp
📋 task_due → Tâches à faire aujourd'hui
⚠️ task_overdue → Tâches en retard
📞 call_reminder → Rappels d'appels
🕐 shift_reminder → Shifts ERP à venir
🔥 lead_hot → Leads chauds à contacter
🧪 Test rapide
Pour vérifier que tout fonctionne :

cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm/agents-ia
python test_assistant.py

Puis ouvrez :

http://localhost/crm/notifications.php

1
Comment notifications.php affiche les notifications pour s'assurer que l'agent crée les bonnes données :

✅ Les notifications de l'agent IA sont déjà configurées pour apparaître dans notifications.php. Voici le flux complet :

🔄 Flux des notifications
📋 Structure de la table crm_notifications
Définie dans create_notifications_system.sql :

✅ Vérification complète
1. L'agent crée bien dans crm_notifications ✅

2. notifications.php lit bien depuis crm_notifications ✅

3. Types de notifications créées par l'agent ✅

📧 email_new → Nouveaux emails importants
💬 whatsapp_new → Nouveaux messages WhatsApp
📋 task_due → Tâches à faire aujourd'hui
⚠️ task_overdue → Tâches en retard
📞 call_reminder → Rappels d'appels
🕐 shift_reminder → Shifts ERP à venir
🔥 lead_hot → Leads chauds à contacter
🧪 Test rapide
Pour vérifier que tout fonctionne :

Puis ouvrez :

Vous verrez toutes les notifications créées par l'agent ! 