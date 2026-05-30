#!/bin/bash
# 🗄️ SCRIPT INTERACTIF POUR CRÉER LA BASE DE DONNÉES WEBEXA

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

clear

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                                                                ║"
echo "║    🗄️  WEBEXA DATABASE SETUP - SCRIPT INTERACTIF             ║"
echo "║                                                                ║"
echo "║    Configuration automatique de la base de données            ║"
echo "║                                                                ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo ""
echo "📋 Ce script va:"
echo "  1. Créer la base de données 'webexa'"
echo "  2. Créer l'utilisateur 'webexa_user'"
echo "  3. Importer le schéma de la base"
echo "  4. Vérifier l'installation"
echo ""

# ===== STEP 1: Check if MySQL is installed =====
echo -e "${YELLOW}📦 Vérification: MySQL installé?${NC}"

if ! command -v mysql &> /dev/null; then
    echo -e "${RED}❌ MySQL n'est pas trouvé!${NC}"
    echo "Installation:"
    echo "  macOS: brew install mysql"
    echo "  Ubuntu: sudo apt-get install mysql-server"
    echo "  CentOS: sudo yum install mysql-server"
    exit 1
fi

echo -e "${GREEN}✅ MySQL trouvé${NC}"
MYSQL_VERSION=$(mysql --version)
echo "   Version: $MYSQL_VERSION"

echo ""

# ===== STEP 2: Get MySQL credentials =====
echo -e "${YELLOW}🔐 Identifiants MySQL${NC}"
echo ""

read -p "Utilisateur MySQL (défaut: root): " MYSQL_USER
MYSQL_USER=${MYSQL_USER:-root}

read -sp "Mot de passe MySQL (défaut: vide): " MYSQL_PASS
echo ""

DB_NAME="webexa"
DB_USER="webexa_user"

echo ""
read -sp "Nouveau mot de passe pour 'webexa_user': " DB_PASS
echo ""

if [ -z "$DB_PASS" ]; then
    echo -e "${RED}❌ Le mot de passe ne peut pas être vide!${NC}"
    exit 1
fi

echo ""

# ===== STEP 3: Test connection =====
echo -e "${YELLOW}🔌 Test de connexion à MySQL...${NC}"

if [ -z "$MYSQL_PASS" ]; then
    # No password
    if ! mysql -u "$MYSQL_USER" -e "SELECT 1" > /dev/null 2>&1; then
        echo -e "${RED}❌ Impossible de se connecter à MySQL${NC}"
        echo "   Vérifiez l'utilisateur et le mot de passe"
        exit 1
    fi
else
    # With password
    if ! mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" -e "SELECT 1" > /dev/null 2>&1; then
        echo -e "${RED}❌ Impossible de se connecter à MySQL${NC}"
        echo "   Vérifiez l'utilisateur et le mot de passe"
        exit 1
    fi
fi

echo -e "${GREEN}✅ Connexion réussie!${NC}"

echo ""

# ===== STEP 4: Create database and user =====
echo -e "${YELLOW}🗄️  Création de la base de données et de l'utilisateur...${NC}"

SQL_SETUP="
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
"

if [ -z "$MYSQL_PASS" ]; then
    mysql -u "$MYSQL_USER" << EOF
$SQL_SETUP
EOF
else
    mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" << EOF
$SQL_SETUP
EOF
fi

echo -e "${GREEN}✅ Base de données et utilisateur créés!${NC}"

echo ""

# ===== STEP 5: Import schema =====
echo -e "${YELLOW}📥 Import du schéma de la base...${NC}"

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
SQL_FILE="$SCRIPT_DIR/crm/crm_database.sql"

if [ ! -f "$SQL_FILE" ]; then
    echo -e "${RED}❌ Fichier non trouvé: $SQL_FILE${NC}"
    exit 1
fi

echo "   Fichier: $SQL_FILE"
echo "   Taille: $(du -h "$SQL_FILE" | cut -f1)"

read -p "Continuer l'import? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Import annulé."
    exit 1
fi

echo "   Importation en cours... (cela peut prendre quelques minutes)"

if [ -z "$MYSQL_PASS" ]; then
    mysql -u "$MYSQL_USER" "$DB_NAME" < "$SQL_FILE" 2>/dev/null
else
    mysql -u "$MYSQL_USER" -p"$MYSQL_PASS" "$DB_NAME" < "$SQL_FILE" 2>/dev/null
fi

echo -e "${GREEN}✅ Schéma importé!${NC}"

echo ""

# ===== STEP 6: Verify import =====
echo -e "${YELLOW}✅ Vérification de l'import...${NC}"

if [ -z "$MYSQL_PASS" ]; then
    TABLE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB_NAME';" 2>/dev/null | tail -1)
else
    TABLE_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DB_NAME';" 2>/dev/null | tail -1)
fi

echo "   Nombre de tables: $TABLE_COUNT"

if [ "$TABLE_COUNT" -gt 20 ]; then
    echo -e "${GREEN}✅ Import réussi! ($TABLE_COUNT tables)${NC}"
else
    echo -e "${RED}⚠️  Nombre de tables faible ($TABLE_COUNT)${NC}"
fi

echo ""

# ===== STEP 7: Save credentials =====
echo -e "${YELLOW}💾 Sauvegarde des identifiants...${NC}"

ENV_FILE="$SCRIPT_DIR/.env"

if [ -f "$ENV_FILE" ]; then
    echo "   Fichier .env trouvé"
    read -p "Mettre à jour .env? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Update .env
        sed -i.bak "s/^DB_HOST=.*/DB_HOST=localhost/" "$ENV_FILE"
        sed -i.bak "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USER/" "$ENV_FILE"
        sed -i.bak "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" "$ENV_FILE"
        sed -i.bak "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" "$ENV_FILE"
        echo -e "${GREEN}✅ Fichier .env mis à jour!${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Fichier .env non trouvé${NC}"
    echo "   Créez .env manuellement avec:"
    echo ""
    echo "   DB_HOST=localhost"
    echo "   DB_USERNAME=$DB_USER"
    echo "   DB_PASSWORD=$DB_PASS"
    echo "   DB_DATABASE=$DB_NAME"
    echo ""
fi

echo ""

# ===== SUMMARY =====
echo -e "${GREEN}"
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                      ✅ INSTALLATION RÉUSSIE!                  ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo ""
echo "📊 Résumé:"
echo "  Base de données: $DB_NAME"
echo "  Utilisateur: $DB_USER"
echo "  Host: localhost"
echo "  Tables importées: $TABLE_COUNT"
echo ""

echo "🔐 Identifiants (à sauvegarder):"
echo "  DB_HOST=localhost"
echo "  DB_USERNAME=$DB_USER"
echo "  DB_PASSWORD=$DB_PASS"
echo "  DB_DATABASE=$DB_NAME"
echo ""

echo "✅ Prochaines étapes:"
echo "  1. Mettre à jour .env avec les credentials"
echo "  2. Tester la connexion: mysql -u $DB_USER -p $DB_NAME"
echo "  3. Lancer l'application PHP"
echo ""

echo -e "${GREEN}🎉 Base de données prête pour Webexa!${NC}"
echo ""
