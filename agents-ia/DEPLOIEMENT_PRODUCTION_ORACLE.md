# 🚀 Déploiement des Agents IA en Production (Oracle Cloud)

## 📋 Pré-requis

- Serveur Oracle Cloud avec Python 3.10+
- MySQL/MariaDB configuré
- Accès SSH au serveur
- Domaine configuré (optionnel mais recommandé)

---

## 🔐 1. SÉCURITÉ - Générer une Clé API Forte

### Sur votre serveur de production :

```bash
# Générer une clé secrète forte (32 caractères minimum)
python3 -c "import secrets; print(secrets.token_urlsafe(32))"
```

**Exemple de sortie :** `xK9mP2nQ8vL4wR7jT5yU3aB6cE1fH0gI9dJ8kM2nO5pQ`

**⚠️ IMPORTANT :** Notez cette clé dans un gestionnaire de mots de passe sécurisé !

---

## 🗂️ 2. Configuration des fichiers

### A. Fichier `.env` Python (sur le serveur)

```bash
cd /chemin/vers/votre/crm/ia
nano .env
```

**Contenu du fichier `.env` pour PRODUCTION :**

```bash
# ============================================
# PRODUCTION CONFIGURATION
# ============================================

# Base de données Oracle
DB_HOST=votre-instance-oracle.cloud.com
DB_PORT=3306
DB_NAME=webitech_prod
DB_USER=crm_user
DB_PASSWORD=MotDePasseComplexeEtSecurise123!

# API Configuration
API_HOST=0.0.0.0
API_PORT=8000
API_SECRET_KEY=xK9mP2nQ8vL4wR7jT5yU3aB6cE1fH0gI9dJ8kM2nO5pQ  # ⚠️ CHANGEZ CETTE CLÉ !

# LLM Provider
LLM_PROVIDER=openai
OPENAI_API_KEY=sk-proj-VOTRE_VRAIE_CLE_OPENAI_ICI
ANTHROPIC_API_KEY=sk-ant-VOTRE_VRAIE_CLE_ANTHROPIC_ICI

# Chemins
VECTOR_DB_PATH=/var/lib/crm-ai/chromadb
LOG_FILE=/var/log/crm-ai/agents.log

# Redis (si disponible)
REDIS_URL=redis://localhost:6379/0

# Logging
LOG_LEVEL=INFO

# Agents activés
INBOX_AGENT_ENABLED=true
WHATSAPP_AGENT_ENABLED=false
LEAD_AGENT_ENABLED=true
CAMPAIGN_AGENT_ENABLED=true
MISSION_AGENT_ENABLED=false
SCHEDULER_AGENT_ENABLED=true
EXECUTOR_AGENT_ENABLED=false

# Mode d'automatisation
AUTOMATION_MODE=assisted  # assisted, semi-auto, autonomous

# Email Processing
EMAIL_BATCH_SIZE=20
EMAIL_PROCESS_INTERVAL=300  # 5 minutes

# Environnement
ENV=production
```

### B. Fichier `.env` PHP (dans le dossier CRM)

Créez/modifiez `/chemin/vers/votre/crm/.env` :

```bash
# Configuration API IA
AI_API_URL=https://votre-domaine.com:8000
AI_API_KEY=xK9mP2nQ8vL4wR7jT5yU3aB6cE1fH0gI9dJ8kM2nO5pQ  # ⚠️ LA MÊME CLÉ !
```

---

## 📦 3. Installation sur le serveur Oracle

### Étape 1 : Connexion SSH

```bash
ssh votre-utilisateur@votre-serveur-oracle.com
```

### Étape 2 : Préparation du serveur

```bash
# Mise à jour du système
sudo yum update -y  # Pour Oracle Linux
# OU
sudo apt update && sudo apt upgrade -y  # Pour Ubuntu

# Installation de Python 3.10+
sudo yum install python3.10 python3.10-pip python3.10-venv -y
# OU
sudo apt install python3.10 python3.10-venv python3-pip -y

# Installation de MySQL client
sudo yum install mysql -y
# OU
sudo apt install mysql-client -y
```

### Étape 3 : Création des dossiers

```bash
# Créer l'arborescence
sudo mkdir -p /var/www/crm/ia
sudo mkdir -p /var/lib/crm-ai/chromadb
sudo mkdir -p /var/log/crm-ai
sudo mkdir -p /etc/crm-ai

# Permissions
sudo chown -R www-data:www-data /var/www/crm
sudo chown -R www-data:www-data /var/lib/crm-ai
sudo chown -R www-data:www-data /var/log/crm-ai
```

### Étape 4 : Upload des fichiers

**Option A - Via SCP :**
```bash
# Depuis votre machine locale
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm
scp -r ia/ votre-utilisateur@votre-serveur:/var/www/crm/
```

**Option B - Via Git :**
```bash
# Sur le serveur
cd /var/www/crm
git clone https://votre-repo.git ia
cd ia
```

### Étape 5 : Installation des dépendances Python

```bash
cd /var/www/crm/ia

# Créer l'environnement virtuel
python3.10 -m venv venv

# Activer l'environnement
source venv/bin/activate

# Installer les dépendances
pip install --upgrade pip
pip install -r requirements.txt
```

---

## 🔧 4. Configuration du service systemd

Créez un service pour que l'API démarre automatiquement :

```bash
sudo nano /etc/systemd/system/crm-ai-agents.service
```

**Contenu :**

```ini
[Unit]
Description=CRM AI Agents API
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/crm/ia
Environment="PATH=/var/www/crm/ia/venv/bin"
ExecStart=/var/www/crm/ia/venv/bin/python -m uvicorn main:app --host 0.0.0.0 --port 8000 --workers 2
Restart=always
RestartSec=10
StandardOutput=append:/var/log/crm-ai/api.log
StandardError=append:/var/log/crm-ai/api-error.log

[Install]
WantedBy=multi-user.target
```

**Activer et démarrer le service :**

```bash
# Recharger systemd
sudo systemctl daemon-reload

# Activer au démarrage
sudo systemctl enable crm-ai-agents

# Démarrer le service
sudo systemctl start crm-ai-agents

# Vérifier le statut
sudo systemctl status crm-ai-agents
```

---

## 🌐 5. Configuration Nginx (Reverse Proxy)

### Installer Nginx

```bash
sudo yum install nginx -y
# OU
sudo apt install nginx -y
```

### Configurer le site

```bash
sudo nano /etc/nginx/sites-available/crm-ai
```

**Contenu :**

```nginx
upstream crm_ai_backend {
    server 127.0.0.1:8000;
}

server {
    listen 80;
    server_name votre-domaine.com;

    # Redirection HTTPS (optionnel mais recommandé)
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name votre-domaine.com;

    # Certificats SSL (Let's Encrypt recommandé)
    ssl_certificate /etc/letsencrypt/live/votre-domaine.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/votre-domaine.com/privkey.pem;

    # Sécurité SSL
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Logs
    access_log /var/log/nginx/crm-ai-access.log;
    error_log /var/log/nginx/crm-ai-error.log;

    # Proxy vers l'API FastAPI
    location /api/ {
        proxy_pass http://crm_ai_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }

    # Documentation API
    location /docs {
        proxy_pass http://crm_ai_backend;
        proxy_set_header Host $host;
    }

    # Health check
    location /health {
        proxy_pass http://crm_ai_backend;
    }
}
```

**Activer la configuration :**

```bash
# Créer le lien symbolique
sudo ln -s /etc/nginx/sites-available/crm-ai /etc/nginx/sites-enabled/

# Tester la configuration
sudo nginx -t

# Recharger Nginx
sudo systemctl reload nginx
```

---

## 🔒 6. Sécurisation du Firewall

### Oracle Cloud - Security Lists

1. Connectez-vous à Oracle Cloud Console
2. Allez dans **Networking → Virtual Cloud Networks**
3. Sélectionnez votre VCN → **Security Lists**
4. Ajoutez ces règles d'entrée :

```
Source: 0.0.0.0/0
Protocol: TCP
Port: 443 (HTTPS)
Description: API IA - HTTPS
```

```
Source: Votre IP CRM
Protocol: TCP
Port: 8000
Description: API IA directe (optionnel)
```

### Firewall serveur

```bash
# Firewalld (Oracle Linux)
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-port=8000/tcp
sudo firewall-cmd --reload

# UFW (Ubuntu)
sudo ufw allow 443/tcp
sudo ufw allow 8000/tcp
sudo ufw enable
```

---

## 📊 7. Monitoring et Logs

### Consulter les logs

```bash
# Logs du service
sudo journalctl -u crm-ai-agents -f

# Logs applicatifs
tail -f /var/log/crm-ai/agents.log
tail -f /var/log/crm-ai/api.log

# Logs Nginx
tail -f /var/log/nginx/crm-ai-access.log
```

### Script de monitoring

Créez `/usr/local/bin/crm-ai-monitor.sh` :

```bash
#!/bin/bash

# Vérifier si le service tourne
if ! systemctl is-active --quiet crm-ai-agents; then
    echo "⚠️ Service arrêté - Redémarrage..."
    systemctl restart crm-ai-agents
fi

# Vérifier la santé de l'API
response=$(curl -s http://localhost:8000/health)
if [ $? -ne 0 ]; then
    echo "⚠️ API non accessible - Redémarrage..."
    systemctl restart crm-ai-agents
fi
```

Ajoutez au crontab :
```bash
sudo crontab -e
# Ajouter :
*/5 * * * * /usr/local/bin/crm-ai-monitor.sh >> /var/log/crm-ai/monitor.log 2>&1
```

---

## 🔄 8. Mise à jour du Client PHP

Modifiez `/var/www/crm/api/AIAgentsClient.php` :

```php
public function __construct(
    string $baseUrl = null,
    ?string $apiKey = null,
    int $timeout = 30
) {
    // En production, lire depuis les variables d'environnement
    $this->baseUrl = rtrim($baseUrl ?? getenv('AI_API_URL') ?? 'https://votre-domaine.com', '/');
    $this->apiKey = $apiKey ?? getenv('AI_API_KEY') ?? '';
    
    if (empty($this->apiKey)) {
        throw new Exception('AI_API_KEY non configurée');
    }
    
    $this->timeout = $timeout;
}
```

---

## ✅ 9. Checklist de déploiement

- [ ] Clé API générée et sécurisée
- [ ] Fichiers `.env` configurés (Python et PHP)
- [ ] Base de données Oracle configurée et accessible
- [ ] Dépendances Python installées
- [ ] Service systemd configuré et démarré
- [ ] Nginx configuré avec SSL
- [ ] Firewall configuré
- [ ] Logs accessibles et moniteur actif
- [ ] Tests de connexion réussis
- [ ] Backup automatique configuré

---

## 🧪 10. Tests post-déploiement

```bash
# Test 1: Health check
curl https://votre-domaine.com/health

# Test 2: Avec authentification
curl -H "X-API-Key: VOTRE_CLE" https://votre-domaine.com/api/inbox/analyze

# Test 3: Depuis PHP
cd /var/www/crm/api
php test-ai-connection.php
```

---

## 🆘 Dépannage

### Service ne démarre pas
```bash
sudo journalctl -u crm-ai-agents -n 50
```

### Erreurs de base de données
```bash
mysql -h votre-db.com -u crm_user -p webitech_prod
SHOW TABLES;
```

### API inaccessible
```bash
netstat -tlnp | grep 8000
curl http://localhost:8000/health
```

---

## 📞 Support

En cas de problème, consultez les logs et vérifiez :
1. Service systemd actif
2. Connexion base de données
3. Clés API valides
4. Firewall ouvert
5. Nginx en écoute

---

**Date de création :** 26 février 2026  
**Version :** 1.0  
**Auteur :** Webitech CRM
