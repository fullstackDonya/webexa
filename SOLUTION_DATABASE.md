# 🗄️ CRÉATION DE LA BASE DE DONNÉES - SOLUTIONS

## ❌ Le Problème

```
Error: #1044 - Accès refusé pour l'utilisateur: 'siteo_user'@'localhost'. Base 'webexa'
```

**Cause:** L'utilisateur `siteo_user` n'a pas les permissions pour **créer** une BD.

---

## ✅ Solution Rapide (Choisir UNE des 3)

### **OPTION 1: Script Interactif (Recommandé) ⭐**

```bash
chmod +x setup-database.sh
./setup-database.sh
```

Le script demandera:
- Utilisateur MySQL (défaut: root)
- Mot de passe MySQL
- Nouveau mot de passe pour webexa_user

Puis il fera tout automatiquement! ✨

---

### **OPTION 2: Script SQL Manuel**

1. Éditer le fichier:
   ```bash
   nano setup_database.sql
   ```

2. Remplacer `secure_password_here` par un mot de passe fort

3. Exécuter:
   ```bash
   mysql -u root < setup_database.sql
   ```

4. Importer le schéma:
   ```bash
   mysql -u webexa_user -p webexa < crm/crm_database.sql
   ```

---

### **OPTION 3: Commandes MySQL Directes**

```bash
# 1. Se connecter
mysql -u root

# 2. Copier-coller dans le terminal:

CREATE DATABASE IF NOT EXISTS webexa 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'webexa_user'@'localhost' 
IDENTIFIED BY 'VotreMotDePasse123!';

GRANT ALL PRIVILEGES ON webexa.* TO 'webexa_user'@'localhost';

FLUSH PRIVILEGES;

# 3. Quitter MySQL
exit;

# 4. Importer les tables
mysql -u webexa_user -p webexa < crm/crm_database.sql
```

---

## 📋 Fichiers Créés Pour Vous

| Fichier | Contenu | Utilité |
|---------|---------|---------|
| **setup-database.sh** | Script interactif | À exécuter (le plus facile) |
| **setup_database.sql** | Script SQL | À exécuter avec mysql |
| **DATABASE_SETUP.md** | Guide complet | À consulter pour les détails |
| **setup_database.sh** | Menu instructions | À afficher avec `bash setup_database.sh` |

---

## 🚀 COMMANDE UNIQUE (Le Plus Rapide)

```bash
chmod +x setup-database.sh && ./setup-database.sh
```

Puis suivez les prompts! 🎯

---

## ✅ Après la Création

### 1. Mettre à jour `.env`

```env
DB_HOST=localhost
DB_USERNAME=webexa_user
DB_PASSWORD=VotreMotDePasse
DB_DATABASE=webexa
```

### 2. Vérifier la connexion

```bash
mysql -u webexa_user -p webexa -e "SHOW TABLES;"
```

### 3. Tester dans l'app PHP

L'app devrait se connecter à la base automatiquement! ✅

---

## 🔒 Important: Le Mot de Passe

Utilisez un mot de passe **FORT**:

```
✓ Bob@123!Secure#DB2024
✓ Webexa_Prod_2024@Secure!
✗ webexa123
✗ password
```

Et **sauvegardez-le** quelque part (gestionnaire de mots de passe).

---

## ❓ Problèmes Courants

### "mysql: command not found"
- **Solution:** Installer MySQL
  ```bash
  # macOS
  brew install mysql
  
  # Ubuntu
  sudo apt-get install mysql-server
  ```

### "Access denied for user 'root'"
- **Solution:** Utiliser le bon mot de passe ou `-p` sans mot de passe
  ```bash
  mysql -u root        # Pas de mot de passe
  mysql -u root -p     # Demander le mot de passe
  ```

### "Cannot find file crm_database.sql"
- **Solution:** Exécuter le script depuis le répertoire webexa
  ```bash
  cd /Applications/MAMP/htdocs/webexa
  ./setup-database.sh
  ```

---

## 📞 Besoin d'Aide?

- **Lire:** DATABASE_SETUP.md
- **Afficher le guide:** bash setup_database.sh
- **Questions?** Relire les options ci-dessus

---

## ✨ Résumé

```bash
# Tout ce dont vous avez besoin:
chmod +x setup-database.sh
./setup-database.sh

# Puis dans .env:
# DB_USERNAME=webexa_user
# DB_PASSWORD=VotreMotDePasse
# DB_DATABASE=webexa
```

**C'est tout!** 🎉

---

**Créé:** 30 mai 2026 | **Version:** 1.0.0 | **Status:** ✅ Ready
