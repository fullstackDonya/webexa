#!/bin/bash
# Webexa Index.php Deployment Script
# This script copies the new index.php and API files to the VPS

set -e

# Configuration
VPS_IP="87.106.3.49"
VPS_USER="root"
VPS_PATH="/var/www/webexa"
LOCAL_PATH="/Applications/MAMP/htdocs/webexa"

echo "🚀 Déploiement de Webexa index.php vers le VPS"
echo "================================================"

# Check if SSH key is available
if [ -z "$SSH_KEY" ]; then
    echo "⚠️  Variable SSH_KEY non définie. Utilisation de la clé SSH par défaut."
    SSH_CMD="ssh"
    SCP_CMD="scp"
else
    echo "✅ Utilisation de la clé SSH spécifiée"
    SSH_CMD="ssh -i $SSH_KEY"
    SCP_CMD="scp -i $SSH_KEY"
fi

# Step 1: Backup existing index.php on VPS
echo ""
echo "📦 Étape 1/4 - Sauvegarde de l'ancien index.php sur VPS..."
$SSH_CMD -o ConnectTimeout=5 $VPS_USER@$VPS_IP \
    "cd $VPS_PATH && \
    if [ -f index.php ]; then \
        cp index.php index.php.backup.\$(date +%Y%m%d_%H%M%S) && \
        echo '✅ Ancien fichier sauvegardé'; \
    fi"

# Step 2: Copy index.php
echo ""
echo "📋 Étape 2/4 - Copie du nouveau index.php..."
$SCP_CMD -o ConnectTimeout=5 "$LOCAL_PATH/index.php" "$VPS_USER@$VPS_IP:$VPS_PATH/index.php"
echo "✅ index.php copié avec succès"

# Step 3: Copy auth API files
echo ""
echo "🔐 Étape 3/4 - Copie des fichiers API d'authentification..."
$SCP_CMD -o ConnectTimeout=5 -r "$LOCAL_PATH/crm/api/auth" "$VPS_USER@$VPS_IP:$VPS_PATH/crm/api/"
echo "✅ Fichiers API copiés avec succès"

# Step 4: Set permissions
echo ""
echo "🔒 Étape 4/4 - Configuration des permissions..."
$SSH_CMD -o ConnectTimeout=5 $VPS_USER@$VPS_IP \
    "cd $VPS_PATH && \
    chown -R www-data:www-data . && \
    find . -type f -name '*.php' -exec chmod 644 {} \; && \
    find . -type d -exec chmod 755 {} \; && \
    echo '✅ Permissions configurées'"

# Verification
echo ""
echo "✔️  Vérification du déploiement..."
$SSH_CMD -o ConnectTimeout=5 $VPS_USER@$VPS_IP \
    "test -f $VPS_PATH/index.php && echo '✅ index.php présent' || echo '❌ index.php manquant'; \
    test -f $VPS_PATH/crm/api/auth/login.php && echo '✅ API login présente' || echo '❌ API login manquante'; \
    test -f $VPS_PATH/crm/api/auth/register.php && echo '✅ API register présente' || echo '❌ API register manquante'"

echo ""
echo "🎉 Déploiement terminé avec succès!"
echo ""
echo "📝 Prochaines étapes:"
echo "1. Vérifier https://webexa.fr/ dans le navigateur"
echo "2. Tester le flux de connexion"
echo "3. Tester le flux d'inscription"
echo "4. Configurer les OAuth (Google, Microsoft)"
