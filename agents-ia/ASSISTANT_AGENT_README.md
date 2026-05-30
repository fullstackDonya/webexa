# Assistant Agent - Documentation

## Vue d'ensemble

L'**Assistant Agent** est un agent IA intelligent qui surveille automatiquement toutes les activités du **CRM et de l'ERP** et crée des notifications pour les actions importantes à effectuer.

⚠️ **Architecture** : L'agent utilise une seule base de données `webitech` qui contient à la fois les tables CRM et ERP.

## Fonctionnalités

### 1. Surveillance automatique

L'agent surveille en permanence :

**CRM :**
- **Emails** : Nouveaux emails non lus, emails urgents
- **WhatsApp** : Nouveaux messages WhatsApp entrants
- **Tâches** : Tâches à faire aujourd'hui et tâches en retard
- **Leads** : Leads chauds nécessitant une action immédiate
- **Appels** : Rappels d'appels programmés
- **Intégrations** : Synchronisation automatique des services tiers

**ERP :**
- **Shifts** : Horaires/shifts des employés à venir (dans les 24h)
- **Employés** : Informations sur les employés assignés aux shifts

### 2. Notifications intelligentes

L'agent crée automatiquement des notifications pour :

- 📧 Nouveaux emails importants (priorité haute/urgente)
- 💬 Nouveaux messages WhatsApp
- 📋 Tâches à effectuer aujourd'hui
- ⚠️ Tâches en retard
- 📞 Rappels d'appels
- � Shifts ERP à venir (moins de 2h)
- �🔥 Leads chauds à contacter
- ✅ Synchronisations réussies
- ❌ Erreurs d'intégration

### 3. Planification automatique

Le scheduler lance l'agent selon le planning suivant :

- **Vérification complète** : Chaque jour à 8h00
- **Vérification emails** : Toutes les heures
- **Synchronisation intégrations** : Toutes les 30 minutes

## Installation

### 1. Installer les dépendances Python

```bash
cd agents-ia
pip install -r requirements.txt
```

Ajouter `schedule` au fichier requirements.txt :

```
schedule==1.1.0
```

### 2. Créer/mettre à jour les tables de base de données

⚠️ **Important** : Cette migration utilise les tables existantes `emails` et `whatsapp_messages`. Elle ajoute seulement les colonnes manquantes pour l'agent assistant.

```bash
mysql -u root -p webitech < migrations/create_assistant_agent_tables.sql
```

**Tables utilisées** :
- ✅ `emails` (existante, colonnes ajoutées : `user_id`, `priority`, `status`, `is_replied`)
- ✅ `whatsapp_messages` (existante, colonnes ajoutées : `user_id`, `is_read`, `is_replied`)
- ✅ `erp_shifts` (table ERP existante, utilisée pour la surveillance des horaires)
- ✅ `erp_employees` (table ERP existante, utilisée pour les informations employés)
- ✨ `tasks` (nouvelle table créée)
- ✨ `call_reminders` (nouvelle table créée)

### 3. Tester l'agent

```bash
cd agents-ia
python test_assistant.py
```

### 4. Lancer le scheduler

```bash
cd agents-ia
python scheduler.py
```

Pour lancer en arrière-plan :

```bash
nohup python scheduler.py > logs/scheduler.log 2>&1 &
```

## Configuration

### Tables de base de données

L'assistant agent utilise les tables existantes du CRM et en crée de nouvelles pour les fonctionnalités spécifiques.

#### Tables existantes réutilisées :

**1. `emails`** (database/email_tables.sql)
- Stocke tous les emails synchronisés
- Colonnes ajoutées par la migration :
  - `user_id` : Utilisateur responsable
  - `priority` : ENUM('low', 'normal', 'high', 'urgent')
  - `status` : ENUM('new', 'inbox', 'archive', 'trash', 'spam')
  - `is_replied` : Indique si l'email a été traité

**2. `whatsapp_messages`** (database/migrations/002_add_whatsapp_support.sql)
- Stocke tous les messages WhatsApp
- Colonnes ajoutées par la migration :
  - `user_id` : Utilisateur responsable
  - `is_read` : Indique si le message a été lu
  - `is_replied` : Indique si on a répondu

**3. `integrations`** (table existante)
- Configurations des intégrations tierces
- Utilisée pour vérifier quelles intégrations sont actives

**4. `erp_shifts`** (🏭 table ERP, migrations/001_create_erp_tables.sql)
- Horaires/shifts des employés
- Surveillée pour créer des rappels de shifts à venir
- Colonnes utilisées : employee_id, start_datetime, end_datetime, role, customer_id

**5. `erp_employees`** (🏭 table ERP)
- Informations sur les employés
- JOIN avec erp_shifts pour obtenir les noms d'employés
- Colonnes : first_name, last_name, job_title

#### Tables créées par la migration :

**1. `tasks`**
- Gestion des tâches à faire
- Colonnes : title, description, due_date, priority, status, assigned_to

**2. `call_reminders`**
- Rappels d'appels programmés
- Colonnes : contact_name, phone, scheduled_time, status, notes

**3. Shifts ERP** 🏭

L'agent surveille automatiquement `erp_shifts` et `erp_employees` pour :
- Les shifts commençant dans les 24 prochaines heures
- Créer des notifications 2h avant le début du shift
- Priorité urgente si le shift commence dans moins de 30 min

Configuration :
- Table : `erp_shifts` (JOIN avec `erp_employees`)
- Lien dans notification : `../erp/shifts.php?id={shift_id}`
- Message : `{Employé} ({Rôle}) - Début: {Heure}`

### Variables d'environnement (.env)

```bash
# Base de données
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=root
DB_NAME=webitech

# Configuration de l'agent
ASSISTANT_DAILY_TIME=08:00
ASSISTANT_EMAIL_CHECK_INTERVAL=60  # minutes
ASSISTANT_SYNC_INTERVAL=30  # minutes
```

## Utilisation

### Exécution manuelle

```python
from agents.assistant_agent import assistant

# Vérification pour un client spécifique
result = assistant.run_daily_check(customer_id=22)

if result['success']:
    print(f"Notifications créées: {result['results']['notifications_created']}")
```

### Vérifications individuelles

```python
# Vérifier uniquement les emails
email_result = assistant._check_new_emails(customer_id)

# Vérifier uniquement WhatsApp
whatsapp_result = assistant._check_new_whatsapp(customer_id)

# Vérifier les tâches
tasks_result = assistant._check_tasks_due(customer_id)

# Vérifier les leads chauds
leads_result = assistant._check_hot_leads(customer_id)

# Synchroniser les intégrations
sync_result = assistant._synchronize_integrations(customer_id)
```

## Intégrations supportées

### Email (IMAP/POP3)
- **Table** : `emails` (existante)
- Synchronisation automatique des emails
- Détection des emails importants (colonnes : `priority`, `status`)
- Création de notifications pour les emails urgents
- **Colonnes utilisées** : `from_address`, `subject`, `body`, `email_date`, `is_read`, `priority`, `status`

### WhatsApp Business API
- **Table** : `whatsapp_messages` (existante)
- Réception des messages entrants
- Notifications en temps réel
- Historique des conversations
- **Colonnes utilisées** : `from_phone`, `contact_name`, `content`, `created_at`, `is_read`, `direction`

### Calendrier (Google Calendar, Outlook)
- Synchronisation des événements
- Rappels automatiques
- Gestion des conflits

### SMS (Twilio, etc.)
- Réception des SMS
- Notifications de nouveaux messages
- Historique des échanges

## Notifications créées

### Types de notifications

| Type | Description | Priorité | Icône |
|------|-------------|----------|-------|
| `email_new` | Nouvel email important | Medium/High | 📧 |
| `whatsapp_new` | Nouveau message WhatsApp | Medium | 💬 |
| `task_due` | Tâche à faire aujourd'hui | High | 📋 |
| `task_overdue` | Tâche en retard | Urgent | ⚠️ |
| `call_reminder` | Rappel d'appel | High | 📞 |
| `shift_reminder` | Shift ERP à venir | High | 🕐 |
| `lead_hot` | Lead chaud à contacter | Urgent | 🔥 |
| `integration_sync` | Synchronisation réussie | Low | ✅ |
| `integration_error` | Erreur d'intégration | Urgent | ❌ |

## Logs

Les logs sont enregistrés dans :

- `logs/scheduler.log` : Logs du scheduler
- `logs/assistant_agent.log` : Logs de l'agent assistant
- Table `agent_logs` : Historique complet dans la BDD

## Monitoring

### Vérifier le statut

```bash
# Voir les derniers logs
tail -f logs/scheduler.log

# Vérifier si le scheduler tourne
ps aux | grep scheduler.py

# Voir les notifications récentes
mysql -u root -p webitech -e "SELECT * FROM crm_notifications ORDER BY created_at DESC LIMIT 10"
```

### Statistiques

```python
# Obtenir les statistiques de la journée
from database import db

stats = db.execute_one("""
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent,
        SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read
    FROM crm_notifications
    WHERE DATE(created_at) = CURDATE()
    AND customer_id = 22
""")

print(f"Notifications aujourd'hui: {stats['total']}")
print(f"Urgentes: {stats['urgent']}")
print(f"Lues: {stats['read']}")
```

## Personnalisation

### Ajouter un nouveau type de surveillance

1. Créer une méthode `_check_xxx()` dans `assistant_agent.py`
2. Appeler cette méthode dans `run_daily_check()`
3. Définir le type de notification dans `notification_types`

Exemple :

```python
def _check_new_sms(self, customer_id: int) -> Dict[str, Any]:
    """Vérifier les nouveaux SMS"""
    try:
        new_sms = db.execute("""
            SELECT * FROM sms_messages 
            WHERE customer_id = %s 
            AND is_read = 0
        """, (customer_id,))
        
        notifications_created = 0
        for sms in new_sms:
            self._create_notification(
                customer_id=customer_id,
                type="sms_new",
                title=f"📱 Nouveau SMS",
                message=f"De: {sms['sender']}",
                link=f"sms.php?id={sms['id']}",
                priority="medium"
            )
            notifications_created += 1
        
        return {"count": len(new_sms), "notifications": notifications_created}
    except:
        return {"count": 0, "notifications": 0}
```

## Dépannage

### L'agent ne se lance pas

1. Vérifier les logs : `cat logs/scheduler.log`
2. Vérifier la connexion BDD : `python test-mysql-hostinger.py`
3. Vérifier les dépendances : `pip list`

### Pas de notifications créées

1. Vérifier qu'il y a des données de test : `SELECT * FROM crm_emails WHERE customer_id=22`
2. Tester manuellement : `python test_assistant.py`
3. Vérifier les erreurs dans `agent_logs`

### Intégrations qui échouent

1. Vérifier la configuration dans la table `integrations`
2. Vérifier les credentials API
3. Consulter `integration_logs` pour les détails

## Production

### Lancer avec systemd (Linux)

Créer `/etc/systemd/system/crm-assistant.service` :

```ini
[Unit]
Description=CRM Assistant Agent
After=network.target mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/crm/agents-ia
ExecStart=/usr/bin/python3 /var/www/crm/agents-ia/scheduler.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Activer et démarrer :

```bash
sudo systemctl enable crm-assistant
sudo systemctl start crm-assistant
sudo systemctl status crm-assistant
```

### Lancer avec cron

Ajouter au crontab :

```bash
# Vérification quotidienne à 8h00
0 8 * * * cd /var/www/crm/agents-ia && python scheduler.py

# Ou lancer le scheduler en permanence au démarrage
@reboot cd /var/www/crm/agents-ia && nohup python scheduler.py > logs/scheduler.log 2>&1 &
```

## Support

Pour toute question ou problème, consulter :
- Documentation principale : `INDEX_DOCUMENTATION.md`
- Logs de l'agent : `logs/scheduler.log`
- Logs BDD : Table `agent_logs`
