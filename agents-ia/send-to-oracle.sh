#!/bin/bash

# ============================================
# Script d'envoi des fichiers vers Oracle Cloud
# À exécuter depuis votre MAC LOCAL
# ============================================

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║  TRANSFERT FICHIERS VERS ORACLE CLOUD                      ║"
echo "╚════════════════════════════════════════════════════════════╝"
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

# Configuration
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
CRM_DIR="$(dirname "$SCRIPT_DIR")"

echo "📁 Dossier actuel: $SCRIPT_DIR"
echo ""

# Demander les infos Oracle Cloud
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📝 INFORMATIONS ORACLE CLOUD"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

read -p "IP Oracle Cloud (ex: 123.45.67.89) : " ORACLE_IP
read -p "Utilisateur SSH (opc/ubuntu/oracle) [opc]: " ORACLE_USER
ORACLE_USER=${ORACLE_USER:-opc}

read -p "Chemin vers la clé SSH [~/.ssh/id_rsa]: " SSH_KEY
SSH_KEY=${SSH_KEY:-~/.ssh/id_rsa}

# Expand tilde
SSH_KEY="${SSH_KEY/#\~/$HOME}"

echo ""
info "Configuration:"
echo "  IP: $ORACLE_IP"
echo "  User: $ORACLE_USER"
echo "  SSH Key: $SSH_KEY"
echo ""

# Vérifier la clé SSH
if [ ! -f "$SSH_KEY" ]; then
    error "Clé SSH introuvable: $SSH_KEY"
fi

# Test de connexion SSH
info "Test de connexion SSH..."
if ssh -i "$SSH_KEY" -o ConnectTimeout=10 -o StrictHostKeyChecking=no "$ORACLE_USER@$ORACLE_IP" "echo 'OK'" > /dev/null 2>&1; then
    success "Connexion SSH réussie"
else
    error "Impossible de se connecter à $ORACLE_USER@$ORACLE_IP"
fi

echo ""

# Créer une archive des fichiers
info "Création de l'archive..."
cd "$CRM_DIR"

# Créer un tar.gz du dossier ia/
TAR_FILE="/tmp/agents-ia-deploy-$(date +%Y%m%d-%H%M%S).tar.gz"

tar -czf "$TAR_FILE" \
    --exclude='ia/__pycache__' \
    --exclude='ia/*.pyc' \
    --exclude='ia/venv' \
    --exclude='ia/.env' \
    --exclude='ia/.git' \
    --exclude='ia/chromadb' \
    ia/

if [ ! -f "$TAR_FILE" ]; then
    error "Impossible de créer l'archive"
fi

TAR_SIZE=$(du -h "$TAR_FILE" | cut -f1)
success "Archive créée: $TAR_FILE ($TAR_SIZE)"

echo ""

# Transfert vers Oracle Cloud
info "Transfert vers Oracle Cloud..."
echo "  Source: $TAR_FILE"
echo "  Destination: $ORACLE_USER@$ORACLE_IP:~/"
echo ""

scp -i "$SSH_KEY" "$TAR_FILE" "$ORACLE_USER@$ORACLE_IP:~/agents-ia.tar.gz"

if [ $? -eq 0 ]; then
    success "Transfert réussi"
else
    error "Échec du transfert"
fi

echo ""

# Nettoyage local
rm -f "$TAR_FILE"
info "Archive locale supprimée"

echo ""

# Instructions pour la suite
echo "╔════════════════════════════════════════════════════════════╗"
echo "║  PROCHAINES ÉTAPES                                         ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "Les fichiers ont été transférés sur Oracle Cloud."
echo ""
echo "Maintenant, CONNECTEZ-VOUS au serveur Oracle et déployez :"
echo ""
echo "  ${GREEN}ssh -i $SSH_KEY $ORACLE_USER@$ORACLE_IP${NC}"
echo ""
echo "Puis sur le serveur Oracle Cloud, exécutez :"
echo ""
echo "  ${YELLOW}# 1. Créer le dossier de destination${NC}"
echo "  sudo mkdir -p /var/www/agents-ia"
echo "  sudo chown $ORACLE_USER:$ORACLE_USER /var/www/agents-ia"
echo ""
echo "  ${YELLOW}# 2. Extraire l'archive${NC}"
echo "  cd /var/www/agents-ia"
echo "  sudo tar -xzf ~/agents-ia.tar.gz --strip-components=1"
echo "  sudo chown -R $ORACLE_USER:$ORACLE_USER /var/www/agents-ia"
echo ""
echo "  ${YELLOW}# 3. Configurer .env${NC}"
echo "  cp .env.production .env"
echo "  nano .env  # Modifiez avec vos vraies valeurs"
echo ""
echo "  ${YELLOW}# 4. (OPTIONNEL) Tester la connexion MySQL Hostinger${NC}"
echo "  python3 test-mysql-hostinger.py"
echo ""
echo "  ${YELLOW}# 5. Lancer le déploiement${NC}"
echo "  ./deploy-oracle.sh"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Proposer de se connecter directement
read -p "Voulez-vous vous connecter maintenant à Oracle Cloud ? (o/N) : " CONNECT
if [ "$CONNECT" = "o" ] || [ "$CONNECT" = "O" ]; then
    echo ""
    success "Connexion à Oracle Cloud..."
    ssh -i "$SSH_KEY" "$ORACLE_USER@$ORACLE_IP"
else
    echo ""
    info "Pour vous connecter plus tard :"
    echo "  ssh -i $SSH_KEY $ORACLE_USER@$ORACLE_IP"
    echo ""
fi

echo ""
success "Transfert terminé ! 🎉"
