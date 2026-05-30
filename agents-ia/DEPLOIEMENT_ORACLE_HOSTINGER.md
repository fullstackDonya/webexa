# 🚀 Déploiement Agents IA sur Oracle Cloud

## 📐 Architecture

```
┌─────────────────────────────────────────┐
│         HOSTINGER                       │
│  ┌──────────────┐   ┌───────────────┐  │
│  │   CRM PHP    │───│  MySQL 3306   │  │
│  │ (webitech)   │   │  (webitech)   │  │
│  └──────────────┘   └───────────────┘  │
│         ↓                               │
└─────────┼───────────────────────────────┘
          │ HTTP/HTTPS
          │ (via clé API)
          ↓
┌─────────────────────────────────────────┐
│       ORACLE CLOUD                      │
│  ┌──────────────────────────────────┐   │
│  │    Agents IA (FastAPI)           │   │
│  │    Port 8000                     │   │
│  │    /var/www/agents-ia/           │   │
│  └──────────────────────────────────┘   │
│         ↓ (connexion distante)          │
│         └────→ MySQL Hostinger          │
└─────────────────────────────────────────┘
```

---

## 📋 Pré-requis

### Sur Hostinger
- ✅ CRM déjà déployé et fonctionnel
- ✅ Base de données MySQL accessible
- ✅ Accès distant MySQL activé
- ✅ IP Oracle Cloud autorisée dans le firewall MySQL

### Sur Oracle Cloud
- Instance Oracle Linux / Ubuntu
- Python 3.10+
- Accès SSH
- Ports ouverts : 8000 (ou via Nginx 443)

---

## 🔧 1. Configuration Hostinger

### A. Autoriser l'accès distant MySQL

**Via cPanel Hostinger :**

1. Connexion à cPanel
2. **Bases de données** → **MySQL à distance**
3. Ajouter l'IP publique de votre instance Oracle
4. Notez le nom d'hôte MySQL (ex: `mysql123.hostinger.com`)

**Tester depuis Oracle Cloud :**
```bash
mysql -h mysql123.hostinger.com -P 3306 -u votre_user -p webitech
```

### B. Configurer le fichier .env du CRM (Hostinger)

Dans `/home/votre_compte/public_html/crm/.env` :

```bash
# API IA sur Oracle Cloud
AI_API_URL=https://votre-ip-oracle.com:8000
AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps
```

Ou modifiez `AIAgentsClient.php` :
```php
public function __construct(
    string $baseUrl = 'https://VOTRE_IP_ORACLE:8000',
    ?string $apiKey = 'bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps',
    int $timeout = 30
)
```

---

## 📦 2. Déploiement sur Oracle Cloud

### Étape 1 : Connexion SSH

```bash
ssh opc@votre-instance-oracle.com
# ou
ssh ubuntu@votre-instance-oracle.com
```

### Étape 2 : Installation des dépendances

```bash
# Mise à jour
sudo yum update -y  # Oracle Linux
# ou
sudo apt update && sudo apt upgrade -y  # Ubuntu

# Python 3.10
sudo yum install python3.10 python3.10-pip python3.10-venv -y
# ou
sudo apt install python3.10 python3.10-venv python3-pip -y

# MySQL client pour tester
sudo yum install mysql -y
# ou
sudo apt install mysql-client -y

# Nginx (optionnel pour HTTPS)
sudo yum install nginx -y
# ou
sudo apt install nginx -y
```

### Étape 3 : Créer l'arborescence

```bash
# Créer les dossiers
sudo mkdir -p /var/www/agents-ia
sudo mkdir -p /var/lib/crm-ai/chromadb
sudo mkdir -p /var/log/crm-ai

# Permissions
sudo chown -R opc:opc /var/www/agents-ia  # Oracle Linux
# ou
sudo chown -R ubuntu:ubuntu /var/www/agents-ia  # Ubuntu
```

### Étape 4 : Upload du dossier `ia`

**Option A - Via SCP (depuis votre machine locale) :**
```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm
scp -r ia/ opc@votre-instance-oracle:/var/www/agents-ia/
```

**Option B - Via Git :**
```bash
# Sur le serveur Oracle
cd /var/www/agents-ia
git clone https://votre-repo.git .
# Ou juste le dossier ia si c'est un sous-dossier
```

**Option C - Via archive :**
```bash
# Local
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm
tar -czf ia-deploy.tar.gz ia/
scp ia-deploy.tar.gz opc@oracle:/tmp/

# Sur Oracle
cd /var/www/agents-ia
tar -xzf /tmp/ia-deploy.tar.gz
mv ia/* .
rm -rf ia
```

### Étape 5 : Configuration

```bash
cd /var/www/agents-ia

# Copier et éditer .env
cp .env.production .env
nano .env
```

**Configurez ces valeurs dans `.env` :**

```bash
# Base de données Hostinger
DB_HOST=mysql123.hostinger.com  # Votre host MySQL Hostinger
DB_PORT=3306
DB_NAME=webitech  # Nom de votre BDD
DB_USER=votre_user_mysql  # Utilisateur MySQL Hostinger
DB_PASSWORD=votre_password  # Password MySQL Hostinger

# API
API_SECRET_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps  # LA MÊME que dans le CRM !

# Clés LLM
OPENAI_API_KEY=sk-proj-VOTRE_CLE...
ANTHROPIC_API_KEY=sk-ant-VOTRE_CLE...

# Environnement
ENV=production
```

### Étape 6 : Tester la connexion MySQL

```bash
# Test connexion
mysql -h mysql123.hostinger.com -u votre_user -p webitech

# Si ça fonctionne, testez depuis Python
python3.10 << EOF
from database import db
try:
    result = db.execute("SELECT COUNT(*) as count FROM emails")
    print(f"✅ Connexion réussie: {result}")
except Exception as e:
    print(f"❌ Erreur: {e}")
EOF
```

### Étape 7 : Installer les dépendances Python

```bash
cd /var/www/agents-ia

# Créer l'environnement virtuel
python3.10 -m venv venv

# Activer
source venv/bin/activate

# Installer
pip install --upgrade pip
pip install -r requirements.txt
```

### Étape 8 : Créer le service systemd

```bash
sudo nano /etc/systemd/system/agents-ia.service
```

**Contenu :**

```ini
[Unit]
Description=Agents IA CRM - FastAPI
After=network.target

[Service]
Type=simple
User=opc
Group=opc
WorkingDirectory=/var/www/agents-ia
Environment="PATH=/var/www/agents-ia/venv/bin"
ExecStart=/var/www/agents-ia/venv/bin/python -m uvicorn main:app --host 0.0.0.0 --port 8000 --workers 2
Restart=always
RestartSec=10
StandardOutput=append:/var/log/crm-ai/api.log
StandardError=append:/var/log/crm-ai/api-error.log

[Install]
WantedBy=multi-user.target
```

**Activer le service :**

```bash
sudo systemctl daemon-reload
sudo systemctl enable agents-ia
sudo systemctl start agents-ia

# Vérifier
sudo systemctl status agents-ia
```

### Étape 9 : Configurer le Firewall Oracle Cloud

**Via Console Oracle Cloud :**

1. **Networking** → **Virtual Cloud Networks**
2. Sélectionnez votre VCN
3. **Security Lists** → **Default Security List**
4. **Add Ingress Rule** :
   - Source CIDR: `0.0.0.0/0`
   - IP Protocol: `TCP`
   - Destination Port Range: `8000`

**Sur le serveur :**

```bash
# Firewalld (Oracle Linux)
sudo firewall-cmd --permanent --add-port=8000/tcp
sudo firewall-cmd --reload

# UFW (Ubuntu)
sudo ufw allow 8000/tcp
sudo ufw enable
```

### Étape 10 : Tester l'API

```bash
# Health check local
curl http://localhost:8000/health

# Depuis l'extérieur (depuis votre machine)
curl http://VOTRE_IP_ORACLE:8000/health

# Avec authentification
curl -H "X-API-Key: bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps" \
     http://VOTRE_IP_ORACLE:8000/api/actions/pending/22
```

---

## 🌐 3. Configuration Nginx (HTTPS - Recommandé)

### Installer Certbot

```bash
sudo yum install certbot python3-certbot-nginx -y
# ou
sudo apt install certbot python3-certbot-nginx -y
```

### Configurer Nginx

```bash
sudo nano /etc/nginx/conf.d/agents-ia.conf
```

**Contenu :**

```nginx
upstream agents_ia {
    server 127.0.0.1:8000;
}

server {
    listen 80;
    server_name votre-domaine-ou-ip.com;

    location / {
        proxy_pass http://agents_ia;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
```

**Activer :**

```bash
sudo nginx -t
sudo systemctl restart nginx

# SSL (si domaine configuré)
sudo certbot --nginx -d votre-domaine.com
```

---

## ✅ 4. Tests de bout en bout

### Test 1 : Depuis le serveur Oracle

```bash
curl http://localhost:8000/health
```

**Résultat attendu :**
```json
{
  "status": "healthy",
  "database": "connected",
  "agents": {...}
}
```

### Test 2 : Depuis Hostinger (CRM PHP)

Connectez-vous via SSH à Hostinger et testez :

```bash
curl -H "X-API-Key: bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps" \
     http://VOTRE_IP_ORACLE:8000/health
```

### Test 3 : Depuis le dashboard CRM

Accédez à : `https://votre-crm.com/ai-dashboard.php`

Les statistiques doivent se charger sans erreur.

---

## 📊 5. Monitoring et Maintenance

### Logs

```bash
# Logs du service
sudo journalctl -u agents-ia -f

# Logs applicatifs
tail -f /var/log/crm-ai/api.log
tail -f /var/log/crm-ai/api-error.log
```

### Commandes utiles

```bash
# Statut
sudo systemctl status agents-ia

# Redémarrer
sudo systemctl restart agents-ia

# Arrêter
sudo systemctl stop agents-ia

# Logs en temps réel
sudo journalctl -u agents-ia -f --since "5 minutes ago"
```

### Script de monitoring

```bash
sudo crontab -e
```

Ajouter :
```cron
*/5 * * * * systemctl is-active --quiet agents-ia || systemctl restart agents-ia
```

---

## 🔒 6. Sécurité

### Checklist

- [ ] IP Oracle autorisée dans MySQL Hostinger
- [ ] Firewall Oracle configuré (port 8000 ou 443 uniquement)
- [ ] Clé API identique dans CRM et agents IA
- [ ] Connexion MySQL en SSL si possible
- [ ] Logs de sécurité activés
- [ ] Certificat SSL configuré (Let's Encrypt)
- [ ] Backups automatiques du dossier `/var/www/agents-ia`

### Restreindre l'accès API

Si vous voulez que seul Hostinger puisse appeler l'API :

**Dans Nginx :**
```nginx
# Autoriser uniquement l'IP de Hostinger
allow IP_HOSTINGER;
deny all;
```

**Ou dans le firewall Oracle :**
```bash
sudo firewall-cmd --permanent --add-rich-rule='rule family="ipv4" source address="IP_HOSTINGER" port port="8000" protocol="tcp" accept'
sudo firewall-cmd --permanent --remove-port=8000/tcp
sudo firewall-cmd --reload
```

---

## 🆘 Dépannage

### Problème : API inaccessible depuis Hostinger

```bash
# Vérifier que le service tourne
sudo systemctl status agents-ia

# Vérifier le firewall
sudo firewall-cmd --list-all

# Tester depuis Oracle vers lui-même
curl http://localhost:8000/health
```

### Problème : Erreur connexion MySQL

```bash
# Tester la connexion
mysql -h mysql123.hostinger.com -u user -p

# Vérifier que l'IP Oracle est autorisée dans cPanel Hostinger
```

### Problème : Erreur 401 (Invalid API key)

- Vérifiez que la clé est **exactement la même** dans :
  - `/var/www/agents-ia/.env` (Oracle)
  - CRM `AIAgentsClient.php` ou `.env` (Hostinger)

---

## 📞 Résumé des URL

- **API IA** : `http://VOTRE_IP_ORACLE:8000`
- **Health check** : `http://VOTRE_IP_ORACLE:8000/health`
- **Documentation** : `http://VOTRE_IP_ORACLE:8000/docs`
- **CRM** : `https://votre-crm-hostinger.com`
- **MySQL** : `mysql123.hostinger.com:3306`

---

**Version :** 1.0  
**Date :** 26 février 2026  
**Architecture :** Oracle Cloud (IA) + Hostinger (CRM + BDD)
