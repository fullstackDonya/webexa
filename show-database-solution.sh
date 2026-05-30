#!/bin/bash
# Affichage visuel de la solution du problème de base de données

clear

cat << 'EOF'

╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║                   🗄️  ERREUR: PERMISSIONS DATABASE                          ║
║                                                                              ║
║  Erreur: #1044 - Accès refusé pour l'utilisateur: 'siteo_user'@'localhost' ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝


🔍 COMPRENDRE LE PROBLÈME
═══════════════════════════════════════════════════════════════════════════════

   Vous avez tenté:  CREATE DATABASE webexa;
   Avec l'utilisateur: siteo_user
   
   ❌ PROBLÈME: siteo_user n'a pas les permissions pour créer une BD


✅ LA SOLUTION
═══════════════════════════════════════════════════════════════════════════════

   Utiliser un compte ADMINISTRATEUR pour créer la BD
   
   Puis créer un utilisateur spécifique 'webexa_user' pour l'application


3️⃣  TROIS SOLUTIONS (Du plus facile au plus manuel)
═══════════════════════════════════════════════════════════════════════════════


┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  ✨ SOLUTION 1: Script Interactif (LE PLUS FACILE!) ⭐                     │
│                                                                             │
│  Juste taper:                                                              │
│                                                                             │
│     chmod +x setup-database.sh                                             │
│     ./setup-database.sh                                                    │
│                                                                             │
│  Le script fait TOUT automatiquement!                                      │
│  - Vérifie MySQL                                                            │
│  - Demande vos identifiants                                                 │
│  - Crée la base                                                             │
│  - Importe le schéma                                                        │
│  - Vérifie le résultat                                                      │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  📝 SOLUTION 2: Fichier SQL + Terminal                                     │
│                                                                             │
│  1. Éditer setup_database.sql                                              │
│     nano setup_database.sql                                                │
│     Remplacer 'secure_password_here' par un mot de passe                   │
│                                                                             │
│  2. Exécuter le script:                                                     │
│     mysql -u root < setup_database.sql                                     │
│                                                                             │
│  3. Importer le schéma:                                                     │
│     mysql -u webexa_user -p webexa < crm/crm_database.sql                  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘


┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  🖥️  SOLUTION 3: Commandes MySQL Directes                                 │
│                                                                             │
│  1. Ouvrir MySQL:                                                           │
│     mysql -u root                                                          │
│                                                                             │
│  2. Exécuter (copier-coller):                                              │
│                                                                             │
│     CREATE DATABASE webexa CHARACTER SET utf8mb4;                          │
│     CREATE USER 'webexa_user'@'localhost' IDENTIFIED BY 'MonPassword123!'; │
│     GRANT ALL PRIVILEGES ON webexa.* TO 'webexa_user'@'localhost';         │
│     FLUSH PRIVILEGES;                                                       │
│     EXIT;                                                                   │
│                                                                             │
│  3. Importer le schéma:                                                     │
│     mysql -u webexa_user -p webexa < crm/crm_database.sql                  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘


🎯 LA SOLUTION LA PLUS RAPIDE
═══════════════════════════════════════════════════════════════════════════════

Une seule commande:

   chmod +x setup-database.sh && ./setup-database.sh

Et suivez les prompts!


📋 ÉTAPES APRÈS LA CRÉATION
═══════════════════════════════════════════════════════════════════════════════

1. Mettre à jour .env:

   nano .env

   DB_HOST=localhost
   DB_USERNAME=webexa_user
   DB_PASSWORD=VotreMotDePasse
   DB_DATABASE=webexa


2. Vérifier la connexion:

   mysql -u webexa_user -p webexa -e "SHOW TABLES;"


3. Utiliser l'application:

   Accédez à http://localhost/webexa
   L'app se connectera à la BD automatiquement!


✅ RÉSUMÉ DE CE QUI VA ARRIVER
═══════════════════════════════════════════════════════════════════════════════

AVANT (❌ Erreur):
   siteo_user → Créer webexa → ERREUR!
   
APRÈS (✅ Succès):
   root → Crée webexa + webexa_user
   
   webexa_user → Accès à webexa → ✅ OK!
   
   Application → Utilise webexa_user → ✅ Connectée!


🔐 NOTES DE SÉCURITÉ
═══════════════════════════════════════════════════════════════════════════════

✓ Utilisez un mot de passe FORT (minimum 12 caractères)
✓ Mélangez majuscules, minuscules, chiffres, symboles
✓ Sauvegardez le mot de passe quelque part (gestionnaire)
✓ Ne le commitez PAS dans Git (.env en .gitignore)

Exemple de bon mot de passe:
   Webexa@2024!SecureDB#Pass

Exemple de MAUVAIS mot de passe:
   webexa123
   password
   12345678


📞 BESOIN D'AIDE?
═══════════════════════════════════════════════════════════════════════════════

Consultez: DATABASE_SETUP.md

Pour la vue complète: SOLUTION_DATABASE.md


═══════════════════════════════════════════════════════════════════════════════

Vous êtes prêt! Lancez:

   chmod +x setup-database.sh
   ./setup-database.sh

Puis mettez à jour .env et c'est parti! 🚀

═══════════════════════════════════════════════════════════════════════════════

EOF

echo ""
echo "💡 Conseil: Lire DATABASE_SETUP.md pour plus de détails"
echo ""
