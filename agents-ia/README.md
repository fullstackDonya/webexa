# 🤖 Système d'Agents IA pour CRM

Intelligence artificielle au service de votre CRM - **Phase 1 implémentée**

## 📋 Vue d'ensemble

Ce système ajoute une couche d'intelligence artificielle au-dessus du CRM existant pour automatiser :

- ✅ **Analyse automatique des emails** (Inbox Agent)
- ✅ **Scoring et qualification des leads** (Lead Analyst Agent)
- 🚧 **Campagnes marketing** (à venir Phase 2)
- 🚧 **Gestion des missions** (à venir Phase 3)
- 🚧 **Rappels et tâches quotidiennes** (à venir Phase 4)

## 🏗️ Architecture

```
CRM (PHP/MySQL)
      ↓
  API REST interne
      ↓
  FastAPI Python
      ↓
  Agents IA + LLM
```

## 🚀 Installation

### Prérequis

- **Python 3.10+**
- **MySQL/MariaDB**
- **Redis** (optionnel pour Celery)
- **Clé API OpenAI ou Anthropic**

### Étape 1 : Configuration de l'environnement Python

```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm/ia

# Créer un environnement virtuel
python3 -m venv venv

# Activer l'environnement
source venv/bin/activate  # macOS/Linux
# ou
venv\Scripts\activate  # Windows

# Installer les dépendances
pip install -r requirements.txt
```

### Étape 2 : Configuration

```bash
# Copier le fichier d'exemple
cp .env.example .env

# Éditer le fichier .env
nano .env
```

**Variables importantes à configurer :**

```env
# Base de données (même config que le CRM)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=webitech
DB_USER=root
DB_PASSWORD=root

# Clé API du LLM (OpenAI ou Anthropic)
LLM_PROVIDER=openai
OPENAI_API_KEY=sk-your-api-key-here

# Sécurité API
API_SECRET_KEY=votre-cle-secrete-unique
```

### Étape 3 : Base de données

```bash
# Exécuter la migration SQL
mysql -u root -proot webitech < ../migrations/007_ai_agents_tables.sql
```

**Tables créées :**
- `agent_logs` - Historique des actions IA
- `agent_actions` - Actions proposées/exécutées
- `agent_permissions` - Permissions par customer
- `agent_memory` - Mémoire contextuelle
- `daily_tasks` - Tâches générées par IA

### Étape 4 : Démarrage de l'API Python

```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm/ia

# Activer l'environnement virtuel
source venv/bin/activate

# Lancer l'API FastAPI
python main.py
```

L'API sera accessible sur : **http://localhost:8000**

**Endpoints disponibles :**
- `GET /health` - Vérifier l'état du système
- `POST /api/inbox/analyze` - Analyser un email
- `POST /api/inbox/process-pending` - Traiter les emails en attente
- `POST /api/leads/score` - Scorer un lead
- `GET /api/leads/hot/{customer_id}` - Leads chauds
- `GET /api/actions/pending/{customer_id}` - Actions en attente

### Étape 5 : Accéder au dashboard

1. Ouvrir le CRM : `http://localhost/crm/`
2. Se connecter
3. Aller sur : **ai-dashboard.php**

## 📱 Interface utilisateur

### Dashboard IA (ai-dashboard.php)

**Statistiques en temps réel :**
- Nombre d'emails analysés
- Leads scorés
- Actions en attente
- Taux de succès

**Actions rapides :**
- 📧 Analyser les emails non traités
- 📊 Scorer automatiquement les leads
- 📝 Voir les logs détaillés
- ❤️ Vérifier l'état du système

**Actions proposées :**
- Chaque suggestion IA apparaît dans une carte
- Boutons **Approuver** / **Rejeter**
- Historique complet des décisions

## 🧠 Agents implémentés

### 1. Inbox Agent

**Capacités :**
- Lecture automatique des emails entrants
- Classification (prospect, relance, client, support, spam)
- Extraction d'informations (nom, téléphone, besoin, budget)
- Résumé automatique
- Analyse de sentiment
- Proposition de réponse
- Création automatique de leads

**Utilisation :**
```python
from agents import inbox_agent

result = inbox_agent.analyze_email(
    email_id=123,
    subject="Demande de devis",
    body="Bonjour, je souhaite...",
    sender="client@example.com",
    customer_id=1
)
```

### 2. Lead Analyst Agent

**Capacités :**
- Scoring automatique (0-100)
- Catégorisation (chaud/tiède/froid/mort)
- Analyse comportementale
- Priorisation des relances
- Détection des opportunités
- Recommandations d'actions

**Utilisation :**
```python
from agents import lead_analyst

result = lead_analyst.score_lead(
    lead_id=456,
    customer_id=1
)
```

## 🔧 Configuration avancée

### Modes d'automatisation

**1. Mode Assisté** (par défaut)
```env
AUTOMATION_MODE=assisted
```
- L'IA propose uniquement
- Validation manuelle requise

**2. Mode Semi-Auto**
```env
AUTOMATION_MODE=semi-auto
```
- L'IA agit sur actions simples
- Validation pour actions critiques

**3. Mode Autonome**
```env
AUTOMATION_MODE=autonomous
```
- L'IA agit sans validation
- Actions critiques nécessitent confirmation

### Activer/Désactiver les agents

Dans `.env` :
```env
INBOX_AGENT_ENABLED=true
LEAD_AGENT_ENABLED=true
WHATSAPP_AGENT_ENABLED=false
CAMPAIGN_AGENT_ENABLED=false
```

### Choisir le provider LLM

**OpenAI (recommandé) :**
```env
LLM_PROVIDER=openai
LLM_MODEL=gpt-4-turbo-preview
OPENAI_API_KEY=sk-...
```

**Anthropic Claude :**
```env
LLM_PROVIDER=anthropic
LLM_MODEL=claude-3-opus-20240229
ANTHROPIC_API_KEY=sk-ant-...
```

## 📊 Monitoring & Logs

### Logs en temps réel

Tous les logs sont stockés dans :
- Base de données : table agent_logs`
- Fichier : `ia/logs/agents.log`

### Consulter les logs

**Via PHP :**
```php
$aiClient = new AIAgentsClient();
$logs = $aiClient->getLogs('inbox_agent', 50);
```

**Via API Python :**
```bash
curl http://localhost:8000/api/logs?agent_name=inbox_agent&limit=50 \
  -H "X-API-Key: your-secret-key"
```

### Statistiques

Vue SQL disponible : `agent_stats`

```sql
SELECT * FROM agent_stats 
WHERE customer_id = 1 
ORDER BY date DESC 
LIMIT 30;
```

## 🔐 Sécurité

### Authentification API

L'API Python utilise une clé d'authentification :

```bash
# Dans .env
API_SECRET_KEY=votre-cle-unique-super-securisee
```

Toutes les requêtes doivent inclure le header :
```
X-API-Key: votre-cle-unique-super-securisee
```

### Permissions par customer

Chaque customer peut configurer :
- Agents autorisés
- Mode d'automatisation
- Actions autorisées

```sql
INSERT INTO agent_permissions 
(customer_id, agent_name, can_execute, automation_mode)
VALUES (1, 'inbox_agent', TRUE, 'semi-auto');
```

## 🧪 Tests

### Test de l'API

```bash
# Vérifier l'état
curl http://localhost:8000/health

# Analyser un email (exemple)
curl -X POST http://localhost:8000/api/inbox/analyze \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-secret-key" \
  -d '{
    "email_id": 1,
    "subject": "Test",
    "body": "Ceci est un test",
    "sender": "test@example.com",
    "customer_id": 1
  }'
```

### Test depuis le CRM

1. Accéder à **ai-dashboard.php**
2. Cliquer sur "État du système"
3. Vérifier que tous les agents sont actifs

## 📈 Roadmap

### Phase 1 - Base IA ✅ (Terminé)
- ✅ Connexion CRM ↔ Python
- ✅ Inbox Agent
- ✅ Lead Analyst Agent
- ✅ Dashboard UI

### Phase 2 - Compréhension métier 🚧 (En cours)
- 🚧 WhatsApp Agent
- 🚧 Campaign Agent
- 🚧 Apprentissage contextuel

### Phase 3 - Automatisation (À venir)
- Mission Agent
- Scheduler Agent
- Rappels quotidiens

### Phase 4 - Communication (À venir)
- Réponses automatiques emails
- Réponses WhatsApp
- Génération de contenu

### Phase 5 - Autonomie (À venir)
- Executor Agent
- Actions autonomes
- Workflow automation

### Phase 6 - Optimisation (À venir)
- Apprentissage comportement
- Optimisation continue
- Prédictions avancées

## ❓ Troubleshooting

### L'API Python ne démarre pas

```bash
# Vérifier Python
python3 --version  # Doit être 3.10+

# Vérifier les dépendances
pip list

# Réinstaller
pip install -r requirements.txt --upgrade
```

### Erreur de connexion DB

```python
# Tester la connexion
python -c "from database import db; print(db.execute('SELECT 1'))"
```

### L'IA ne propose pas d'actions

1. Vérifier que l'agent est activé dans `.env`
2. Vérifier les permissions dans `agent_permissions`
3. Consulter les logs : `ia/logs/agents.log`

### Erreur "LLM API Key invalid"

1. Vérifier la clé API dans `.env`
2. Vérifier les quotas OpenAI/Anthropic
3. Tester directement l'API du provider

## 📞 Support

Pour toute question ou problème :

1. Consulter les logs : `ia/logs/agents.log`
2. Vérifier la table `agent_logs` dans MySQL
3. Tester l'endpoint `/health` de l'API

## 📄 Licence

Propriétaire - Tous droits réservés

---

**Développé pour le CRM Webitech** 🚀
Version 1.0.0 - Phase 1 implémentée (Février 2026)
