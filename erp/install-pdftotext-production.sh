#!/bin/bash
# Script d'installation pour serveur de production (Hostinger/VPS)

echo "=========================================="
echo "Installation pdftotext sur serveur Linux"
echo "=========================================="
echo ""

# Vérifier si déjà installé
if command -v pdftotext &> /dev/null; then
    echo "✅ pdftotext est déjà installé !"
    pdftotext -v
    exit 0
fi

echo "📦 Installation de poppler-utils (contient pdftotext)..."
echo ""

# Méthode 1 : Avec droits sudo (VPS)
if sudo -n true 2>/dev/null; then
    echo "Installation avec sudo..."
    sudo apt-get update
    sudo apt-get install -y poppler-utils
    
# Méthode 2 : Sans sudo (hébergement partagé Hostinger)
else
    echo "⚠️  Pas de droits sudo détectés"
    echo ""
    echo "🔧 Pour Hostinger hébergement partagé :"
    echo "   1. Contactez le support Hostinger"
    echo "   2. Demandez l'installation de 'poppler-utils'"
    echo "   3. OU utilisez l'IA (GPT-4 Vision) comme solution"
    echo ""
    echo "📝 Message pour le support :"
    echo "-------------------------------------"
    echo "Bonjour,"
    echo "Pourriez-vous installer le package 'poppler-utils' "
    echo "sur mon serveur pour extraire le texte des PDF ?"
    echo "Commande : apt-get install poppler-utils"
    echo "Merci !"
    echo "-------------------------------------"
    exit 1
fi

# Vérifier l'installation
if command -v pdftotext &> /dev/null; then
    echo ""
    echo "✅ Installation réussie !"
    pdftotext -v
    echo ""
    echo "🎉 Vous pouvez maintenant extraire les données des PDF gratuitement"
else
    echo ""
    echo "❌ Échec de l'installation"
    echo "Utilisez l'IA (GPT-4 Vision) comme alternative"
fi
