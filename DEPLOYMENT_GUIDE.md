# 📋 Guide de Déploiement Webexa sur VPS

## 📌 Architecture Cible

```
/var/www/
├── html/              (Site existant)
├── siteo/             (Site existant)
├── webexa/            (Application PHP - webexa.fr)
│   ├── crm/
│   ├── erp/
│   ├── forms/
│   ├── assets/
│   ├── src/
│   ├── vendor/
│   ├── index.php
│   └── .env
└── webexa-ai/         (Application Python IA - webexa.online)
    ├── agents-ia/
    ├── venv/
    ├── .env
    └── start-api.sh
```

## 🚀 Étape 1: Préparation du VPS

### 1.1 Connexion SSH
```bash
ssh root@your-vps-ip
```

### 1.2 Création des répertoires
```bash
cd /var/www

# Créer le répertoire pour webexa.fr (PHP)
mkdir -p webexa
chmod 755 webexa

# Créer le répertoire pour webexa.online (Python)
mkdir -p webexa-ai
chmod 755 webexa-ai

# Changer les permissions pour le serveur web
chown -R www-data:www-data webexa
chown -R www-data:www-data webexa-ai
```

## 📦 Étape 2: Déploiement de la Partie PHP (webexa.fr)

### 2.1 Copier les fichiers
```bash
# Depuis votre machine locale
scp -r /Applications/MAMP/htdocs/webexa/* root@your-vps:/var/www/webexa/

# OU utiliser Git
cd /var/www/webexa
git clone https://your-repo-url.git .
```

### 2.2 Installer les dépendances PHP
```bash
cd /var/www/webexa
composer install --optimize-autoloader --no-dev
```

### 2.3 Configuration de l'environnement
```bash
cp .env.example .env
# Éditer .env avec vos paramètres (BDD, API, etc.)
nano .env
```

### 2.4 Permissions
```bash
chmod 755 /var/www/webexa
chmod 755 /var/www/webexa/{crm,erp,forms,assets}
chmod 644 /var/www/webexa/*.php
find /var/www/webexa -name "*.php" -type f -exec chmod 644 {} \;
```

## 🐍 Étape 3: Déploiement de la Partie Python (webexa.online)

### 3.1 Copier les fichiers IA
```bash
# Copier uniquement agents-ia
scp -r /Applications/MAMP/htdocs/webexa/agents-ia root@your-vps:/var/www/webexa-ai/

# OU
cp -r /var/www/webexa/agents-ia /var/www/webexa-ai/
rm -rf /var/www/webexa/agents-ia  # Optionnel: supprimer du dossier PHP
```

### 3.2 Créer l'environnement virtuel Python
```bash
cd /var/www/webexa-ai
python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install -r agents-ia/requirements.txt
```

### 3.3 Configuration de l'environnement Python
```bash
cp agents-ia/.env.example agents-ia/.env
# Éditer la configuration
nano agents-ia/.env
```

### 3.4 Créer le script de démarrage
```bash
cat > /var/www/webexa-ai/start-api.sh << 'EOF'
#!/bin/bash
cd /var/www/webexa-ai
source venv/bin/activate
cd agents-ia
gunicorn main:app --bind 127.0.0.1:8000 --workers 4 --worker-class uvicorn.workers.UvicornWorker
EOF

chmod +x /var/www/webexa-ai/start-api.sh
```

### 3.5 Installer Gunicorn
```bash
cd /var/www/webexa-ai
source venv/bin/activate
pip install gunicorn
```

## 🌐 Étape 4: Configuration Nginx

### 4.1 Configuration pour webexa.fr (PHP)
```bash
cat > /etc/nginx/sites-available/webexa.fr << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name webexa.fr www.webexa.fr;

    root /var/www/webexa;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/webexa.fr.access.log;
    error_log /var/log/nginx/webexa.fr.error.log;

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ ~$ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # PHP FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # Rewrite rules
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
EOF
```

### 4.2 Configuration pour webexa.online (Python)
```bash
cat > /etc/nginx/sites-available/webexa.online << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name webexa.online www.webexa.online;

    # Logs
    access_log /var/log/nginx/webexa.online.access.log;
    error_log /var/log/nginx/webexa.online.error.log;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
    }
}
EOF
```

### 4.3 Activer les sites
```bash
ln -s /etc/nginx/sites-available/webexa.fr /etc/nginx/sites-enabled/
ln -s /etc/nginx/sites-available/webexa.online /etc/nginx/sites-enabled/

# Tester la configuration
nginx -t

# Redémarrer Nginx
systemctl restart nginx
```

## 🔐 Étape 5: SSL Let's Encrypt (HTTPS)

```bash
# Installer certbot
apt-get install certbot python3-certbot-nginx -y

# Générer certificats
certbot --nginx -d webexa.fr -d www.webexa.fr
certbot --nginx -d webexa.online -d www.webexa.online

# Renouvellement automatique
systemctl enable certbot.timer
```

## 🔄 Étape 6: Service Systemd pour l'API Python

```bash
cat > /etc/systemd/system/webexa-api.service << 'EOF'
[Unit]
Description=Webexa AI API (FastAPI)
After=network.target
Wants=webexa-api.service

[Service]
Type=notify
User=www-data
WorkingDirectory=/var/www/webexa-ai
Environment="PATH=/var/www/webexa-ai/venv/bin"
ExecStart=/var/www/webexa-ai/start-api.sh
Restart=always
RestartSec=10
StandardOutput=append:/var/log/webexa-api.log
StandardError=append:/var/log/webexa-api.error.log

[Install]
WantedBy=multi-user.target
EOF

# Activer et démarrer le service
systemctl daemon-reload
systemctl enable webexa-api.service
systemctl start webexa-api.service

# Vérifier le statut
systemctl status webexa-api.service
```

## ✅ Étape 7: Vérification

```bash
# 1. Vérifier les répertoires
ls -la /var/www/webexa/
ls -la /var/www/webexa-ai/

# 2. Vérifier Nginx
curl -I http://webexa.fr
curl -I http://webexa.online

# 3. Vérifier l'API Python
curl http://webexa.online/docs

# 4. Vérifier les logs
tail -f /var/log/nginx/webexa.fr.error.log
tail -f /var/log/nginx/webexa.online.error.log
tail -f /var/log/webexa-api.log
```

## 🔧 Étape 8: Configuration DNS

Configurer vos domaines chez votre registraire:
```
webexa.fr     A    your-vps-ip
webexa.online A    your-vps-ip
```

## 📝 Notes Importantes

1. **Base de données**: Mettre à jour les variables d'environnement dans `.env`
2. **API Keys**: Configurer les clés API (OpenAI, Anthropic, etc.)
3. **CORS**: Adapter la configuration CORS dans FastAPI selon vos besoins
4. **Backups**: Mettre en place une stratégie de sauvegarde
5. **Monitoring**: Installer un outil comme Prometheus/Grafana

## 🆘 Troubleshooting

### PHP ne fonctionne pas
```bash
# Vérifier PHP-FPM
systemctl status php8.1-fpm  # Adapter la version
systemctl restart php8.1-fpm
```

### API Python ne répond pas
```bash
systemctl status webexa-api.service
systemctl restart webexa-api.service
tail -f /var/log/webexa-api.error.log
```

### Permissions
```bash
chown -R www-data:www-data /var/www/webexa
chown -R www-data:www-data /var/www/webexa-ai
chmod -R 755 /var/www/webexa
chmod -R 755 /var/www/webexa-ai
```
