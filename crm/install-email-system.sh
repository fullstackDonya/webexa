#!/bin/bash

# Script d'installation du système Email pour CRM
# Ce script installe toutes les dépendances et configure l'environnement

echo "================================================"
echo "  Installation du Système Email - CRM"
echo "================================================"
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Répertoire du projet
PROJECT_DIR="/Applications/MAMP/htdocs/PP/webitech/WEB/crm"
cd "$PROJECT_DIR" || exit 1

echo -e "${YELLOW}📁 Répertoire du projet: $PROJECT_DIR${NC}"
echo ""

# 1. Vérifier PHP
echo "🔍 Vérification de PHP..."
if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ PHP n'est pas installé${NC}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✓ PHP $PHP_VERSION trouvé${NC}"

# 2. Vérifier les extensions PHP
echo ""
echo "🔍 Vérification des extensions PHP..."
REQUIRED_EXTENSIONS=("openssl" "pdo" "imap" "mbstring" "curl")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "$ext"; then
        echo -e "${GREEN}✓ Extension $ext${NC}"
    else
        echo -e "${RED}✗ Extension $ext manquante${NC}"
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    echo -e "${RED}Certaines extensions PHP sont manquantes. À installer:${NC}"
    for ext in "${MISSING_EXTENSIONS[@]}"; do
        echo "  - $ext"
    done
    echo ""
    echo "Pour MAMP, éditez:"
    echo "  /Applications/MAMP/bin/php/php8.x.x/conf/php.ini"
    echo "Et décommentez les extensions nécessaires"
    exit 1
fi

# 3. Vérifier Composer
echo ""
echo "🔍 Vérification de Composer..."
if ! command -v composer &> /dev/null; then
    echo -e "${YELLOW}⚠ Composer n'est pas installé globalement${NC}"
    echo "Installation de composer localement..."
    curl -sS https://getcomposer.org/installer | php
    COMPOSER_CMD="php composer.phar"
else
    echo -e "${GREEN}✓ Composer trouvé${NC}"
    COMPOSER_CMD="composer"
fi

# 4. Installer les dépendances
echo ""
echo "📦 Installation des dépendances..."

if [ ! -f "composer.json" ]; then
    echo "Création de composer.json..."
    cat > composer.json <<EOL
{
    "name": "webitech/crm",
    "description": "CRM avec gestion email complète",
    "require": {
        "phpmailer/phpmailer": "^6.8"
    },
    "autoload": {
        "psr-4": {
            "CRM\\\\": "includes/"
        }
    }
}
EOL
fi

$COMPOSER_CMD install --no-dev
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Dépendances installées${NC}"
else
    echo -e "${RED}✗ Erreur lors de l'installation des dépendances${NC}"
    exit 1
fi

# 5. Vérifier .env
echo ""
echo "🔍 Vérification du fichier .env..."
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠ Fichier .env non trouvé${NC}"
    echo "Création du fichier .env..."
    
    # Générer une clé de chiffrement
    CRYPTO_KEY=$(php -r "echo base64_encode(random_bytes(32));")
    
    cat > .env <<EOL
# Configuration Email CRM

# Clé de chiffrement pour les credentials (NE PAS MODIFIER)
MAIL_CRYPTO_KEY=$CRYPTO_KEY

# OAuth2 Gmail (optionnel)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8888/crm/oauth-callback.php

# OAuth2 Microsoft/Outlook (optionnel)
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_REDIRECT_URI=http://localhost:8888/crm/oauth-callback.php

# Configuration générale
TIMEZONE=Europe/Paris
EOL
    echo -e "${GREEN}✓ Fichier .env créé avec une clé de chiffrement${NC}"
    echo -e "${YELLOW}⚠ N'oubliez pas de remplir les credentials OAuth si nécessaire${NC}"
else
    echo -e "${GREEN}✓ Fichier .env existe${NC}"
    
    # Vérifier si MAIL_CRYPTO_KEY existe
    if ! grep -q "MAIL_CRYPTO_KEY" .env; then
        echo -e "${YELLOW}⚠ MAIL_CRYPTO_KEY manquante, ajout...${NC}"
        CRYPTO_KEY=$(php -r "echo base64_encode(random_bytes(32));")
        echo "" >> .env
        echo "# Clé de chiffrement pour les credentials" >> .env
        echo "MAIL_CRYPTO_KEY=$CRYPTO_KEY" >> .env
        echo -e "${GREEN}✓ Clé ajoutée${NC}"
    fi
fi

# 6. Vérifier la base de données
echo ""
echo "🔍 Vérification de la base de données..."
echo "Vous devez exécuter manuellement:"
echo -e "${YELLOW}mysql -u root webitech < database/email_tables.sql${NC}"
echo ""
read -p "Avez-vous déjà créé les tables email ? (o/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Oo]$ ]]; then
    echo -e "${GREEN}✓ Tables email OK${NC}"
else
    echo -e "${YELLOW}⚠ Exécutez la commande ci-dessus pour créer les tables${NC}"
fi

# 7. Créer le répertoire logs
echo ""
echo "📁 Création du répertoire logs..."
mkdir -p logs
chmod 755 logs
echo -e "${GREEN}✓ Répertoire logs créé${NC}"

# 8. Rendre le script cron exécutable
echo ""
echo "🔧 Configuration du script cron..."
chmod +x cron-email-sync.php
echo -e "${GREEN}✓ Script cron configuré${NC}"

# 9. Test du cron
echo ""
echo "🧪 Test du script de synchronisation..."
read -p "Voulez-vous tester le cron maintenant ? (o/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Oo]$ ]]; then
    php cron-email-sync.php
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Test du cron réussi${NC}"
    else
        echo -e "${YELLOW}⚠ Le test a échoué (normal si aucun compte configuré)${NC}"
    fi
fi

# 10. Instructions pour crontab
echo ""
echo "================================================"
echo "  ✅ Installation terminée !"
echo "================================================"
echo ""
echo "📝 Prochaines étapes:"
echo ""
echo "1. Vérifier le fichier .env et remplir les credentials OAuth si nécessaire"
echo ""
echo "2. Configurer le crontab pour synchronisation automatique:"
echo -e "${YELLOW}crontab -e${NC}"
echo "Puis ajoutez:"
echo -e "${GREEN}*/5 * * * * /usr/bin/php $PROJECT_DIR/cron-email-sync.php >> $PROJECT_DIR/logs/email-sync.log 2>&1${NC}"
echo ""
echo "3. Accéder au CRM:"
echo "   - Paramètres Email: http://localhost:8888/crm/email-settings.php"
echo "   - Boîte de réception: http://localhost:8888/crm/email-inbox.php"
echo "   - Composer email: http://localhost:8888/crm/email-compose.php"
echo ""
echo "4. Configurer votre premier compte email via l'interface web"
echo ""
echo "📖 Documentation complète: EMAIL_SYSTEM_README.md"
echo ""
echo -e "${GREEN}Bon travail ! 🚀${NC}"
