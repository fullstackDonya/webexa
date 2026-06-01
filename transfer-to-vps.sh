#!/bin/bash
# 🚀 TRANSFERT RAPIDE DES FICHIERS WEBEXA VERS VPS

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║           🚀 TRANSFERT WEBEXA VERS VPS                     ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Demander l'IP du VPS
read -p "🔗 Adresse IP du VPS (ex: 87.106.3.49): " VPS_IP
if [ -z "$VPS_IP" ]; then
    echo "❌ IP requise!"
    exit 1
fi

# Vérifier la connexion SSH
echo "🔌 Vérification de la connexion SSH..."
if ! ssh -o ConnectTimeout=5 root@$VPS_IP "echo ✅ Connecté" 2>/dev/null; then
    echo "❌ Impossible de se connecter à $VPS_IP"
    echo "   Vérifiez:"
    echo "   - L'IP est correcte"
    echo "   - Vous avez accès SSH"
    echo "   - VPS est allumé"
    exit 1
fi

echo "✅ Connexion SSH OK"
echo ""

# Naviguer vers le répertoire
cd /Applications/MAMP/htdocs/webexa

echo "📦 ÉTAPE 1: Transférer le code PHP/CRM"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Source: $(pwd)"
echo "Destination: root@$VPS_IP:/var/www/webexa/"
echo ""
echo "Exclusions:"
echo "  - vendor/ (reinstallé via composer)"
echo "  - venv/ (recreé sur VPS)"
echo "  - .git/ (historique non nécessaire)"
echo "  - __pycache__/ (fichiers générés)"
echo "  - *.log (fichiers journaux)"
echo "  - .env (configuration spécifique)"
echo ""

read -p "Continuer? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Transfert annulé"
    exit 1
fi

rsync -avz --delete \
  --exclude='vendor' \
  --exclude='venv' \
  --exclude='.git' \
  --exclude='__pycache__' \
  --exclude='*.log' \
  --exclude='.env' \
  --exclude='node_modules' \
  --exclude='.DS_Store' \
  --exclude='.idea' \
  --exclude='.vscode' \
  . root@$VPS_IP:/var/www/webexa/

echo ""
echo "✅ Code PHP transféré!"
echo ""

# Créer les répertoires nécessaires sur VPS
echo "📂 ÉTAPE 2: Créer les répertoires de travail"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

ssh root@$VPS_IP << 'SSH_COMMANDS'
mkdir -p /var/www/webexa-ai
mkdir -p /var/log/webexa
chmod 755 /var/log/webexa
echo "✅ Répertoires créés"
SSH_COMMANDS

echo ""

# Afficher les prochaines étapes
echo "╔════════════════════════════════════════════════════════════╗"
echo "║              ✅ TRANSFERT COMPLÉTÉ!                        ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 PROCHAINES ÉTAPES:"
echo ""
echo "1️⃣  Installer les dépendances PHP:"
echo "    ssh root@$VPS_IP"
echo "    cd /var/www/webexa"
echo "    composer install --optimize-autoloader --no-dev"
echo ""
echo "2️⃣  Créer .env sur le VPS:"
echo "    cp /var/www/webexa/.env.example /var/www/webexa/.env"
echo "    nano /var/www/webexa/.env"
echo ""
echo "    IMPORTANT: Mettre à jour:"
echo "    - DB_HOST=localhost"
echo "    - DB_USER=webexa_user"
echo "    - DB_PASS=YourPassword"
echo "    - AI_API_BASE_URL=https://webexa.online"
echo "    - AI_API_KEY=bDVQoVSdFU0UN7Z1xLlWDH7eRV6Yor2dOI-fgUj3Cps"
echo ""
echo "3️⃣  Configurer la base de données:"
echo "    ssh root@$VPS_IP"
echo "    mysql -u root -p"
echo ""
echo "    CREATE DATABASE webexa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "    CREATE USER 'webexa_user'@'localhost' IDENTIFIED BY 'password';"
echo "    GRANT ALL PRIVILEGES ON webexa.* TO 'webexa_user'@'localhost';"
echo "    FLUSH PRIVILEGES;"
echo "    USE webexa;"
echo "    SOURCE /var/www/webexa/crm/crm_database.sql;"
echo "    EXIT;"
echo ""
echo "4️⃣  Transférer les agents IA:"
echo "    rsync -avz --exclude='venv' agents-ia/ root@$VPS_IP:/var/www/webexa-ai/"
echo ""
echo "5️⃣  Configurer l'environnement Python:"
echo "    ssh root@$VPS_IP"
echo "    cd /var/www/webexa-ai"
echo "    python3 -m venv venv"
echo "    source venv/bin/activate"
echo "    pip install -r requirements.txt"
echo "    cp .env.example .env"
echo "    # Éditer .env avec les bonnes valeurs"
echo ""
echo "6️⃣  Démarrer les services:"
echo "    systemctl restart php-fpm nginx"
echo "    systemctl enable webexa-api"
echo "    systemctl start webexa-api"
echo ""
echo "7️⃣  Tester la connexion:"
echo "    curl https://webexa.fr/crm/api/test-ai-connection.php"
echo ""
echo "📞 Pour plus d'aide:"
echo "   - Voir: CONFIGURATION_DEUX_DOMAINES.md"
echo "   - Voir: DEPLOYMENT_GUIDE.md"
echo ""
