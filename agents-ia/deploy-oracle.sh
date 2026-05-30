#!/bin/bash

# ============================================
# Déploiement Agents IA sur Oracle Cloud
# Connexion à MySQL Hostinger
# ============================================

set -e

echo "============================================"
echo "🚀 DÉPLOIEMENT AGENTS IA - ORACLE CLOUD"
echo "Architecture: Oracle (IA) + Hostinger (BDD)"
echo "============================================"
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

# Vérifier qu'on est bien sur Oracle Cloud
read -p "Êtes-vous connecté au serveur ORACLE CLOUD ? (oui/non) : " confirm
if [ "$confirm" != "oui" ]; then
    error "Connectez-vous d'abord au serveur Oracle Cloud via SSH"
fi

echo ""
info "Début du déploiement..."
echo ""

# 1. Vérification Python
info "1️⃣  Vérification de Python..."
if ! command -v python3.10 &> /dev/null; then
    warning "Python 3.10 non trouvé, tentative d'installation..."
    if command -v yum &> /dev/null; then
        sudo yum install -y python3.10 python3.10-pip python3.10-venv
    elif command -v apt &> /dev/null; then
        sudo apt update && sudo apt install -y python3.10 python3.10-venv python3-pip
    else
        error "Gestionnaire de paquets non reconnu"
    fi
fi
success "Python $(python3.10 --version) installé"

# 2. Création de l'arborescence
info "2️⃣  Création de l'arborescence..."
sudo mkdir -p /var/www/agents-ia
sudo mkdir -p /var/lib/crm-ai/chromadb
sudo mkdir -p /var/log/crm-ai

# Déterminer l'utilisateur
if id "opc" &>/dev/null; then
    USER_GROUP="opc:opc"
elif id "ubuntu" &>/dev/null; then
    USER_GROUP="ubuntu:ubuntu"
else
    USER_GROUP="$USER:$USER"
fi

sudo chown -R $USER_GROUP /var/www/agents-ia
sudo chown -R $USER_GROUP /var/lib/crm-ai
sudo chown -R $USER_GROUP /var/log/crm-ai
success "Arborescence créée (propriétaire: $USER_GROUP)"

# 3. Copie des fichiers
info "3️⃣  Vérification des fichiers..."
if [ ! -f "main.py" ]; then
    warning "Les fichiers de l'application ne sont pas présents dans $(pwd)"
    echo ""
    echo "Transférez d'abord le dossier 'ia' depuis votre machine locale:"
    echo "  scp -r /chemin/vers/crm/ia/ $USER@$(hostname -I | awk '{print $1}'):/var/www/agents-ia/"
    echo ""
    read -p "Fichiers transférés vers /var/www/agents-ia ? (oui/non) : " files_ready
    if [ "$files_ready" != "oui" ]; then
        error "Transférez les fichiers d'abord"
    fi
    cd /var/www/agents-ia
fi
success "Fichiers présents"

# 4. Configuration .env
info "4️⃣  Configuration du fichier .env..."
if [ ! -f ".env" ]; then
    if [ -f ".env.production" ]; then
        cp .env.production .env
        warning "Fichier .env créé depuis .env.production"
        echo ""
        echo "📝 IMPORTANT: Éditez .env avec vos vraies valeurs:"
        echo "  - DB_HOST (MySQL Hostinger)"
        echo "  - DB_USER et DB_PASSWORD"
        echo "  - API_SECRET_KEY (même que dans le CRM)"
        echo "  - OPENAI_API_KEY"
        echo ""
        read -p "Voulez-vous éditer .env maintenant ? (oui/non) : " edit_env
        if [ "$edit_env" == "oui" ]; then
            ${EDITOR:-nano} .env
        fi
    else
        error "Fichier .env.production non trouvé"
    fi
fi

# Vérifier que la config a été changée
if grep -q "VOTRE_MOT_DE_PASSE_HOSTINGER" .env 2>/dev/null; then
    warning "Le fichier .env contient encore des valeurs par défaut !"
    read -p "Continuez quand même ? (oui/non) : " continue
    if [ "$continue" != "oui" ]; then
        error "Configurez .env correctement d'abord"
    fi
fi
success "Fichier .env configuré"

# 5. Test connexion MySQL Hostinger
info "5️⃣  Test de connexion à MySQL Hostinger..."
echo "Vérification de la connexion à la base de données..."

# Installer mysql client si nécessaire
if ! command -v mysql &> /dev/null; then
    info "Installation du client MySQL..."
    if command -v yum &> /dev/null; then
        sudo yum install -y mysql
    elif command -v apt &> /dev/null; then
        sudo apt install -y mysql-client
    fi
fi

# Test Python pour éviter de demander le password
python3.10 << 'ENDPYTHON'
import sys
try:
    from dotenv import load_dotenv
    load_dotenv()
    from database import db
    result = db.execute("SELECT 1 as test")
    print("✅ Connexion MySQL Hostinger réussie")
    sys.exit(0)
except Exception as e:
    print(f"❌ Erreur connexion MySQL: {e}")
    print("\nVérifiez:")
    print("1. Que l'IP Oracle est autorisée dans cPanel Hostinger (MySQL distant)")
    print("2. Les credentials DB_HOST, DB_USER, DB_PASSWORD dans .env")
    print("3. Que le firewall MySQL Hostinger autorise la connexion")
    sys.exit(1)
ENDPYTHON

if [ $? -ne 0 ]; then
    error "Impossible de se connecter à MySQL Hostinger"
fi
success "Connexion MySQL Hostinger validée"

# 6. Environnement virtuel Python
info "6️⃣  Création de l'environnement virtuel..."
if [ ! -d "venv" ]; then
    python3.10 -m venv venv
fi
source venv/bin/activate
success "Environnement virtuel activé"

# 7. Installation des dépendances
info "7️⃣  Installation des dépendances Python..."
pip install --upgrade pip -q
pip install -r requirements.txt -q
success "Dépendances installées"

# 8. Service systemd
info "8️⃣  Configuration du service systemd..."

sudo tee /etc/systemd/system/agents-ia.service > /dev/null << EOF
[Unit]
Description=Agents IA CRM - FastAPI
After=network.target

[Service]
Type=simple
User=${USER_GROUP%:*}
Group=${USER_GROUP#*:}
WorkingDirectory=/var/www/agents-ia
Environment="PATH=/var/www/agents-ia/venv/bin"
ExecStart=/var/www/agents-ia/venv/bin/python -m uvicorn main:app --host 0.0.0.0 --port 8000 --workers 2
Restart=always
RestartSec=10
StandardOutput=append:/var/log/crm-ai/api.log
StandardError=append:/var/log/crm-ai/api-error.log

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable agents-ia
success "Service systemd configuré"

# 9. Firewall
info "9️⃣  Configuration du firewall..."
if command -v firewall-cmd &> /dev/null; then
    sudo firewall-cmd --permanent --add-port=8000/tcp 2>/dev/null || true
    sudo firewall-cmd --reload 2>/dev/null || true
    success "Firewalld configuré"
elif command -v ufw &> /dev/null; then
    sudo ufw allow 8000/tcp 2>/dev/null || true
    success "UFW configuré"
else
    warning "Firewall non détecté - configurez manuellement le port 8000"
fi

# 10. Démarrage du service
info "🔟 Démarrage du service..."
sudo systemctl start agents-ia
sleep 3

if sudo systemctl is-active --quiet agents-ia; then
    success "Service démarré avec succès"
else
    warning "Le service ne semble pas actif"
    echo "Vérifiez les logs: sudo journalctl -u agents-ia -n 50"
fi

# 11. Test de l'API
info "1️⃣1️⃣  Test de l'API..."
sleep 2

if curl -s http://localhost:8000/health > /dev/null 2>&1; then
    success "API accessible localement"
    response=$(curl -s http://localhost:8000/health)
    echo "$response" | python3 -m json.tool 2>/dev/null || echo "$response"
else
    warning "API non accessible - vérifiez les logs"
fi

# Résumé
echo ""
echo "============================================"
echo "✅ DÉPLOIEMENT TERMINÉ"
echo "============================================"
echo ""
echo "📊 Informations :"
echo "  - Service      : agents-ia"
echo "  - Port         : 8000"
echo "  - Dossier      : /var/www/agents-ia"
echo "  - Logs         : /var/log/crm-ai/"
echo "  - Base données : MySQL Hostinger"
echo ""
echo "🔧 Commandes utiles :"
echo "  - Statut       : sudo systemctl status agents-ia"
echo "  - Redémarrer   : sudo systemctl restart agents-ia"
echo "  - Logs         : sudo journalctl -u agents-ia -f"
echo "  - Logs app     : tail -f /var/log/crm-ai/api.log"
echo ""
echo "🧪 Tests :"
IP=$(hostname -I | awk '{print $1}')
echo "  - Local        : curl http://localhost:8000/health"
echo "  - Distant      : curl http://$IP:8000/health"
echo ""
echo "⚠️  IMPORTANT - Prochaines étapes :"
echo ""
echo "1️⃣  Vérifiez que l'API est accessible depuis l'extérieur:"
echo "   curl http://$IP:8000/health"
echo ""
echo "2️⃣  Sur Hostinger, configurez le CRM pour pointer vers Oracle:"
echo "   Éditez AIAgentsClient.php ou .env:"
echo "   AI_API_URL=http://$IP:8000"
echo "   AI_API_KEY=$(grep API_SECRET_KEY .env | cut -d'=' -f2)"
echo ""
echo "3️⃣  Dans Oracle Cloud Console:"
echo "   - Virtual Cloud Networks → Security Lists"
echo "   - Ajoutez Ingress Rule : Port 8000, Source 0.0.0.0/0"
echo ""
echo "4️⃣  Testez depuis le dashboard CRM:"
echo "   https://votre-crm-hostinger.com/ai-dashboard.php"
echo ""
success "Déploiement réussi ! 🎉"
