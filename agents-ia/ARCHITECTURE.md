# 🏗️ Architecture Technique du Système d'Agents IA

## Vue d'ensemble

```
┌─────────────────────────────────────────────────────────────┐
│                         UTILISATEUR                          │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                      CRM (Frontend PHP)                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Dashboard IA │  │   Emails     │  │    Leads     │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTP REST
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                API PHP (AIAgentsClient.php)                  │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  - analyzeEmail()                                    │   │
│  │  - scoreLead()                                       │   │
│  │  - getPendingActions()                                │   │
│  │  - approveAction() / rejectAction()                  │   │
│  └─────────────────────────────────────────────────────┘   │
└──────────────────────────┬──────────────────────────────────┘
                           │ HTTP + X-API-Key
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              API FastAPI Python (main.py)                    │
│  ┌───────────────────────────────────────────────────────┐ │
│  │  Routes:                                               │ │
│  │  - POST /api/inbox/analyze                            │ │
│  │  - POST /api/inbox/process-pending                    │ │
│  │  - POST /api/leads/score                              │ │
│  │  - GET  /api/leads/hot/{customer_id}                  │ │
│  │  - GET  /api/actions/pending/{customer_id}            │ │
│  │  - POST /api/actions/update                           │ │
│  └───────────────────────────────────────────────────────┘ │
└──────────────────┬───────────────┬──────────────────────────┘
                   │               │
                   ▼               ▼
    ┌──────────────────┐  ┌──────────────────┐
    │   Inbox Agent    │  │ Lead Analyst     │
    │                  │  │     Agent        │
    │ - analyze_email  │  │ - score_lead     │
    │ - extract_info   │  │ - detect_cold    │
    │ - create_lead    │  │ - detect_hot     │
    └────────┬─────────┘  └────────┬─────────┘
             │                     │
             └──────────┬──────────┘
                        ▼
             ┌────────────────────┐
             │   LLM Service      │
             │   (OpenAI/Claude)  │
             │                    │
             │ - chat()           │
             │ - analyze_email()  │
             │ - score_lead()     │
             │ - generate_response│
             └──────────┬─────────┘
                        │
        ┌───────────────┼───────────────┐
        ▼               ▼               ▼
   ┌─────────┐   ┌──────────┐   ┌──────────────┐
   │ MySQL   │   │  Logs    │   │ Vector DB    │
   │ Database│   │  Files   │   │  (ChromaDB)  │
   └─────────┘   └──────────┘   └──────────────┘
```

## Composants détaillés

### 1. Frontend (CRM PHP)

**Fichiers principaux :**
- `ai-dashboard.php` - Interface utilisateur principale
- `ai-logs.php` - Consultation des logs
- `assets/css/style.css` - Styles

**Fonctionnalités :**
- Affichage des statistiques IA
- Gestion des actions proposées
- Approbation/Rejet des suggestions
- Monitoring en temps réel

### 2. API PHP (Couche intermédiaire)

**Fichier : `api/AIAgentsClient.php`**

```php
class AIAgentsClient {
    private string $baseUrl = 'http://localhost:8000';
    private string $apiKey;
    
    public function analyzeEmail(...) { }
    public function scoreLead(...) { }
    public function getPendingActions(...) { }
}
```

**Rôle :**
- Bridge entre le CRM PHP et l'API Python
- Gestion des authentifications
- Transformation des données
- Gestion des erreurs

**Fichier : `api/ai-agents.php`**

```php
// Routeur API côté CRM
switch ($action) {
    case 'analyze-email': ...
    case 'score-lead': ...
    case 'pending-actions': ...
}
```

### 3. API FastAPI Python

**Fichier : `main.py`**

```python
@app.post("/api/inbox/analyze")
async def analyze_email(request: EmailAnalysisRequest):
    result = inbox_agent.analyze_email(...)
    return result
```

**Caractéristiques :**
- Asynchrone (FastAPI + Uvicorn)
- Authentification par X-API-Key
- Documentation auto-générée (/docs)
- Validation Pydantic
- Gestion d'erreurs centralisée

### 4. Agents IA

#### Inbox Agent (`agents/inbox_agent.py`)

```python
class InboxAgent:
    def analyze_email(self, email_id, subject, body, sender, customer_id):
        # 1. Analyse LLM
        analysis = llm.analyze_email(subject, body, sender)
        
        # 2. Recherche contact
        contact = self._find_contact(sender, customer_id)
        
        # 3. Création lead si nécessaire
        if analysis["type"] == "nouveau_prospect":
            lead_id = self._create_lead_from_email(...)
        
        # 4. Proposition de réponse
        if "répondre" in analysis["suggested_action"]:
            response = llm.generate_response(...)
        
        # 5. Création de l'action
        action_id = AgentAction.create(...)
        
        return result
```

**Capacités :**
- Classification (prospect, relance, client, support, spam)
- Extraction d'informations (nom, tel, besoin, budget)
- Analyse de sentiment
- Génération de réponses
- Création automatique de leads

#### Lead Analyst Agent (`agents/lead_analyst.py`)

```python
class LeadAnalystAgent:
    def score_lead(self, lead_id, customer_id):
        # 1. Récupération données enrichies
        lead_data = self._get_lead_data(lead_id, customer_id)
        
        # 2. Scoring IA
        score_result = llm.score_lead(lead_data)
        
        # 3. Mise à jour base
        self._update_lead_score(lead_id, score_result)
        
        # 4. Actions recommandées
        for action in score_result["recommended_actions"]:
            AgentAction.create(...)
        
        return result
```

**Capacités :**
- Scoring 0-100
- Catégorisation (chaud/tiède/froid/mort)
- Analyse comportementale
- Recommandations d'actions
- Détection leads chauds/froids

### 5. LLM Service (`llm_service.py`)

```python
class LLMService:
    def __init__(self):
        self.provider = "openai"  # ou "anthropic"
        self.client = OpenAI(api_key=...)
    
    def chat(self, messages, system=None, json_mode=False):
        # Appel API OpenAI/Anthropic
        response = self.client.chat.completions.create(...)
        return response.choices[0].message.content
    
    def analyze_email(self, subject, body, sender):
        # Prompt structuré pour analyse email
        prompt = f"""Analyse cet email et retourne un JSON avec:
        - type (prospect, relance, client, support, spam)
        - priority (haute, moyenne, basse)
        - sentiment (positif, neutre, negatif, urgent)
        - summary
        - extracted_info (nom, tel, besoin, budget, urgence)
        - suggested_action
        - confidence (0.0 à 1.0)
        """
        return json.loads(self.chat(...))
    
    def score_lead(self, lead_data):
        # Prompt pour scoring de lead
        prompt = f"""Analyse ce lead et retourne:
        - score (0-100)
        - category (chaud, tiede, froid, mort)
        - reasoning
        - strengths
        - weaknesses
        - recommended_actions
        - next_contact_delay
        """
        return json.loads(self.chat(...))
```

**Providers supportés :**
- OpenAI (GPT-4, GPT-3.5-turbo)
- Anthropic (Claude 3)
- Possibilité d'ajouter des modèles locaux

### 6. Base de données (`database.py`)

```python
class Database:
    def connect(self): ...
    def execute(self, query, params): ...
    def insert(self, query, params): ...
    def update(self, query, params): ...

class AgentLog:
    @staticmethod
    def create(agent_name, action, input_data, output_data, ...): ...
    
    @staticmethod
    def get_recent(agent_name=None, limit=50): ...

class AgentAction:
    @staticmethod
    def create(agent_name, action_type, target_type, ...): ...
    
    @staticmethod
    def get_pending(customer_id): ...
    
    @staticmethod
    def update_status(action_id, status, result): ...
```

**Tables MySQL :**
- `agent_logs` - Historique complet des actions IA
- `agent_actions` - Actions proposées/exécutées
- `agent_permissions` - Permissions par customer
- `agent_memory` - Mémoire contextuelle
- `daily_tasks` - Tâches générées
- `emails` - Colonnes ajoutées pour analyse IA
- `leads` - Colonnes ajoutées pour scoring

## Flux de données

### Flux 1 : Analyse d'un email

```
1. Email arrive → MySQL (emails table)
2. CRM détecte nouvel email
3. CRM → PHP API → Python API
4. Inbox Agent traite l'email
5. LLM Service analyse le contenu
6. Extraction des informations
7. Recherche/Création du lead
8. Génération de réponse suggérée
9. Création AgentAction (status: pending)
10. Retour à l'UI → Dashboard
11. Utilisateur approuve/rejette
12. AgentAction status → approved/rejected
```

### Flux 2 : Scoring d'un lead

```
1. Lead existant dans MySQL
2. Trigger : nouveau email OU demande manuelle
3. Lead Analyst Agent activé
4. Récupération données enrichies :
   - Lead de base
   - Historique interactions
   - Emails échangés
   - Ancienneté
5. LLM analyse les données
6. Calcul du score (0-100)
7. Catégorisation (chaud/tiède/froid/mort)
8. Génération d'actions recommandées
9. Mise à jour dans MySQL
10. Création AgentActions
11. Affichage dashboard
```

## Sécurité

### Authentification

**API Python :**
```python
async def verify_api_key(x_api_key: str = Header(None)):
    if x_api_key != API_CONFIG["secret_key"]:
        raise HTTPException(401, "Invalid API key")
```

**API PHP :**
```php
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-API-Key: ' . $this->apiKey
]);
```

### Permissions

```sql
-- Table agent_permissions
CREATE TABLE agent_permissions (
    customer_id INT,
    agent_name VARCHAR(50),
    can_read BOOLEAN DEFAULT TRUE,
    can_suggest BOOLEAN DEFAULT TRUE,
    can_execute BOOLEAN DEFAULT FALSE,
    automation_mode ENUM('assisted', 'semi-auto', 'autonomous')
);
```

### Traçabilité

Tous les appels sont loggés :
```python
AgentLog.create(
    agent_name="inbox_agent",
    action="analyze_email",
    input_data={"email_id": 123, "subject": "..."},
    output_data={"analysis": {...}, "lead_id": 456},
    status="success",
    customer_id=1
)
```

## Performance

### Optimisations

1. **Cache** : Résultats LLM mis en cache (agent_memory)
2. **Batch** : Traitement par lots des emails
3. **Async** : FastAPI asynchrone
4. **Index DB** : Index sur tous les champs critiques
5. **Timeout** : Timeouts configurables pour LLM

### Métriques

```sql
-- Vue agent_stats
SELECT 
    agent_name,
    date,
    COUNT(*) as total_actions,
    AVG(success_rate) as avg_success
FROM agent_logs
GROUP BY agent_name, date;
```

## Évolutivité

### Extension future

**Phase 2 : Nouveaux agents**
```python
# agents/whatsapp_agent.py
class WhatsAppAgent:
    def analyze_message(self, ...): ...

# agents/campaign_agent.py
class CampaignAgent:
    def create_campaign(self, ...): ...
```

**Phase 3 : Queue système**
```python
# Ajout Celery pour traitement asynchrone
@celery.task
def process_email_batch():
    inbox_agent.process_pending_emails(...)
```

**Phase 4 : Vector Database**
```python
# Ajout mémoire longue terme avec ChromaDB
from chromadb import Client

vector_db = Client()
collection = vector_db.create_collection("emails")
```

## Configuration

### Environnements

**Development :**
```env
ENV=development
LLM_PROVIDER=openai
LLM_MODEL=gpt-3.5-turbo  # Plus économique
LOG_LEVEL=DEBUG
```

**Production :**
```env
ENV=production
LLM_PROVIDER=openai
LLM_MODEL=gpt-4-turbo-preview  # Plus performant
LOG_LEVEL=INFO
API_SECRET_KEY=cle-production-ultra-securisee
```

## Maintenance

### Logs

```bash
# Logs Python
tail -f ia/logs/agents.log

# Logs MySQL
SELECT * FROM agent_logs 
WHERE status = 'error' 
ORDER BY created_at DESC;
```

### Monitoring

```bash
# Santé de l'API
curl http://localhost:8000/health

# Statistiques du jour
SELECT * FROM agent_stats WHERE date = CURDATE();
```

### Backup

```bash
# Backup des tables IA
mysqldump webitech agent_logs agent_actions agent_permissions > backup_ia.sql
```

---

**Architecture conçue pour :** Scalabilité, Maintenabilité, Sécurité, Performance
