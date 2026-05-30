#!/bin/bash
# VPS Setup Script for Webexa
# Run this on your VPS as root
# Usage: bash setup-vps.sh

set -e

echo "🚀 ===== WEBEXA VPS SETUP ====="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables
WEBROOT="/var/www"
WEBEXA_DIR="$WEBROOT/webexa"
WEBEXA_AI_DIR="$WEBROOT/webexa-ai"

# ===== STEP 1: Update system =====
echo -e "${YELLOW}📦 Step 1: Updating system...${NC}"
apt-get update
apt-get upgrade -y

# ===== STEP 2: Install dependencies =====
echo -e "${YELLOW}📦 Step 2: Installing dependencies...${NC}"

# PHP dependencies
apt-get install -y \
    php-fpm \
    php-mysql \
    php-mbstring \
    php-xml \
    php-gd \
    php-curl \
    php-json \
    php-opcache \
    composer

# Python dependencies
apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    python3-dev

# Nginx
apt-get install -y nginx

# Certbot for HTTPS
apt-get install -y certbot python3-certbot-nginx

# Other tools
apt-get install -y curl wget git vim nano

# ===== STEP 3: Create directories =====
echo -e "${YELLOW}📁 Step 3: Creating directories...${NC}"

mkdir -p "$WEBEXA_DIR"
mkdir -p "$WEBEXA_AI_DIR"
mkdir -p /var/log/webexa

chmod 755 "$WEBEXA_DIR"
chmod 755 "$WEBEXA_AI_DIR"
chown -R www-data:www-data "$WEBEXA_DIR"
chown -R www-data:www-data "$WEBEXA_AI_DIR"
chown -R www-data:www-data /var/log/webexa

# ===== STEP 4: Configure Nginx =====
echo -e "${YELLOW}🌐 Step 4: Configuring Nginx...${NC}"

# Remove default config
rm -f /etc/nginx/sites-enabled/default

# Create webexa.fr config
cat > /etc/nginx/sites-available/webexa.fr << 'NGINX_PHP'
server {
    listen 80;
    listen [::]:80;
    server_name webexa.fr www.webexa.fr;

    root /var/www/webexa;
    index index.php index.html;

    access_log /var/log/nginx/webexa.fr.access.log;
    error_log /var/log/nginx/webexa.fr.error.log;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ ^/(composer\.|\.env|vendor/) {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300s;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
NGINX_PHP

# Create webexa.online config
cat > /etc/nginx/sites-available/webexa.online << 'NGINX_PYTHON'
server {
    listen 80;
    listen [::]:80;
    server_name webexa.online www.webexa.online;

    access_log /var/log/nginx/webexa.online.access.log;
    error_log /var/log/nginx/webexa.online.error.log;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
        
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }

    location ~ /\.(env|git) {
        deny all;
    }
}
NGINX_PYTHON

# Enable sites
ln -sf /etc/nginx/sites-available/webexa.fr /etc/nginx/sites-enabled/
ln -sf /etc/nginx/sites-available/webexa.online /etc/nginx/sites-enabled/

# Test and reload Nginx
nginx -t
systemctl restart nginx

# ===== STEP 5: Configure PHP =====
echo -e "${YELLOW}🐘 Step 5: Configuring PHP-FPM...${NC}"
systemctl enable php-fpm
systemctl restart php-fpm

# ===== STEP 6: Create Python virtualenv =====
echo -e "${YELLOW}🐍 Step 6: Setting up Python environment...${NC}"

cd "$WEBEXA_AI_DIR"
python3 -m venv venv
source venv/bin/activate

pip install --upgrade pip setuptools wheel
# Note: requirements.txt will be copied from local machine

# ===== STEP 7: Create API service =====
echo -e "${YELLOW}⚙️  Step 7: Creating systemd service...${NC}"

cat > /etc/systemd/system/webexa-api.service << 'SYSTEMD'
[Unit]
Description=Webexa AI API Service
After=network.target
Wants=webexa-api.service

[Service]
Type=notify
User=www-data
Group=www-data
WorkingDirectory=/var/www/webexa-ai
Environment="PATH=/var/www/webexa-ai/venv/bin"
Environment="PYTHONUNBUFFERED=1"

ExecStart=/var/www/webexa-ai/venv/bin/gunicorn \
    --workers 4 \
    --worker-class uvicorn.workers.UvicornWorker \
    --bind 127.0.0.1:8000 \
    --timeout 300 \
    agents-ia.main:app

Restart=always
RestartSec=10

StandardOutput=append:/var/log/webexa/api.log
StandardError=append:/var/log/webexa/api.error.log

[Install]
WantedBy=multi-user.target
SYSTEMD

systemctl daemon-reload

# ===== STEP 8: Setup SSL =====
echo -e "${YELLOW}🔒 Step 8: Setting up SSL (Let's Encrypt)...${NC}"
echo -e "${YELLOW}Enter your domain (webexa.fr) and email to generate SSL certificate.${NC}"
echo -e "${RED}Note: DNS must be configured first!${NC}"

read -p "Continue with SSL setup? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    certbot --nginx -d webexa.fr -d www.webexa.fr || echo "SSL setup skipped"
    certbot --nginx -d webexa.online -d www.webexa.online || echo "SSL setup skipped"
    systemctl enable certbot.timer
fi

# ===== STEP 9: Verify installation =====
echo -e "${YELLOW}✅ Step 9: Verifying installation...${NC}"

echo -e "${GREEN}Nginx status:${NC}"
systemctl status nginx --no-pager | grep "Active"

echo -e "${GREEN}PHP-FPM status:${NC}"
systemctl status php-fpm --no-pager | grep "Active"

# ===== Summary =====
echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}✅ WEBEXA VPS SETUP COMPLETED${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo "📌 Next steps:"
echo ""
echo "1️⃣  Copy your project files:"
echo "   - PHP files → /var/www/webexa/"
echo "   - Python files → /var/www/webexa-ai/agents-ia/"
echo ""
echo "2️⃣  Install PHP dependencies:"
echo "   cd /var/www/webexa && composer install"
echo ""
echo "3️⃣  Install Python dependencies:"
echo "   cd /var/www/webexa-ai"
echo "   source venv/bin/activate"
echo "   pip install -r agents-ia/requirements.txt"
echo "   pip install gunicorn"
echo ""
echo "4️⃣  Configure environment files:"
echo "   nano /var/www/webexa/.env"
echo "   nano /var/www/webexa-ai/agents-ia/.env"
echo ""
echo "5️⃣  Start the API service:"
echo "   systemctl start webexa-api"
echo "   systemctl status webexa-api"
echo ""
echo "6️⃣  Configure DNS records:"
echo "   webexa.fr → your-vps-ip"
echo "   webexa.online → your-vps-ip"
echo ""
echo "📊 Monitor logs:"
echo "   tail -f /var/log/nginx/webexa.fr.error.log"
echo "   tail -f /var/log/nginx/webexa.online.error.log"
echo "   tail -f /var/log/webexa/api.error.log"
echo ""
