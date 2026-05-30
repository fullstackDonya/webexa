#!/bin/bash

# ============================================
# Script de déploiement automatique
# Agents IA CRM - Production Oracle Cloud
# ============================================

set -e  # Arrêter en cas d'erreur

echo "============================================"
echo "🚀 DÉPLOIEMENT AGENTS IA - PRODUCTION"
echo "============================================"
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction pour afficher les messages
info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
    exit 1
}

# Vérifier qu'on est bien sur le serveur de production
read -p "Êtes-vous sûr d'être sur le serveur de PRODUCTION ? (oui/non) : " confirm
if [ "$confirm" != "oui" ]; then
    error "Déploiement annulé"
fi

echo ""
info "Début du déploiement..."
echo ""

# 1. Vérification Python
info "1️⃣  Vérification de Python..."
if ! command -v python3.10 &> /dev/null; then
    error "Python 3.10+ non installé. Installez-le d'abord !"
fi
success "Python $(python3.10 --version) installé"

# 2. Création des dossiers
info "2️⃣  Création de l'arborescence..."
sudo mkdir -p /var/www/crm/ia
sudo mkdir -p /var/lib/crm-ai/chromadb
sudo mkdir -p /var/log/crm-ai
sudo mkdir -p /etc/crm-ai
sudo chown -R www-data:www-data /var/www/crm
sudo chown -R www-data:www-data /var/lib/crm-ai
sudo chown -R www-data:www-data /var/log/crm-ai
success "Arborescence créée"

# 3. Copie des fichiers (si pas déjà fait via Git/SCP)
info "3️⃣  Copie des fichiers..."
if [ -d "$(pwd)/venv" ]; then
    warning "Les fichiers semblent déjà présents"
else
    info "Copiez vos fichiers via Git ou SCP avant de continuer"
    read -p "Fichiers copiés ? (oui/non) : " files_ready
    if [ "$files_ready" != "oui" ]; then
        error "Copiez les fichiers d'abord"
    fi
fi

# 4. Environnement virtuel Python
info "4️⃣  Création de l'environnement virtuel..."
cd /var/www/crm/ia
python3.10 -m venv venv
source venv/bin/activate
success "Environnement virtuel créé"

# 5. Installation des dépendances
info "5️⃣  Installation des dépendances Python..."
pip install --upgrade pip
pip install -r requirements.txt
success "Dépendances installées"

# 6. Configuration .env
info "6️⃣  Configuration du fichier .env..."
if [ ! -f ".env" ]; then
    warning "Fichier .env non trouvé"
    read -p "Copier depuis .env.production ? (oui/non) : " copy_env
    if [ "$copy_env" == "oui" ]; then
        cp .env.production .env
        warning "Fichier .env créé - MODIFIEZ LES VALEURS AVANT DE CONTINUER !"
        nano .env
    else
        error "Créez le fichier .env d'abord"
    fi
fi

# Vérifier que la clé API a été changée
if grep -q "GENEREZ_UNE_CLE" .env; then
    error "La clé API n'a pas été changée ! Utilisez generate-api-key.py"
fi
success "Fichier .env configuré"

# 7. Test de connexion base de données
info "7️⃣  Test de connexion à la base de données..."
python3 << 'ENDPYTHON'
from database import db
try:
    result = db.execute("SELECT 1")
    print("✅ Connexion DB réussie")
except Exception as e:
    print(f"❌ Erreur DB: {e}")
    exit(1)
ENDPYTHON
success "Base de données accessible"

# 8. Configuration du service systemd
info "8️⃣  Configuration du service systemd..."
sudo tee /etc/systemd/system/crm-ai-agents.service > /dev/null << 'EOF'
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
EOF

sudo systemctl daemon-reload
sudo systemctl enable crm-ai-agents
success "Service systemd configuré"

# 9. Démarrage du service
info "9️⃣  Démarrage du service..."
sudo systemctl start crm-ai-agents
sleep 3

# Vérifier le statut
if sudo systemctl is-active --quiet crm-ai-agents; then
    success "Service démarré avec succès"
else
    error "Échec du démarrage du service. Vérifiez: sudo journalctl -u crm-ai-agents"
fi

# 10. Test de l'API
info "🔟 Test de l'API..."
sleep 2
response=$(curl -s http://localhost:8000/health)
if [ $? -eq 0 ]; then
    success "API accessible : $response"
else
    error "API non accessible"
fi

# 11. Configuration Nginx (optionnel)
echo ""
read -p "Configurer Nginx comme reverse proxy ? (oui/non) : " setup_nginx
if [ "$setup_nginx" == "oui" ]; then
    info "Configuration de Nginx..."
    
    read -p "Nom de domaine (ex: api.moncrm.com) : " domain
    
    sudo tee /etc/nginx/sites-available/crm-ai > /dev/null << EOF
upstream crm_ai_backend {
    server 127.0.0.1:8000;
}

server {
    listen 80;
    server_name $domain;
    
    location / {
        proxy_pass http://crm_ai_backend;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        
        # Authentication
        proxy_set_header X-API-Key \$http_x_api_key;
    }
}
EOF
    
    sudo ln -sf /etc/nginx/sites-available/crm-ai /etc/nginx/sites-enabled/
    sudo nginx -t && sudo systemctl reload nginx
    success "Nginx configuré"
fi

# Résumé final
echo ""
echo "============================================"
echo "✅ DÉPLOIEMENT TERMINÉ"
echo "============================================"
echo ""
echo "📊 Informations :"
echo "  - Service : crm-ai-agents"
echo "  - Port : 8000"
echo "  - Logs : /var/log/crm-ai/"
echo ""
echo "🔧 Commandes utiles :"
echo "  - Statut      : sudo systemctl status crm-ai-agents"
echo "  - Redémarrer  : sudo systemctl restart crm-ai-agents"
echo "  - Logs        : sudo journalctl -u crm-ai-agents -f"
echo "  - Stop        : sudo systemctl stop crm-ai-agents"
echo ""
echo "🧪 Tests :"
echo "  - Health      : curl http://localhost:8000/health"
echo "  - API Docs    : http://localhost:8000/docs"
echo ""

if [ "$setup_nginx" == "oui" ]; then
    echo "🌐 Accès public :"
    echo "  - URL         : https://$domain"
    echo ""
    info "N'oubliez pas de configurer SSL avec certbot :"
    echo "  sudo certbot --nginx -d $domain"
    echo ""
fi

echo "⚠️  IMPORTANT - Prochaines étapes :"
echo "  1. Testez l'API avec votre client PHP"
echo "  2. Configurez le monitoring et les alertes"
echo "  3. Configurez les backups automatiques"
echo "  4. Testez la récupération après crash"
echo ""
success "Déploiement réussi ! 🎉"
