#!/bin/bash

# Script de test pour vérifier la configuration des campagnes
# Usage: bash test-campaign-config.sh

echo "🧪 Test de la configuration des campagnes"
echo "=========================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables
DB_NAME="webitech"
DB_USER="root"
DB_PASS=""

echo "📋 Étape 1: Vérification des fichiers créés"
echo "-------------------------------------------"

files=(
    "database/migrations/003_link_campaigns_to_configs.sql"
    "includes/campaign-config-helper.php"
    "CONFIGURATION_CAMPAGNES.md"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file existe"
    else
        echo -e "${RED}✗${NC} $file manquant"
    fi
done

echo ""
echo "📊 Étape 2: Vérification de la base de données"
echo "-----------------------------------------------"

# Vérifier si MySQL est accessible
if command -v mysql &> /dev/null; then
    echo -e "${GREEN}✓${NC} MySQL est installé"
    
    # Vérifier les colonnes de la table campaigns
    echo ""
    echo "Vérification des colonnes de la table 'campaigns':"
    
    columns=("email_config_id" "whatsapp_config_id" "channel")
    
    for col in "${columns[@]}"; do
        result=$(mysql -u $DB_USER $DB_NAME -e "SHOW COLUMNS FROM campaigns LIKE '$col'" 2>/dev/null | wc -l)
        if [ $result -gt 1 ]; then
            echo -e "  ${GREEN}✓${NC} Colonne '$col' existe"
        else
            echo -e "  ${YELLOW}⚠${NC} Colonne '$col' n'existe pas (exécutez la migration SQL)"
        fi
    done
    
    # Vérifier les configurations email
    echo ""
    echo "Statistiques des configurations:"
    email_count=$(mysql -u $DB_USER $DB_NAME -e "SELECT COUNT(*) FROM email_configurations" -s -N 2>/dev/null || echo "0")
    whatsapp_count=$(mysql -u $DB_USER $DB_NAME -e "SELECT COUNT(*) FROM whatsapp_configurations" -s -N 2>/dev/null || echo "0")
    campaigns_count=$(mysql -u $DB_USER $DB_NAME -e "SELECT COUNT(*) FROM campaigns" -s -N 2>/dev/null || echo "0")
    
    echo "  📧 Configurations email : $email_count"
    echo "  📱 Configurations WhatsApp : $whatsapp_count"
    echo "  📣 Campagnes : $campaigns_count"
    
else
    echo -e "${RED}✗${NC} MySQL n'est pas accessible"
fi

echo ""
echo "🔧 Étape 3: Vérification du code PHP"
echo "--------------------------------------"

# Vérifier la syntaxe PHP des fichiers
if command -v php &> /dev/null; then
    echo -e "${GREEN}✓${NC} PHP est installé"
    
    php_files=(
        "includes/campaign-config-helper.php"
        "includes/send-campaign.php"
    )
    
    for file in "${php_files[@]}"; do
        if [ -f "$file" ]; then
            php -l "$file" > /dev/null 2>&1
            if [ $? -eq 0 ]; then
                echo -e "  ${GREEN}✓${NC} $file : syntaxe valide"
            else
                echo -e "  ${RED}✗${NC} $file : erreur de syntaxe"
            fi
        fi
    done
else
    echo -e "${RED}✗${NC} PHP n'est pas installé"
fi

echo ""
echo "📝 Étape 4: Prochaines actions"
echo "------------------------------"

if [ $email_count -eq 0 ] && [ $whatsapp_count -eq 0 ]; then
    echo -e "${YELLOW}⚠${NC} Aucune configuration email/WhatsApp trouvée"
    echo "   → Configurez un compte dans email-settings.php ou whatsapp-settings.php"
fi

if ! mysql -u $DB_USER $DB_NAME -e "SHOW COLUMNS FROM campaigns LIKE 'channel'" 2>/dev/null | grep -q "channel"; then
    echo -e "${YELLOW}⚠${NC} Migration SQL non exécutée"
    echo "   → Exécutez: mysql -u root webitech < database/migrations/003_link_campaigns_to_configs.sql"
fi

echo ""
echo "✅ Test terminé!"
echo ""
echo "📖 Consultez CONFIGURATION_CAMPAGNES.md pour la documentation complète"
echo ""
