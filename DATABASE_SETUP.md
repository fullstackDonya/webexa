# 🗄️ SETUP DATABASE WEBEXA

## ❌ Le Problème

```
Error: #1044 - Accès refusé pour l'utilisateur: 'siteo_user'@'localhost'. Base 'webexa'
```

**Cause:** L'utilisateur `siteo_user` n'a pas les permissions pour **créer** une base de données.

---

## ✅ La Solution (3 étapes simples)

### **ÉTAPE 1: Créer la base de données**

Connectez-vous à MySQL avec un compte administrateur:

```bash
# Sur MAMP (local):
mysql -u root

# Sur VPS:
mysql -u root -p
# (Entrez le mot de passe root MySQL)
```

Une fois connecté, copier-coller ce script:

```sql
-- Créer la base de données
CREATE DATABASE IF NOT EXISTS webexa 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Créer l'utilisateur webexa
CREATE USER IF NOT EXISTS 'webexa_user'@'localhost' 
IDENTIFIED BY 'votre_mot_de_passe_secure';

-- Accorder les permissions
GRANT ALL PRIVILEGES ON webexa.* TO 'webexa_user'@'localhost';

-- Appliquer les changements
FLUSH PRIVILEGES;

-- Vérifier
SHOW GRANTS FOR 'webexa_user'@'localhost';
```

### **ÉTAPE 2: Importer le schéma de base**

```bash
# Depuis le répertoire webexa
mysql -u webexa_user -p webexa < crm/crm_database.sql

# Entrez le mot de passe défini à l'étape 1
```

### **ÉTAPE 3: Vérifier l'import**

```bash
# Lister les tables
mysql -u webexa_user -p webexa -e "SHOW TABLES;"

# Ou compter:
mysql -u webexa_user -p webexa -e "SELECT COUNT(*) as 'Nombre de tables' FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'webexa';"
```

---

## 🚀 VERSION SUPER RAPIDE (Copy-Paste)

### Locale (MAMP):

```bash
# 1. Ouvrir le terminal
cd /Applications/MAMP/htdocs/webexa

# 2. Créer la base avec le script
mysql -u root < setup_database.sql

# 3. Importer les tables
mysql -u webexa_user -p webexa < crm/crm_database.sql
# Mot de passe: (celui choisi dans setup_database.sql)

# 4. Vérifier
mysql -u webexa_user -p -e "USE webexa; SHOW TABLES;"
```

### VPS (SSH):

```bash
# 1. SSH vers le VPS
ssh root@votre-ip-vps

# 2. Aller au répertoire
cd /var/www/webexa

# 3. Créer la base
mysql -u root -p < setup_database.sql

# 4. Importer les tables
mysql -u webexa_user -p webexa < crm/crm_database.sql

# 5. Vérifier
mysql -u webexa_user -p -e "USE webexa; SHOW TABLES;"
```

---

## 📝 MODIFIER LE MOT DE PASSE

**Important:** Le fichier `setup_database.sql` contient un mot de passe par défaut.

Avant d'exécuter, modifiez-le:

```sql
-- Avant (MAUVAIS):
CREATE USER IF NOT EXISTS 'webexa_user'@'localhost' IDENTIFIED BY 'secure_password_here';

-- Après (BON):
CREATE USER IF NOT EXISTS 'webexa_user'@'localhost' IDENTIFIED BY 'MonMotDePasseSecure123!';
```

Puis mettez à jour `.env`:

```env
DB_HOST=localhost
DB_USERNAME=webexa_user
DB_PASSWORD=MonMotDePasseSecure123!
DB_DATABASE=webexa
```

---

## 🔍 Vérification Étape par Étape

### 1. Vérifier que la base existe:

```bash
mysql -u root -e "SHOW DATABASES;"
```

Vous devriez voir `webexa` dans la liste.

### 2. Vérifier que l'utilisateur existe:

```bash
mysql -u root -e "SELECT User, Host FROM mysql.user WHERE User='webexa_user';"
```

### 3. Vérifier les permissions:

```bash
mysql -u root -e "SHOW GRANTS FOR 'webexa_user'@'localhost';"
```

Résultat attendu:
```
GRANT ALL PRIVILEGES ON `webexa`.* TO `webexa_user`@`localhost`
```

### 4. Vérifier les tables:

```bash
mysql -u webexa_user -p webexa -e "SHOW TABLES;" 
```

Vous devriez voir 20+ tables.

### 5. Compter les tables:

```bash
mysql -u webexa_user -p webexa << 'EOF'
SELECT COUNT(*) as total_tables FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'webexa';
EOF
```

---

## ❓ FAQ

### **Q: Je ne connais pas le mot de passe root MySQL**

**Réponses possibles:**

**MAMP (Mac):**
- Pas de mot de passe - appuyez sur Entrée
- Ou vérifiez dans: `/Applications/MAMP/conf/mysql/my.cnf`

**Linux (VPS):**
- Demandez au fournisseur VPS
- Ou vérifiez: `/etc/mysql/debian.cnf`

**Windows:**
- Normalement pas de mot de passe par défaut

### **Q: Comment réinitialiser la base?**

```bash
# Supprimer la base
mysql -u root -e "DROP DATABASE webexa;"

# Supprimer l'utilisateur
mysql -u root -e "DROP USER 'webexa_user'@'localhost';"

# Puis refaire les étapes
```

### **Q: L'import prend longtemps, c'est normal?**

Oui, le fichier `crm_database.sql` est volumineux. Peut prendre 1-5 minutes selon l'ordinateur.

### **Q: Comment savoir si l'import est en cours?**

```bash
# Dans une autre fenêtre terminal:
mysql -u root -e "SHOW PROCESSLIST;" | grep -i "insert\|load"
```

### **Q: Puis-je modifier les données après import?**

Oui, une fois importées, ce sont des vraies tables MySQL.

### **Q: Besoin de restaurer une ancienne sauvegarde?**

```bash
# D'abord supprimer
mysql -u root -e "DROP DATABASE webexa;"

# Puis importer une autre sauvegarde
mysql -u webexa_user -p webexa < /chemin/vers/ancienne_backup.sql
```

---

## 🔒 Sécurité

### ✅ Bonnes pratiques:

1. **Mot de passe fort:**
   ```
   ✓ MonBDP@Webexa2024#Secure!
   ✓ Minimum 12 caractères
   ✓ Mix de majuscules, minuscules, chiffres, spéciaux
   
   ✗ webexa123
   ✗ password
   ✗ 12345678
   ```

2. **Permissions minimales:**
   ```sql
   -- BON (accès seulement à webexa):
   GRANT ALL PRIVILEGES ON webexa.* TO 'webexa_user'@'localhost';
   
   -- MAUVAIS (accès à tout):
   GRANT ALL PRIVILEGES ON *.* TO 'webexa_user'@'localhost';
   ```

3. **Restreindre l'accès:**
   ```sql
   -- Seulement localhost:
   'webexa_user'@'localhost'
   
   -- Ou depuis une IP spécifique:
   'webexa_user'@'192.168.1.50'
   
   -- Partout (DANGEREUX):
   'webexa_user'@'%'
   ```

---

## 📊 Contenu du schéma importé

Le fichier `crm_database.sql` crée environ **25-30 tables** pour:

- ✅ Users & Auth
- ✅ Customers & Companies
- ✅ Contacts
- ✅ Activities (calls, emails, meetings)
- ✅ Opportunities & Deals
- ✅ Campaigns & Email Marketing
- ✅ CRM Analytics
- ✅ Automations
- ✅ Agent Logs & Actions
- ✅ Audit Logs
- ✅ Settings

---

## 🎯 Prochaines étapes

1. ✅ **Créer la base** (ce script)
2. ✅ **Importer le schéma** (`crm_database.sql`)
3. ⏭️ **Mettre à jour `.env`** avec les credentials
4. ⏭️ **Tester la connexion** dans l'app PHP

---

## 📞 Besoin d'aide?

- **Erreur de permissions?** → Utilisez un compte administrateur (root)
- **Fichier non trouvé?** → Vérifiez le chemin du fichier
- **Erreur SQL?** → Copiez l'erreur et cherchez sur Google
- **Connexion refusée?** → Vérifiez que MySQL est en cours d'exécution

---

## ✨ Résumé

| Étape | Commande |
|-------|----------|
| Créer BD + User | `mysql -u root < setup_database.sql` |
| Importer schéma | `mysql -u webexa_user -p webexa < crm_database.sql` |
| Vérifier | `mysql -u webexa_user -p -e "USE webexa; SHOW TABLES;"` |

---

**Créé:** 30 mai 2026  
**Version:** 1.0.0  
**Pour:** Webexa CRM/ERP Database Setup
