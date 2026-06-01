# 🚀 Guide de Déploiement - Webexa Index.php Redesign v2024

## 📋 Résumé des Changements

Le fichier `index.php` a été complètement redesigné avec une interface **Monday.com-style** consolidant tous les éléments d'authentification en une seule page élégante.

### ✅ Changements Effectués

#### 1. **Nouveau index.php**
- **Design**: Split-screen layout (gradient sidebar + white auth section)
- **Modes**: Welcome → Login → Signup (3-step wizard)
- **Responsive**: Mobile, tablet, desktop

#### 2. **Nouveaux Fichiers API** (crm/api/auth/)
```
✅ login.php              - Authentification email/password
✅ register.php           - Création de compte utilisateur  
✅ oauth-google.php       - Redirection OAuth Google
✅ oauth-microsoft.php    - Redirection OAuth Microsoft
```

---

## 🧪 Testing Local

### 1. **Accès à la page**
```
http://localhost/webexa/
```

### 2. **Test Welcome Screen**
- ✅ Design Monday.com visible
- ✅ Boutons "Se connecter" et "S'inscrire"
- ✅ Sidebar avec features

### 3. **Test Login**
```
POST /crm/api/auth/login.php
Body: email, password
Expected: redirect to crm/index.php
```

### 4. **Test Signup**
```
Step 1: Personal info
Step 2: Company info
Step 3: Module selection
Final: POST to /api/setup-wizard.php
Expected: redirect to crm/index.php
```

### 5. **Vérifier Base de Données**
```sql
SELECT * FROM users WHERE email = 'test@example.com';
SELECT * FROM companies WHERE interne_customer = 1;
```

---

## 🚀 Déploiement VPS

### **Script Automatique (Recommandé)**

```bash
cd /Applications/MAMP/htdocs/webexa
./deploy-index.sh
```

### **Copie Manuelle**

```bash
# Sauvegarder
ssh root@87.106.3.49 "cd /var/www/webexa && cp index.php index.php.backup"

# Copier fichiers
scp /Applications/MAMP/htdocs/webexa/index.php root@87.106.3.49:/var/www/webexa/
scp -r /Applications/MAMP/htdocs/webexa/crm/api/auth root@87.106.3.49:/var/www/webexa/crm/api/

# Permissions
ssh root@87.106.3.49 "chown -R www-data:www-data /var/www/webexa"
```

---

## ✔️ Vérification Post-Déploiement

```bash
# Vérifier fichiers
ssh root@87.106.3.49 "ls -la /var/www/webexa/index.php /var/www/webexa/crm/api/auth/"

# Syntaxe PHP
ssh root@87.106.3.49 "php -l /var/www/webexa/index.php"

# Test navigateur
https://webexa.fr/
```

---

## 🔧 Configuration OAuth

### Google
```env
GOOGLE_CLIENT_ID=xxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=xxxxx
# Redirect URI: https://webexa.fr/crm/api/auth/oauth-google-callback.php
```

### Microsoft
```env
MICROSOFT_CLIENT_ID=xxxxx
MICROSOFT_CLIENT_SECRET=xxxxx
# Redirect URI: https://webexa.fr/crm/api/auth/oauth-microsoft-callback.php
```

---

## 🐛 Dépannage

| Erreur | Cause | Solution |
|--------|-------|----------|
| 404 /api/setup-wizard.php | Symlink manquant | `ln -s crm/api api` |
| Database connection error | .env credentials | Vérifier DB_USER, DB_PASS |
| Failed to parse dotenv | Quotes manquantes | `GOOGLE_SCOPES="value1 value2"` |
| Session not saved | Permissions dossier | `chmod 777 /var/lib/php/sessions/` |

---

## 📱 Responsive Design

Fonctionne sur: Desktop, Tablet, Mobile

Test avec Chrome DevTools (Ctrl+Shift+M)

---

## 📊 Fichiers Modifiés

| Fichier | Status |
|---------|--------|
| index.php | ✅ REMPLACÉ (Monday.com UI) |
| crm/api/auth/login.php | ✅ NOUVEAU |
| crm/api/auth/register.php | ✅ NOUVEAU |
| crm/api/auth/oauth-google.php | ✅ NOUVEAU |
| crm/api/auth/oauth-microsoft.php | ✅ NOUVEAU |
| crm/api/setup-wizard.php | ❌ Pas de changement |
| Database schema | ❌ Pas de changement |

**Backup**: `index.php.backup` sauvegardé localement

---

## 🎯 Checklist de Lancement

- [ ] Tests locaux réussis
- [ ] Déploiement VPS réussi
- [ ] Google OAuth configuré
- [ ] Microsoft OAuth configuré
- [ ] Test signup complet
- [ ] Test login complet
- [ ] Vérifier companies.interne_customer = 1
- [ ] Logs d'erreur vérifiés
- [ ] Design responsive vérifié

---

**Version**: 2024.01
**Status**: ✅ Ready for Deployment
