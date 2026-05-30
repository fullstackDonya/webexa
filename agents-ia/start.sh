#!/bin/bash

# Script de démarrage automatique de l'API IA
# Usage: ./start.sh

echo "========================================="
echo "🤖 Démarrage des Agents IA pour CRM"
echo "========================================="

# Vérifier si l'environnement virtuel existe
if [ ! -d "venv" ]; then
    echo "❌ Environnement virtuel non trouvé"
    echo "📦 Création de l'environnement virtuel..."
    python3 -m venv venv
    
    echo "📦 Installation des dépendances..."
    source venv/bin/activate
    pip install --upgrade pip
    pip install -r requirements.txt
fi

# Activer l'environnement virtuel
echo "🔧 Activation de l'environnement virtuel..."
source venv/bin/activate

# Vérifier si .env existe
if [ ! -f ".env" ]; then
    echo "⚠️  Fichier .env non trouvé"
    echo "📄 Copie de .env.example vers .env..."
    cp .env.example .env
    echo "⚠️  IMPORTANT: Éditez le fichier .env avec vos configurations !"
    echo "   Notamment: OPENAI_API_KEY, API_SECRET_KEY, DB_PASSWORD"
    exit 1
fi

# Vérifier la connexion à la base de données
echo "🔍 Vérification de la base de données..."
python -c "from database import db; db.execute('SELECT 1')" 2>/dev/null

if [ $? -ne 0 ]; then
    echo "❌ Impossible de se connecter à la base de données"
    echo "   Vérifiez les paramètres DB_* dans .env"
    exit 1
fi

echo "✅ Base de données connectée"

# Vérifier si les tables existent
echo "🔍 Vérification des tables IA..."
python -c "from database import db; db.execute('SELECT 1 FROM agent_logs LIMIT 1')" 2>/dev/null

if [ $? -ne 0 ]; then
    echo "⚠️  Tables IA non trouvées"
    echo "📊 Voulez-vous exécuter la migration maintenant ? (o/n)"
    read -r response
    if [ "$response" = "o" ]; then
        echo "📊 Exécution de la migration..."
        mysql -u root -proot webitech < ../migrations/007_ai_agents_tables.sql
        echo "✅ Migration terminée"
    else
        echo "❌ Migration annulée. Exécutez manuellement :"
        echo "   mysql -u root -proot webitech < ../migrations/007_ai_agents_tables.sql"
        exit 1
    fi
fi

# Créer les dossiers nécessaires
echo "📁 Création des dossiers..."
mkdir -p data logs data/chromadb

# Démarrer l'API
echo ""
echo "========================================="
echo "🚀 Démarrage de l'API FastAPI"
echo "========================================="
echo ""
echo "📍 URL: http://localhost:8000"
echo "📚 Docs: http://localhost:8000/docs"
echo "❤️  Health: http://localhost:8000/health"
echo ""
echo "👉 Appuyez sur Ctrl+C pour arrêter"
echo ""

# Lancer l'API
python main.py
