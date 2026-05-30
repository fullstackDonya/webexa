#!/bin/bash

###############################################################################
# CRM Email Integration - Installation Script
# 
# Ce script installe et configure l'intégration email OAuth2
###############################################################################

set -e  # Exit on error

echo "========================================="
echo "  CRM Email Integration - Installation"
echo "========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Determine project root
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_ROOT"

# 1. Check PHP
echo -e "${YELLOW}[1/8]${NC} Vérification PHP..."
if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ PHP n'est pas installé${NC}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✓ PHP ${PHP_VERSION} détecté${NC}"

# Check required extensions
REQUIRED_EXTS=("openssl" "pdo" "pdo_mysql" "curl" "redis" "imap" "json")
MISSING_EXTS=()

for ext in "${REQUIRED_EXTS[@]}"; do
    if ! php -m | grep -q "^${ext}$"; then
        MISSING_EXTS+=("$ext")
    fi
done

if [ ${#MISSING_EXTS[@]} -gt 0 ]; then
    echo -e "${RED}✗ Extensions PHP manquantes: ${MISSING_EXTS[*]}${NC}"
    echo "  Installez-les et relancez ce script"
    exit 1
fi

echo -e "${GREEN}✓ Toutes les extensions PHP requises sont présentes${NC}"

# 2. Check MySQL
echo ""
echo -e "${YELLOW}[2/8]${NC} Vérification MySQL..."
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}✗ MySQL n'est pas installé${NC}"
    exit 1
fi

echo -e "${GREEN}✓ MySQL détecté${NC}"

# 3. Check Redis
echo ""
echo -e "${YELLOW}[3/8]${NC} Vérification Redis..."
if ! command -v redis-cli &> /dev/null; then
    echo -e "${RED}✗ Redis n'est pas installé${NC}"
    echo "  Installez Redis:"
    echo "    macOS: brew install redis"
    echo "    Linux: sudo apt-get install redis-server"
    exit 1
fi

if ! redis-cli ping &> /dev/null; then
    echo -e "${YELLOW}⚠ Redis n'est pas démarré${NC}"
    echo "  Démarrage de Redis..."
    if [[ "$OSTYPE" == "darwin"* ]]; then
        brew services start redis
    else
        sudo systemctl start redis
    fi
    sleep 2
fi

if redis-cli ping &> /dev/null; then
    echo -e "${GREEN}✓ Redis est actif${NC}"
else
    echo -e "${RED}✗ Impossible de démarrer Redis${NC}"
    exit 1
fi

# 4. Configuration .env
echo ""
echo -e "${YELLOW}[4/8]${NC} Configuration de l'environnement..."

if [ ! -f .env ]; then
    echo "  Copie de .env.example vers .env..."
    cp .env.example .env
    
    # Generate MAIL_CRYPTO_KEY
    echo "  Génération de MAIL_CRYPTO_KEY..."
    CRYPTO_KEY=$(php -r "echo base64_encode(random_bytes(32));")
    
    # Replace in .env
    if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i '' "s|MAIL_CRYPTO_KEY=|MAIL_CRYPTO_KEY=${CRYPTO_KEY}|" .env
    else
        sed -i "s|MAIL_CRYPTO_KEY=|MAIL_CRYPTO_KEY=${CRYPTO_KEY}|" .env
    fi
    
    echo -e "${GREEN}✓ .env créé avec clé de chiffrement${NC}"
    echo -e "${YELLOW}  ⚠ IMPORTANT: Configurez vos credentials OAuth dans .env${NC}"
else
    echo -e "${GREEN}✓ .env existe déjà${NC}"
fi

# 5. Create logs directory
echo ""
echo -e "${YELLOW}[5/8]${NC} Création des répertoires..."
mkdir -p logs
chmod 755 logs
echo -e "${GREEN}✓ Répertoire logs créé${NC}"

# 6. Database migration
echo ""
echo -e "${YELLOW}[6/8]${NC} Migration de la base de données..."
echo "  Veuillez entrer vos credentials MySQL:"
read -p "  DB User [root]: " DB_USER
DB_USER=${DB_USER:-root}

read -sp "  DB Password: " DB_PASS
echo ""

read -p "  DB Name [webitech]: " DB_NAME
DB_NAME=${DB_NAME:-webitech}

echo "  Exécution de la migration..."
if mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/migrations/001_add_oauth_support.sql 2>/dev/null; then
    echo -e "${GREEN}✓ Migration SQL exécutée avec succès${NC}"
else
    echo -e "${RED}✗ Erreur lors de la migration SQL${NC}"
    echo "  Exécutez manuellement:"
    echo "  mysql -u $DB_USER -p $DB_NAME < database/migrations/001_add_oauth_support.sql"
fi

# 7. Test encryption
echo ""
echo -e "${YELLOW}[7/8]${NC} Test du chiffrement..."
TEST_RESULT=$(php -r "
try {
    require 'includes/EmailCrypto.php';
    \$crypto = new EmailCrypto();
    \$encrypted = \$crypto->encrypt('test');
    \$decrypted = \$crypto->decrypt(\$encrypted);
    echo (\$decrypted === 'test') ? 'OK' : 'FAIL';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
")

if [ "$TEST_RESULT" = "OK" ]; then
    echo -e "${GREEN}✓ Chiffrement fonctionne correctement${NC}"
else
    echo -e "${RED}✗ Test de chiffrement échoué: $TEST_RESULT${NC}"
fi

# 8. Worker setup
echo ""
echo -e "${YELLOW}[8/8]${NC} Configuration des workers..."
chmod +x workers/email-sync-worker.php
echo -e "${GREEN}✓ Workers configurés${NC}"

# Summary
echo ""
echo "========================================="
echo -e "${GREEN}Installation terminée!${NC}"
echo "========================================="
echo ""
echo "📋 Prochaines étapes:"
echo ""
echo "1. Configurez OAuth dans .env:"
echo "   - GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET"
echo "   - MICROSOFT_CLIENT_ID, MICROSOFT_CLIENT_SECRET"
echo ""
echo "2. Démarrez les workers:"
echo "   Option A: php workers/email-sync-worker.php"
echo "   Option B: sudo supervisorctl start crm-workers:*"
echo ""
echo "3. Testez l'intégration:"
echo "   - Allez sur: http://localhost/crm/email-settings.php"
echo "   - Cliquez 'Connecter Gmail' ou 'Connecter Outlook'"
echo ""
echo "📚 Documentation: EMAIL_OAUTH_SETUP.md"
echo "✅ Tests: EMAIL_TESTS_CHECKLIST.md"
echo ""
