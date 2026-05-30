#!/bin/bash
# Quick deployment script for Webexa
# This script deploys Webexa to your VPS

set -e

echo "🚀 ===== WEBEXA DEPLOYMENT SCRIPT ====="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# ===== Configuration =====
echo -e "${BLUE}Enter deployment configuration:${NC}"
echo ""

read -p "VPS IP address: " VPS_IP
read -p "VPS username (default: root): " VPS_USER
VPS_USER=${VPS_USER:-root}

read -p "PHP domain (default: webexa.fr): " PHP_DOMAIN
PHP_DOMAIN=${PHP_DOMAIN:-webexa.fr}

read -p "Python domain (default: webexa.online): " PYTHON_DOMAIN
PYTHON_DOMAIN=${PYTHON_DOMAIN:-webexa.online}

WEBROOT="/var/www"
WEBEXA_DIR="$WEBROOT/webexa"
WEBEXA_AI_DIR="$WEBROOT/webexa-ai"

echo ""
echo -e "${YELLOW}Summary:${NC}"
echo "  VPS: $VPS_IP"
echo "  User: $VPS_USER"
echo "  PHP Domain: $PHP_DOMAIN"
echo "  Python Domain: $PYTHON_DOMAIN"
echo ""

# ===== Confirmation =====
read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled."
    exit 1
fi

# ===== Step 1: Setup VPS =====
echo ""
echo -e "${YELLOW}📦 Step 1: Setting up VPS environment...${NC}"

# Check if user can SSH
if ! ssh -o ConnectTimeout=5 $VPS_USER@$VPS_IP "echo 'SSH connection OK'" > /dev/null 2>&1; then
    echo -e "${RED}❌ Cannot connect to VPS. Check IP and SSH access.${NC}"
    exit 1
fi
echo -e "${GREEN}✅ SSH connection successful${NC}"

# Copy setup script to VPS
echo "Copying setup script to VPS..."
scp setup-vps.sh $VPS_USER@$VPS_IP:/tmp/setup-vps.sh

# Run setup on VPS
echo "Running VPS setup (this may take a few minutes)..."
ssh $VPS_USER@$VPS_IP "bash /tmp/setup-vps.sh"

# ===== Step 2: Deploy PHP files =====
echo ""
echo -e "${YELLOW}📂 Step 2: Deploying PHP files...${NC}"

echo "Syncing PHP files to $WEBEXA_DIR..."
rsync -avz \
    --exclude='agents-ia' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='*.log' \
    . $VPS_USER@$VPS_IP:$WEBEXA_DIR/

# Install Composer dependencies on VPS
echo "Installing Composer dependencies..."
ssh $VPS_USER@$VPS_IP "cd $WEBEXA_DIR && composer install --optimize-autoloader --no-dev"

# Set permissions
echo "Setting permissions..."
ssh $VPS_USER@$VPS_IP "
    chown -R www-data:www-data $WEBEXA_DIR
    chmod -R 755 $WEBEXA_DIR
    chmod 644 $WEBEXA_DIR/*.php
    find $WEBEXA_DIR -type f -name '*.php' -exec chmod 644 {} \;
"

echo -e "${GREEN}✅ PHP deployment completed${NC}"

# ===== Step 3: Deploy Python files =====
echo ""
echo -e "${YELLOW}🐍 Step 3: Deploying Python AI API...${NC}"

echo "Syncing Python files to $WEBEXA_AI_DIR..."
rsync -avz \
    --exclude='venv' \
    --exclude='.env' \
    --exclude='__pycache__' \
    --exclude='*.pyc' \
    --exclude='.git' \
    agents-ia/ $VPS_USER@$VPS_IP:$WEBEXA_AI_DIR/agents-ia/

# Install Python dependencies on VPS
echo "Installing Python dependencies..."
ssh $VPS_USER@$VPS_IP "
    cd $WEBEXA_AI_DIR
    source venv/bin/activate
    pip install -r agents-ia/requirements.txt
    pip install gunicorn
"

echo -e "${GREEN}✅ Python deployment completed${NC}"

# ===== Step 4: Configuration =====
echo ""
echo -e "${YELLOW}⚙️  Step 4: Configuration instructions${NC}"
echo ""
echo "📝 Configure environment variables:"
echo ""
echo "  1. PHP configuration:"
echo "     ssh $VPS_USER@$VPS_IP"
echo "     nano $WEBEXA_DIR/.env"
echo ""
echo "  2. Python AI configuration:"
echo "     nano $WEBEXA_AI_DIR/agents-ia/.env"
echo ""

# ===== Step 5: Start services =====
echo ""
echo -e "${YELLOW}🚀 Step 5: Starting services...${NC}"

echo "Starting Python API service..."
ssh $VPS_USER@$VPS_IP "
    systemctl enable webexa-api.service
    systemctl start webexa-api.service
    sleep 2
    systemctl status webexa-api.service --no-pager
"

echo -e "${GREEN}✅ Services started${NC}"

# ===== Step 6: Verification =====
echo ""
echo -e "${YELLOW}✅ Step 6: Verification${NC}"

echo "Checking PHP website..."
if curl -s -I http://$PHP_DOMAIN 2>/dev/null | grep -q "HTTP"; then
    echo -e "${GREEN}✅ PHP website is accessible${NC}"
else
    echo -e "${YELLOW}⚠️  PHP website not yet accessible (DNS may need configuration)${NC}"
fi

echo ""
echo "Checking Python API..."
if curl -s http://127.0.0.1:8000/docs 2>/dev/null > /dev/null; then
    echo -e "${GREEN}✅ Python API is running locally${NC}"
else
    echo -e "${YELLOW}⚠️  Python API may still be starting...${NC}"
fi

# ===== Final Summary =====
echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}✅ DEPLOYMENT COMPLETED SUCCESSFULLY${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo "📌 Important URLs:"
echo "  - PHP App: http://$PHP_DOMAIN"
echo "  - Python API: http://$PYTHON_DOMAIN"
echo "  - API Docs: http://$PYTHON_DOMAIN/docs"
echo ""
echo "📊 Monitoring commands:"
echo "  - SSH: ssh $VPS_USER@$VPS_IP"
echo "  - View logs: tail -f /var/log/nginx/webexa.*.error.log"
echo "  - API logs: tail -f /var/log/webexa/api.error.log"
echo "  - Service status: systemctl status webexa-api"
echo ""
echo "🔒 Next step: Configure DNS"
echo "  - Point $PHP_DOMAIN to $VPS_IP"
echo "  - Point $PYTHON_DOMAIN to $VPS_IP"
echo ""
echo "🔐 Then enable HTTPS:"
echo "  ssh $VPS_USER@$VPS_IP"
echo "  certbot --nginx"
echo ""
