# 📧 Email Integration OAuth2 - CRM

> **Intégration email production-ready** avec OAuth2 pour Gmail, Outlook et support IMAP/SMTP custom

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Redis](https://img.shields.io/badge/Redis-6.0+-DC382D?logo=redis&logoColor=white)](https://redis.io/)
[![OAuth 2.0](https://img.shields.io/badge/OAuth-2.0-3C873A?logo=oauth&logoColor=white)](https://oauth.net/2/)

---

## ✨ Fonctionnalités

- ✅ **OAuth2** Gmail (Google API) et Outlook (Microsoft Graph)
- ✅ **IMAP/SMTP** pour providers custom (Hostinger, cPanel, etc.)
- ✅ **Chiffrement AES-256-CBC + HMAC** pour tous credentials
- ✅ **Workers background** avec Redis (évite blocage requêtes web)
- ✅ **Auto-refresh tokens** OAuth avec gestion erreurs
- ✅ **Rate limiting** et backoff exponentiel
- ✅ **Multi-tenant** isolation par customer_id

---

## 🚀 Quick Start (5 minutes)

```bash
# 1. Installation automatique
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm
./install-email-oauth.sh

# 2. Configurer .env (copier credentials OAuth)
nano .env

# 3. Démarrer Redis
brew services start redis  # macOS
# ou: sudo systemctl start redis  # Linux

# 4. Lancer worker
php workers/email-sync-worker.php
```

**Ensuite:** Aller sur `http://localhost/crm/email-settings.php` et cliquer **"Connecter Gmail"** ! 🎉

---

## 📚 Documentation

| Document | Description | Durée |
|----------|-------------|-------|
| **[QUICK_START.md](QUICK_START.md)** | Démarrage rapide | 5 min ⚡ |
| **[EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md)** | Guide installation complet | 30 min 📖 |
| **[ARCHITECTURE.md](ARCHITECTURE.md)** | Architecture technique | 15 min 🏗️ |
| **[EMAIL_TESTS_CHECKLIST.md](EMAIL_TESTS_CHECKLIST.md)** | Checklist tests | 60 min ✅ |
| **[INDEX.md](INDEX.md)** | Navigation docs | 2 min 📚 |

---

## 🎯 Cas d'usage

### Connecter Gmail
1. Clic **"Connecter Gmail"** sur `email-settings.php`
2. Autoriser avec Google
3. ✅ Emails synchronisés automatiquement

### Connecter Outlook
1. Clic **"Connecter Outlook"**
2. Autoriser avec Microsoft
3. ✅ Emails synchronisés automatiquement

### Provider Custom (Hostinger, etc.)
1. Clic **"IMAP/SMTP Manuel"**
2. Configurer serveurs IMAP/SMTP
3. ✅ Connexion sécurisée chiffrée

---

## 🛠️ Stack Technique

```
PHP 8.1+
├── openssl (chiffrement)
├── pdo_mysql (database)
├── curl (API calls)
├── redis (queue)
└── imap (IMAP support)

MySQL 5.7+ / 8.0+
Redis 6.0+
OAuth 2.0 (RFC 6749)
```

---

## 📁 Structure

```
crm/
├── includes/              # Classes core
│   ├── EmailCrypto.php
│   ├── GoogleOAuth.php
│   ├── MicrosoftOAuth.php
│   └── EmailSyncQueue.php
├── oauth/                 # Endpoints OAuth
│   ├── google/
│   └── microsoft/
├── workers/               # Background workers
│   └── email-sync-worker.php
├── database/migrations/   # SQL migrations
├── scripts/               # Utilitaires
├── docs/                  # Documentation
└── .env.example           # Config template
```

---

## ⚙️ Configuration OAuth

### Gmail (Google Cloud Console)

1. [console.cloud.google.com](https://console.cloud.google.com)
2. Créer projet → Activer **Gmail API**
3. **OAuth consent screen** → External
4. **Credentials** → OAuth 2.0 Client ID
5. Redirect: `http://localhost/crm/oauth/google/callback`

### Outlook (Azure Portal)

1. [portal.azure.com](https://portal.azure.com)
2. **App registrations** → New → Multitenant
3. **API permissions**: Mail.Read, Mail.Send, offline_access
4. **Client secret** → Create
5. Redirect: `http://localhost/crm/oauth/microsoft/callback`

---

## 🔒 Sécurité

- ✅ Chiffrement **AES-256-CBC + HMAC** pour tous tokens
- ✅ Master key stockée dans **environnement** (jamais en code)
- ✅ **CSRF protection** avec state parameter OAuth
- ✅ **Multi-tenant isolation** (WHERE customer_id = ?)
- ✅ Logs **sans credentials**
- ✅ **.gitignore** protège .env

---

## 🧪 Tests

```bash
# Test chiffrement
php -r "require 'includes/EmailCrypto.php'; \$c = new EmailCrypto(); echo \$c->decrypt(\$c->encrypt('test'));"

# Vérifier queue Redis
redis-cli ZCARD email:sync:queue

# Voir logs worker
tail -f logs/worker.log
```

**Checklist complète:** [EMAIL_TESTS_CHECKLIST.md](EMAIL_TESTS_CHECKLIST.md)

---

## 📊 Monitoring

```sql
-- Comptes avec erreurs
SELECT id, email, error_count, last_error 
FROM email_configurations 
WHERE error_count > 3;

-- Tokens expirés
SELECT id, email, oauth_token_expires_at 
FROM email_configurations 
WHERE oauth_provider != 'none' 
  AND oauth_token_expires_at < NOW();
```

```bash
# Stats queue Redis
redis-cli <<EOF
ZCARD email:sync:queue
KEYS email:sync:processing:*
ZCARD email:sync:failed
EOF
```

---

## 🐛 Troubleshooting

| Problème | Solution |
|----------|----------|
| "MAIL_CRYPTO_KEY not set" | `php scripts/generate-key.php` |
| "Redis connection failed" | `brew services start redis` |
| Token expiré | Clic **"Reconnecter"** sur UI |
| Worker ne démarre pas | `chmod +x workers/email-sync-worker.php` |

**Guide complet:** [EMAIL_OAUTH_SETUP.md#troubleshooting](EMAIL_OAUTH_SETUP.md#troubleshooting)

---

## 🎯 Roadmap

- [x] OAuth Gmail ✅
- [x] OAuth Outlook ✅
- [x] IMAP/SMTP custom ✅
- [x] Workers Redis ✅
- [x] Rate limiting ✅
- [ ] Webhooks Gmail Pub/Sub 🔜
- [ ] Webhooks Microsoft Graph 🔜
- [ ] Email sending capabilities 🔜
- [ ] AI email classification 🔜

---

## 📦 Installation

### Automatique (Recommandé)

```bash
./install-email-oauth.sh
```

### Manuelle

```bash
# 1. Migration DB
mysql -u root webitech < database/migrations/001_add_oauth_support.sql

# 2. Générer clé
php scripts/generate-key.php
# Copier dans .env

# 3. Configurer .env
cp .env.example .env
nano .env

# 4. Redis
brew services start redis

# 5. Workers
php workers/email-sync-worker.php
```

---

## 📞 Support

- **Documentation:** [INDEX.md](INDEX.md)
- **Quick Start:** [QUICK_START.md](QUICK_START.md)
- **Setup Complet:** [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md)
- **Architecture:** [ARCHITECTURE.md](ARCHITECTURE.md)

---

## 📜 Licence

Proprietary - Usage interne uniquement

---

## ✅ Critères d'Acceptation

| Critère | Status |
|---------|--------|
| OAuth Gmail E2E | ✅ |
| OAuth Outlook E2E | ✅ |
| IMAP custom | ✅ |
| Chiffrement AES-256 | ✅ |
| Auto-refresh tokens | ✅ |
| Workers background | ✅ |
| Rate limiting | ✅ |
| Multi-tenant | ✅ |
| Documentation | ✅ |

---

**🚀 Prêt pour production !**

Commencez avec [QUICK_START.md](QUICK_START.md) →
