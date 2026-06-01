# 🌐 Configuration Webexa - Deux Domaines

## Vue d'ensemble

L'application Webexa est déployée sur **deux domaines distincts**:

| Domaine | Application | Technologie | Port | Localisation VPS |
|---------|-------------|-------------|------|------------------|
| **webexa.fr** | CRM / ERP | PHP 7.4+ | 80/443 | `/var/www/webexa` |
| **webexa.online** | API Agents IA | Python FastAPI | 8000 | `/var/www/webexa-ai` |

---

## 📋 Architecture de Communication

```
┌─────────────────────────────────────────────────────────────────┐
│                    Client Browser                                │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                    ┌──────┴──────┐
                    ▼             ▼
         ┌────────────────┐  ┌─────────────────┐
         │  webexa.fr     │  │  webexa.online  │
         │  (Nginx)       │  │  (Nginx)        │
         └────────┬───────┘  └────────┬────────┘
                  │                   │
         ┌────────▼──────────┐       │
         │  PHP-FPM          │       │
         │  (:9000)          │       │
         │                   │       │
         │  CRM / ERP Code   │       │
         └────────┬──────────┘       │
                  │                  │
         ┌────────▼──────────────────▼──────────┐
         │   Shared MySQL Database                │
         │   (webexa_db / webexa_user)            │
         └──────────────────────────────────────┘
                           ▲
                           │
                  ┌────────┘
                  │
         ┌────────▼──────────────┐
         │  Gunicorn ASGI        │
         │  (127.0.0.1:8000)     │
         │                       │
         │  Python FastAPI       │
         │  Agents IA Code       │
         └───────────────────────┘
```

---

## 🔧 Configuration Fichiers

### 1️⃣ Fichier `.env` (webexa.fr)

**Localisation:** `/var/www/webexa/.env`

```bash
# ===== DATABASE =====
DB_HOST=localhost
DB_NAME=webexa
DB_USER=webexa_user
DB_PASS=your_secure_password

# ===== AI AGENTS CONFIGURATION =====
AI_API_BASE_URL=https://webexa.online
AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps

# ===== OTHER SETTINGS =====
APP_URL=https://webexa.fr
APP_ENV=production
LOG_LEVEL=warning
```

### 2️⃣ Fichier `.env` (webexa-ai)

**Localisation:** `/var/www/webexa-ai/agents-ia/.env`

```bash
# ===== DATABASE =====
DB_HOST=localhost
DB_NAME=webexa
DB_USER=webexa_user
DB_PASSWORD=your_secure_password

# ===== API SECURITY =====
API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
SECRET_KEY=your_secret_key_here

# ===== LLM INTEGRATIONS =====
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...

# ===== APPLICATION =====
APP_ENV=production
LOG_LEVEL=info
BASE_URL=https://webexa.online
```

---

## 🔗 Points d'Intégration

### PHP → Python API

Tous les appels de **webexa.fr** vers **webexa.online** passent par:

1. **Fichier client principal:** `/crm/api/AIAgentsClient.php`
   - Classe: `AIAgentsClient`
   - URL de base: `$_ENV['AI_API_BASE_URL']` (défaut: `https://webexa.online`)
   - Authentification: Header `X-API-Key`

2. **Endpoint de proxying:** `/crm/api/ai-agents.php`
   - Point d'entrée pour toutes les requêtes IA depuis le navigateur
   - Effectue l'authentification de l'utilisateur
   - Proxifie vers l'API Python

3. **Fichiers utilisant l'API IA:**
   - `/crm/ai-dashboard.php` - Dashboard IA
   - `/crm/ai-insights.php` - Analyses IA
   - `/crm/chat-assistant.php` - Assistant IA

### Endpoints Python API

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/health` | GET | Vérification santé API |
| `/api/inbox/analyze` | POST | Analyser un email |
| `/api/inbox/process-pending` | POST | Traiter les emails en attente |
| `/api/leads/score` | POST | Scorer un lead |
| `/api/leads/hot/{customer_id}` | GET | Récupérer les leads chauds |
| `/api/leads/cold/{customer_id}` | GET | Récupérer les leads froids |
| `/api/actions/pending/{customer_id}` | GET | Actions en attente |
| `/api/actions/update` | POST | Mettre à jour une action |

---

## 📡 Flux de Communication

### Exemple: Analyser un Email

```
1. User clicks "Analyze" in webexa.fr
   ↓
2. JavaScript sends POST to /crm/api/ai-agents.php?action=analyze-email
   ↓
3. PHP Backend (ai-agents.php)
   - Validate user session
   - Load AIAgentsClient
   - Call AIAgentsClient::analyzeEmail()
   ↓
4. AIAgentsClient (AIAgentsClient.php)
   - Prepare request data
   - Send CURL POST to https://webexa.online/api/inbox/analyze
   - Include header: X-API-Key: [KEY]
   ↓
5. Nginx on webexa.online
   - Route to Gunicorn (127.0.0.1:8000)
   ↓
6. FastAPI Application (agents-ia/main.py)
   - Validate API key
   - Process email analysis
   - Query shared MySQL database
   - Return results
   ↓
7. Response flows back through the chain
   ↓
8. JavaScript updates UI with results
```

---

## 🔐 Sécurité

### CORS (Cross-Origin Resource Sharing)

Nginx sur `webexa.online` accepte les requêtes de `webexa.fr`:

```nginx
add_header Access-Control-Allow-Origin "https://webexa.fr" always;
add_header Access-Control-Allow-Methods "GET, POST, OPTIONS" always;
add_header Access-Control-Allow-Headers "Content-Type, X-API-Key" always;
```

### Authentification API

- **Clé API:** Header `X-API-Key`
- **Valeur:** `bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps`
- **Stockage:** Variable d'environnement `AI_API_KEY`

### Authentification Session

- **webexa.fr:** Session PHP standard (`$_SESSION['customer_id']`)
- **webexa.online:** API Key validation seulement (pas de session)

---

## 🚀 Déploiement & Transfert de Fichiers

### 1. Transférer les fichiers

**Depuis votre machine locale:**

```bash
cd /Applications/MAMP/htdocs/webexa

# Transférer le code PHP/CRM + tous les fichiers
rsync -avz --exclude='vendor' --exclude='venv' --exclude='.git' \
  --exclude='__pycache__' --exclude='*.log' --exclude='.env' \
  . root@YOUR_VPS_IP:/var/www/webexa/
```

### 2. Configuration .env sur le VPS

```bash
# SSH sur le VPS
ssh root@YOUR_VPS_IP

# Naviguer vers webexa
cd /var/www/webexa

# Copier le template .env depuis local
# OU créer un nouveau .env avec les valeurs de production
nano .env
```

### 3. Installer les dépendances PHP

```bash
cd /var/www/webexa
composer install --optimize-autoloader --no-dev
```

### 4. Configurer la base de données

```bash
mysql -u root -p

mysql> CREATE DATABASE webexa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
mysql> CREATE USER 'webexa_user'@'localhost' IDENTIFIED BY 'secure_password';
mysql> GRANT ALL PRIVILEGES ON webexa.* TO 'webexa_user'@'localhost';
mysql> FLUSH PRIVILEGES;

mysql> USE webexa;
mysql> SOURCE /var/www/webexa/crm/crm_database.sql;
```

### 5. Configuration Python (webexa-ai)

```bash
# Créer le répertoire
mkdir -p /var/www/webexa-ai

# Transférer les fichiers
rsync -avz --exclude='venv' --exclude='__pycache__' \
  agents-ia/ root@YOUR_VPS_IP:/var/www/webexa-ai/

# SSH sur VPS
ssh root@YOUR_VPS_IP

# Créer venv
cd /var/www/webexa-ai
python3 -m venv venv
source venv/bin/activate

# Installer les dépendances
pip install -r requirements.txt

# Créer .env
nano .env
```

---

## 🧪 Tests de Connectivité

### Depuis webexa.fr

**Fichier:** `/crm/api/test-ai-connection.php`

```bash
# Accès depuis le navigateur
https://webexa.fr/crm/api/test-ai-connection.php
```

Teste:
- ✅ Connexion à l'API Python
- ✅ Validation de la clé API
- ✅ Endpoints disponibles

### Depuis la console Python

```bash
ssh root@YOUR_VPS_IP
cd /var/www/webexa-ai
source venv/bin/activate

python3 -c "
from agents.api_client import APIClient
client = APIClient(base_url='https://webexa.online')
print(client.health_check())
"
```

---

## 🐛 Dépannage

### Erreur: "API unreachable"

**Cause:** webexa.online ne répond pas

**Solutions:**
```bash
# 1. Vérifier que le service est démarré
systemctl status webexa-api

# 2. Vérifier le port
netstat -tlnp | grep 8000

# 3. Vérifier les logs
tail -f /var/log/nginx/webexa.online.error.log
tail -f /var/log/webexa/api.error.log

# 4. Tester la connexion
curl -H "X-API-Key: bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps" \
  https://webexa.online/health
```

### Erreur: "Invalid API Key"

**Cause:** Clés API non synchronisées

**Solutions:**
```bash
# 1. Vérifier la clé dans .env (webexa.fr)
grep AI_API_KEY /var/www/webexa/.env

# 2. Vérifier la clé dans .env (webexa-ai)
grep API_KEY /var/www/webexa-ai/.env

# 3. Vérifier qu'elles sont identiques
# 4. Redémarrer les services
systemctl restart webexa-api php-fpm nginx
```

### Erreur: "CORS not allowed"

**Cause:** Domaine autorisé non configuré

**Solution:** Vérifier `/etc/nginx/sites-available/webexa.online`
```nginx
add_header Access-Control-Allow-Origin "https://webexa.fr" always;
```

---

## ✅ Checklist de Déploiement

- [ ] Fichiers transférés sur le VPS
- [ ] `.env` créé sur webexa.fr avec `AI_API_BASE_URL=https://webexa.online`
- [ ] `.env` créé sur webexa-ai avec les credentials
- [ ] Base de données créée et importée
- [ ] PHP dependencies installées (`composer install`)
- [ ] Python venv créé et dépendances installées
- [ ] Services démarrés:
  - [ ] PHP-FPM
  - [ ] Nginx
  - [ ] Webexa API (systemd)
- [ ] DNS pointé vers le VPS:
  - [ ] webexa.fr
  - [ ] webexa.online
- [ ] SSL certificates installés (Let's Encrypt)
- [ ] Test de connectivité: `https://webexa.fr/crm/api/test-ai-connection.php`
- [ ] Test des features IA dans le CRM

---

## 📞 Support

Pour d'autres questions sur la configuration, consultez:
- [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) - Guide de déploiement complet
- [CHEAT_SHEET.md](CHEAT_SHEET.md) - Commandes utiles
- [DATABASE_SETUP.md](DATABASE_SETUP.md) - Configuration base de données
