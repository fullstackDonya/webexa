#!/bin/bash
# 🗄️ GUIDE SETUP DATABASE WEBEXA
# Comment créer et configurer la base de données

cat << 'EOF'

╔══════════════════════════════════════════════════════════════════════════╗
║                                                                          ║
║      🗄️  CRÉATION DE LA BASE DE DONNÉES WEBEXA                         ║
║                                                                          ║
║   Erreur: User 'siteo_user'@'localhost' n'a pas les permissions        ║
║                                                                          ║
╚══════════════════════════════════════════════════════════════════════════╝


📋 RÉSUMÉ DU PROBLÈME
═══════════════════════════════════════════════════════════════════════════

✗ Vous essayez de créer la base 'webexa' avec l'utilisateur 'siteo_user'
✗ Cet utilisateur N'A PAS les permissions pour créer une BD

✓ SOLUTION: Se connecter avec ROOT ou un administrateur MySQL


═══════════════════════════════════════════════════════════════════════════

🚀 SOLUTION RAPIDE (4 ÉTAPES)
═══════════════════════════════════════════════════════════════════════════

ÉTAPE 1: Se connecter à MySQL en tant que ROOT
────────────────────────────────────────────────

   LOCAL (MAMP):
   mysql -u root -p
   # Mot de passe MAMP par défaut: (vide - appuyez sur Entrée)

   OU LOCAL (sans mot de passe):
   mysql -u root


ÉTAPE 2: Exécuter le script de setup (copier-coller)
────────────────────────────────────────────────────

   -- Créer la base de données
   CREATE DATABASE IF NOT EXISTS webexa 
   CHARACTER SET utf8mb4 
   COLLATE utf8mb4_unicode_ci;

   -- Créer l'utilisateur
   CREATE USER IF NOT EXISTS 'webexa_user'@'localhost' 
   IDENTIFIED BY 'changez_ce_mot_de_passe';

   -- Accorder les permissions
   GRANT ALL PRIVILEGES ON webexa.* TO 'webexa_user'@'localhost';

   -- Appliquer les changements
   FLUSH PRIVILEGES;


ÉTAPE 3: Importer le schéma de la base
───────────────────────────────────────

   mysql -u webexa_user -p webexa < /path/to/crm_database.sql

   # Mot de passe: celui que vous avez mis à l'étape 2


ÉTAPE 4: Vérifier que tout fonctionne
──────────────────────────────────────

   mysql -u webexa_user -p -e "USE webexa; SHOW TABLES;"


═══════════════════════════════════════════════════════════════════════════

💻 INSTRUCTIONS DÉTAILLÉES (LOCAL - MAMP)
═══════════════════════════════════════════════════════════════════════════

1. Ouvrez le terminal et connectez-vous à MySQL:

   mysql -u root

   (Si demande mot de passe, appuyez sur Entrée)


2. Exécutez le script setup_database.sql:

   Vous pouvez soit:
   
   A) Copier-coller le contenu dans mysql> :
   
      source /Applications/MAMP/htdocs/webexa/setup_database.sql
   
   B) Ou importer directement en ligne de commande:
   
      mysql -u root < /Applications/MAMP/htdocs/webexa/setup_database.sql


3. Importez le schéma de base:

   mysql -u webexa_user -p webexa < /Applications/MAMP/htdocs/webexa/crm/crm_database.sql
   
   Mot de passe: (celui que vous avez défini)


4. Vérifiez:

   mysql -u webexa_user -p -e "USE webexa; SHOW TABLES;"


═══════════════════════════════════════════════════════════════════════════

🖥️  INSTRUCTIONS POUR VPS (SSH)
═══════════════════════════════════════════════════════════════════════════

1. SSH vers votre VPS:

   ssh root@votre-ip-vps


2. Connectez-vous à MySQL:

   mysql -u root -p
   # Entrez le mot de passe root MySQL du VPS


3. Exécutez le script setup:

   mysql -u root < setup_database.sql


4. Importez le schéma:

   mysql -u webexa_user -p webexa < crm_database.sql


═══════════════════════════════════════════════════════════════════════════

⚠️  IMPORTANT: CHANGER LE MOT DE PASSE!
═══════════════════════════════════════════════════════════════════════════

Dans le script setup_database.sql, remplacez:

   'secure_password_here'

Par un mot de passe FORT comme:

   'Webexa@2024!Secure#Password'

Puis mettez à jour votre fichier .env:

   DB_HOST=localhost
   DB_USERNAME=webexa_user
   DB_PASSWORD=Webexa@2024!Secure#Password
   DB_DATABASE=webexa


═══════════════════════════════════════════════════════════════════════════

✅ VÉRIFICATION FINALE
═══════════════════════════════════════════════════════════════════════════

Après avoir importé, vérifiez:

1. La base existe:
   mysql -u root -e "SHOW DATABASES;" | grep webexa

2. Les tables ont été créées:
   mysql -u webexa_user -p webexa -e "SHOW TABLES;"

3. L'utilisateur a les bonnes permissions:
   mysql -u root -e "SHOW GRANTS FOR 'webexa_user'@'localhost';"


═══════════════════════════════════════════════════════════════════════════

❓ FAQ
═══════════════════════════════════════════════════════════════════════════

Q: Je ne connais pas le mot de passe root MySQL
R: 
   - MAMP: Pas de mot de passe (appuyez sur Entrée)
   - VPS: Demandez à votre fournisseur VPS
   - Local: Voir /Applications/MAMP/conf/mysql/my.cnf


Q: Comment savoir si l'import a réussi?
R: 
   mysql -u webexa_user -p webexa -e "SELECT COUNT(*) as 'Nombre de tables' FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'webexa';"


Q: Je veux réinitialiser la base?
R:
   mysql -u root -e "DROP DATABASE webexa; DROP USER 'webexa_user'@'localhost';"
   Puis refaire les étapes.


═══════════════════════════════════════════════════════════════════════════

📝 RÉSUMÉ DES COMMANDES
═══════════════════════════════════════════════════════════════════════════

# Exécuter le script de setup (local):
mysql -u root < setup_database.sql

# Importer le schéma:
mysql -u webexa_user -p webexa < crm_database.sql

# Vérifier:
mysql -u webexa_user -p -e "USE webexa; SHOW TABLES;"

# Connexion de test:
mysql -u webexa_user -p webexa


═══════════════════════════════════════════════════════════════════════════

🎯 PROCHAIN ÉTAPE
═══════════════════════════════════════════════════════════════════════════

1. ✅ Créer la base de données (ce script)
2. ✅ Importer le schéma (crm_database.sql)
3. ⏭️  Mettre à jour .env avec les credentials
4. ⏭️  Tester la connexion dans l'app PHP


═══════════════════════════════════════════════════════════════════════════

Besoin d'aide?
   → Consultez: DATABASE_SETUP.md


EOF

echo ""
echo "✅ Guide créé! Consultez le fichier 'DATABASE_SETUP.md' pour les détails."
echo ""
