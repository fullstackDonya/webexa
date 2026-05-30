# Architecture CRM + ERP - Agents IA

## 🏗️ Vue d'ensemble

Les agents IA surveillent **à la fois le CRM et l'ERP** en utilisant une seule base de données **webitech** qui contient toutes les tables des deux systèmes.

```
┌─────────────────────────────────────────────────────────────┐
│                   Base de données: webitech                  │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌─────────────────────┐        ┌─────────────────────┐    │
│  │   Tables CRM        │        │   Tables ERP         │    │
│  ├─────────────────────┤        ├─────────────────────┤    │
│  │ • emails            │        │ • erp_shifts         │    │
│  │ • whatsapp_messages │        │ • erp_employees      │    │
│  │ • crm_notifications │        │ • erp_payrolls       │    │
│  │ • leads             │        │ • erp_companies      │    │
│  │ • tasks             │        │ • erp_sales          │    │
│  │ • call_reminders    │        │ • erp_stock          │    │
│  │ • integrations      │        │ • erp_inventory      │    │
│  └─────────────────────┘        └─────────────────────┘    │
│                                                               │
└─────────────────────────────────────────────────────────────┘
                            ▲
                            │
                    ┌───────┴────────┐
                    │  agents-ia/    │
                    │  database.py   │
                    └───────┬────────┘
                            │
            ┌───────────────┼───────────────┐
            │               │               │
    ┌───────▼────────┐ ┌───▼──────┐ ┌─────▼────────┐
    │ assistant_agent │ │ scheduler │ │ inbox_agent  │
    │  (surveillance) │ │  (cron)   │ │ (emails)     │
    └────────────────┘ └───────────┘ └──────────────┘
```

## 📊 Tables surveillées par l'Assistant Agent

### Tables CRM

| Table | Colonne clé | Surveillance | Notification |
|-------|-------------|--------------|--------------|
| `emails` | `email_date` | Emails < 24h, is_read=0 | Nouveaux emails importants |
| `whatsapp_messages` | `created_at` | Messages < 24h, is_read=0 | Nouveaux messages WhatsApp |
| `tasks` | `due_date` | Tasks du jour + en retard | Tâches à faire |
| `call_reminders` | `scheduled_time` | Appels du jour, status=pending | Rappels d'appel |
| `leads` | `ai_score`, `last_contact` | Score ≥ 80, contact > 3j | Leads chauds |
| `integrations` | `is_active` | Toutes actives | Synchronisation |

### Tables ERP 🏭

| Table | Colonne clé | Surveillance | Notification |
|-------|-------------|--------------|--------------|
| `erp_shifts` | `start_datetime` | Shifts < 24h | Shifts à venir |
| `erp_employees` | `id` | JOIN avec shifts | Nom de l'employé |

## 🔄 Flux de surveillance

### 1. Vérification quotidienne (08:00)

```python
assistant.run_daily_check(customer_id=22)
```

**Étapes** :
1. ✅ Vérifier nouveaux emails (CRM)
2. ✅ Vérifier messages WhatsApp (CRM)
3. ✅ Vérifier tâches dues/en retard (CRM)
4. ✅ Vérifier leads chauds (CRM)
5. ✅ Vérifier rappels d'appels (CRM)
6. ✅ **Vérifier shifts ERP à venir** (ERP)
7. ✅ Synchroniser intégrations (CRM)

### 2. Surveillance des shifts ERP

**Requête** :
```sql
SELECT s.id, s.start_datetime, s.end_datetime, s.role, s.notes,
       e.first_name, e.last_name, e.job_title
FROM erp_shifts s
JOIN erp_employees e ON s.employee_id = e.id
WHERE s.customer_id = %s
AND s.start_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
ORDER BY s.start_datetime ASC
```

**Logique de notification** :
- ⏰ Si shift dans **< 2h** → Notification priorité "high"
- 🚨 Si shift dans **< 30min** → Notification priorité "urgent"

**Format notification** :
```
Type: shift_reminder
Icône: fa-clock (🕐)
Titre: Shift à venir
Message: Jean Dupont (Chauffeur) - Début: 14:30
Lien: ../erp/shifts.php?id=123
```

## 🗂️ Structure des fichiers

```
crm/
├── agents-ia/
│   ├── agents/
│   │   ├── assistant_agent.py  ← Surveillance CRM+ERP
│   │   ├── inbox_agent.py      ← Emails uniquement
│   │   └── lead_analyst.py     ← Analyse leads
│   ├── database.py             ← Connexion unique à webitech
│   ├── scheduler.py            ← Planification automatique
│   ├── config.py               ← DB_CONFIG (webitech)
│   ├── test_assistant.py       ← Tests
│   ├── check_tables.py         ← Vérification tables CRM+ERP
│   └── ASSISTANT_AGENT_README.md
├── migrations/
│   └── create_assistant_agent_tables.sql
│
erp/
├── migrations/
│   └── 001_create_erp_tables.sql  ← Crée erp_shifts, erp_employees, etc.
└── shifts.php  ← Page de gestion des shifts
```

## 🔗 Liens entre CRM et ERP

### customer_id : Clé de liaison

Toutes les tables (CRM et ERP) ont une colonne `customer_id` qui permet de filtrer les données par entreprise cliente.

**Exemple** :
```sql
-- CRM : Emails du client 22
SELECT * FROM emails WHERE customer_id = 22;

-- ERP : Shifts du client 22
SELECT * FROM erp_shifts WHERE customer_id = 22;

-- Agent : Vérification pour le client 22
assistant.run_daily_check(customer_id=22)
```

### Navigation entre systèmes

Les notifications peuvent créer des liens vers :
- Pages CRM : `emails-view.php`, `leads-view.php`, `tasks.php`
- Pages ERP : `../erp/shifts.php`, `../erp/employees.php`

**Exemple dans le code** :
```python
# Notification CRM
link=f"emails-view.php?id={email['id']}"

# Notification ERP
link=f"../erp/shifts.php?id={shift['id']}"
```

## 📈 Évolutions futures

### Surveillances ERP supplémentaires

L'agent pourrait également surveiller :

1. **Paies en retard** (`erp_payrolls`)
   ```python
   def _check_pending_payrolls(self, customer_id: int):
       """Vérifier les bulletins de paie non générés"""
       # TODO: Implémenter
   ```

2. **Stock faible** (`erp_stock`)
   ```python
   def _check_low_stock(self, customer_id: int):
       """Alerter si stock < seuil"""
       # TODO: Implémenter
   ```

3. **Ventes du jour** (`erp_sales`)
   ```python
   def _summarize_daily_sales(self, customer_id: int):
       """Résumé des ventes quotidiennes"""
       # TODO: Implémenter
   ```

4. **Employés sans shift** (`erp_employees`)
   ```python
   def _check_employees_without_shifts(self, customer_id: int):
       """Employés actifs sans shifts planifiés"""
       # TODO: Implémenter
   ```

### Intégration bidirectionnelle

- **CRM → ERP** : Créer automatiquement un shift quand un lead devient client
- **ERP → CRM** : Créer un lead quand un employé signale un prospect
- **Notifications unifiées** : Dashboard unique CRM+ERP

## 🧪 Tests

### Vérifier les tables

```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm/agents-ia
python check_tables.py
```

**Vérifie** :
- ✅ Tables CRM (emails, whatsapp_messages, tasks, call_reminders)
- ✅ Tables ERP (erp_shifts, erp_employees)
- ✅ Colonnes requises dans chaque table

### Tester l'agent

```bash
python test_assistant.py
```

**Affiche** :
- Nouveaux emails
- Messages WhatsApp
- Tâches dues/en retard
- Leads chauds
- Rappels d'appels
- **Shifts ERP à venir** 🏭
- Intégrations synchronisées
- Notifications créées

## 🚀 Déploiement

### 1. Exécuter les migrations

```bash
# CRM
mysql -u root -proot webitech < crm/migrations/create_assistant_agent_tables.sql

# ERP déjà créé
# mysql -u root -proot webitech < erp/migrations/001_create_erp_tables.sql
```

### 2. Vérifier les tables

```bash
python check_tables.py
```

### 3. Tester

```bash
python test_assistant.py
```

### 4. Lancer le scheduler

```bash
# Premier plan
python scheduler.py

# Ou en arrière-plan
nohup python scheduler.py > logs/scheduler.log 2>&1 &
```

## 📚 Documentation

- [ASSISTANT_AGENT_README.md](ASSISTANT_AGENT_README.md) - Documentation complète de l'agent
- [database.py](database.py) - Gestion de la connexion à webitech
- [config.py](config.py) - Configuration (DB_CONFIG)

## 🎯 Résumé

✅ **Une seule base** : `webitech` contient CRM + ERP  
✅ **Un seul agent** : `assistant_agent.py` surveille les deux  
✅ **customer_id** : Clé de liaison entre tous les systèmes  
✅ **Surveillance ERP** : Shifts à venir avec rappels automatiques  
✅ **Notifications unifiées** : CRM et ERP dans le même système  
