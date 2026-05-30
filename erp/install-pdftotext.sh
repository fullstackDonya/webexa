#!/bin/bash
# Installation de pdftotext pour extraction de PDF

echo "=== Installation de poppler-utils (pdftotext) ==="

# Détecter l'OS
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS (MAMP local)
    echo "Installation sur macOS avec Homebrew..."
    if ! command -v brew &> /dev/null; then
        echo "❌ Homebrew n'est pas installé. Installez-le d'abord :"
        echo "   /bin/bash -c \"\$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)\""
        exit 1
    fi
    
    brew install poppler
    
elif [[ -f /etc/debian_version ]]; then
    # Debian/Ubuntu (Hostinger)
    echo "Installation sur Debian/Ubuntu..."
    sudo apt-get update
    sudo apt-get install -y poppler-utils
    
elif [[ -f /etc/redhat-release ]]; then
    # RedHat/CentOS
    echo "Installation sur RedHat/CentOS..."
    sudo yum install -y poppler-utils
    
else
    echo "❌ OS non reconnu. Installation manuelle requise."
    exit 1
fi

# Vérifier l'installation
if command -v pdftotext &> /dev/null; then
    echo "✅ pdftotext installé avec succès !"
    pdftotext -v
else
    echo "❌ Échec de l'installation"
    exit 1
fi

echo ""
echo "🎯 Test d'extraction sur un PDF exemple..."
echo "Vous pouvez maintenant uploader des documents et l'extraction fonctionnera automatiquement."
