#!/bin/bash
# Script de déploiement Webexa sur VPS
# Usage: ./deploy.sh <vps-ip> <user>

set -e

VPS_IP=${1:-"your-vps-ip"}
VPS_USER=${2:-"root"}
WEBROOT="/var/www"

echo "🚀 Déploiement Webexa sur $VPS_IP..."

# 1. Créer les répertoires
echo "📁 Création des répertoires..."
ssh $VPS_USER@$VPS_IP "
    mkdir -p $WEBROOT/webexa
    mkdir -p $WEBROOT/webexa-ai
    chown -R www-data:www-data $WEBROOT/webexa
    chown -R www-data:www-data $WEBROOT/webexa-ai
"

# 2. Copier les fichiers PHP
echo "📦 Copie de la partie PHP..."
rsync -avz --exclude='agents-ia' --exclude='vendor' --exclude='.env' \
    . $VPS_USER@$VPS_IP:$WEBROOT/webexa/

# 3. Copier les fichiers Python
echo "🐍 Copie de la partie Python..."
rsync -avz agents-ia/ $VPS_USER@$VPS_IP:$WEBROOT/webexa-ai/agents-ia/

# 4. Composer install
echo "📚 Installation des dépendances PHP..."
ssh $VPS_USER@$VPS_IP "
    cd $WEBROOT/webexa
    composer install --optimize-autoloader --no-dev
    chmod -R 755 .
"

# 5. Python virtualenv
echo "🐍 Création de l'environnement Python..."
ssh $VPS_USER@$VPS_IP "
    cd $WEBROOT/webexa-ai
    python3 -m venv venv
    source venv/bin/activate
    pip install --upgrade pip
    pip install -r agents-ia/requirements.txt
    pip install gunicorn
"

echo "✅ Déploiement terminé!"
echo "⚠️  Prochaines étapes:"
echo "   1. SSH sur le VPS: ssh $VPS_USER@$VPS_IP"
echo "   2. Configurer .env pour PHP: nano $WEBROOT/webexa/.env"
echo "   3. Configurer .env pour Python: nano $WEBROOT/webexa-ai/agents-ia/.env"
echo "   4. Configurer Nginx (voir DEPLOYMENT_GUIDE.md)"
echo "   5. Configurer DNS pour webexa.fr et webexa.online"
