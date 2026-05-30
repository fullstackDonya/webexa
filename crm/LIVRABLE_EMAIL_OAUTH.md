# 📦 Livrable - Intégration Email OAuth2 Complète

**Version:** 1.0.0  
**Date:** 16 février 2026  
**Stack:** PHP 8.1+ | MySQL | Redis | OAuth2

---

## 📋 Vue d'ensemble

Intégration email **production-ready** pour CRM multi-tenant avec :

✅ **OAuth2** pour Gmail (Google API) et Outlook (Microsoft Graph)  
✅ **IMAP/SMTP** pour providers custom (Hostinger, cPanel, etc.)  
✅ **Chiffrement AES-256-CBC + HMAC** pour tous les credentials  
✅ **Workers background** avec Redis pour éviter blocage requêtes web  
✅ **Auto-refresh tokens** OAuth avec gestion erreurs  
✅ **Rate limiting** + backoff exponentiel  
✅ **Multi-tenant isolation** par customer_id  

---

## 📂 Fichiers Livrés

### 🗄️ Base de Données

| Fichier | Description |
|---------|-------------|
| `database/migrations/001_add_oauth_support.sql` | Migration complète: colonnes OAuth, tables queue, webhooks, rate limits |

**Tables ajoutées/modifiées:**
- `email_configurations` : colonnes OAuth (provider, access_token, refresh_token, expires_at, etc.)
- `email_sync_queue` : queue fallback si Redis indisponible
- `email_webhook_logs` : logs webhooks Gmail/Microsoft
- `email_rate_limits` : tracking rate limits par provider

### 🔐 Sécurité & Chiffrement

| Fichier | Description |
|---------|-------------|
| `includes/EmailCrypto.php` | Classe de chiffrement AES-256-CBC + HMAC-SHA256 |
| `.env.example` | Template variables d'environnement avec toutes les options |
| `includes/env.php` | Loader pour variables .env |
| `scripts/generate-key.php` | Générateur de clé MAIL_CRYPTO_KEY |

**Fonctionnalités:**
- ✅ Chiffrement authenticated (HMAC pour détecter tampering)
- ✅ Master key depuis env (jamais hardcodée)
- ✅ Helper pour chiffrer tokens OAuth en masse
- ✅ Détection corruption avec hash_equals (timing-safe)

### 🔗 OAuth Google (Gmail)

| Fichier | Description |
|---------|-------------|
| `includes/GoogleOAuth.php` | Classe complète OAuth2 Google |
| `oauth/google/connect.php` | Endpoint initiation flow |
| `oauth/google/callback.php` | Endpoint callback après autorisation |

**Fonctionnalités:**
- ✅ Authorization Code Flow avec state CSRF
- ✅ Auto-refresh avec refresh_token
- ✅ Gestion scopes (gmail.readonly, gmail.send, gmail.modify)
- ✅ Revocation token
- ✅ Error handling (invalid_grant, expired token, etc.)

### 🔗 OAuth Microsoft (Outlook)

| Fichier | Description |
|---------|-------------|
| `includes/MicrosoftOAuth.php` | Classe complète OAuth2 Microsoft Graph |
| `oauth/microsoft/connect.php` | Endpoint initiation flow |
| `oauth/microsoft/callback.php` | Endpoint callback après autorisation |

**Fonctionnalités:**
- ✅ Multi-tenant support (tenant = common par défaut)
- ✅ Scopes: Mail.Read, Mail.Send, offline_access
- ✅ Auto-refresh (Microsoft peut retourner nouveau refresh_token)
- ✅ Graph API v1.0 ready

### ⚙️ Workers & Queue

| Fichier | Description |
|---------|-------------|
| `includes/EmailSyncQueue.php` | Classe queue Redis (priority, retry, backoff) |
| `workers/email-sync-worker.php` | Worker background consommant jobs |
| `config/supervisord.conf` | Config Supervisord pour production |

**Fonctionnalités:**
- ✅ Priority queue (high/normal/low)
- ✅ Retry automatique avec backoff exponentiel (1min → 5min → 15min)
- ✅ Dead letter queue pour jobs échoués définitivement
- ✅ Graceful shutdown (SIGTERM/SIGINT)
- ✅ Isolation jobs (évite doublons en cours)
- ✅ Stats (pending, processing, failed)

**Worker implémente:**
- Sync Gmail via Gmail API
- Sync Outlook via Microsoft Graph API
- Sync IMAP classique (Hostinger, custom)

### 🎨 Interface Utilisateur

| Fichier | Description |
|---------|-------------|
| `email-settings.php` | Page settings modifiée avec boutons OAuth |

**Améliorations UI:**
- ✅ Boutons "Connecter Gmail" et "Connecter Outlook"
- ✅ Affichage méthode de connexion (🔐 OAuth2, 🔑 App password, 🔓 Password)
- ✅ Badge statut token (OK / Expiré / Expire bientôt)
- ✅ Bouton "Reconnecter" si token expiré
- ✅ Affichage dernière erreur si présente
- ✅ Messages succès/erreur session après callback OAuth

### 📚 Documentation

| Fichier | Description |
|---------|-------------|
| `EMAIL_OAUTH_SETUP.md` | Guide complet installation et configuration |
| `EMAIL_TESTS_CHECKLIST.md` | Checklist tests manuels détaillée |
| `install-email-oauth.sh` | Script installation automatisé |
| `.gitignore` | Protection credentials et logs |

---

## 🚀 Installation en 3 Minutes

```bash
# 1. Aller dans le dossier CRM
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm

# 2. Rendre le script exécutable et lancer
chmod +x install-email-oauth.sh
./install-email-oauth.sh

# 3. Suivre les instructions (migration DB, config .env)
```

Le script fait automatiquement:
- ✅ Vérification PHP extensions
- ✅ Vérification/démarrage Redis
- ✅ Copie .env.example → .env
- ✅ Génération MAIL_CRYPTO_KEY
- ✅ Migration SQL
- ✅ Test chiffrement
- ✅ Configuration workers

---

## ⚙️ Configuration OAuth (5 minutes)

### Gmail

1. [console.cloud.google.com](https://console.cloud.google.com) → Créer projet
2. Activer **Gmail API**
3. **OAuth consent screen** → Configurer (External)
4. **Credentials** → OAuth 2.0 Client ID
5. Redirect URI: `http://localhost/crm/oauth/google/callback`
6. Copier Client ID + Secret dans `.env`

### Outlook

1. [portal.azure.com](https://portal.azure.com) → **App registrations**
2. **New registration** → Multitenant
3. **API permissions** → Mail.Read, Mail.Send, offline_access
4. **Certificates & secrets** → New client secret
5. Redirect URI: `http://localhost/crm/oauth/microsoft/callback`
6. Copier App ID + Secret dans `.env`

---

## 🎯 Utilisation

### Connecter un compte

1. Aller sur `http://localhost/crm/email-settings.php`
2. Clic **"Connecter Gmail"** ou **"Connecter Outlook"**
3. Autoriser dans popup OAuth
4. ✅ Compte connecté automatiquement

### Synchroniser emails

**Automatique** (recommandé):
```bash
# Démarrer worker
php workers/email-sync-worker.php

# Ou avec supervisord
sudo supervisorctl start crm-workers:*
```

**Manuelle**:
- Clic bouton "Synchroniser" sur carte du compte

### Reconnecter si token expiré

- Badge rouge "Expiré" s'affiche
- Clic bouton "Reconnecter"
- Nouvelle autorisation OAuth

---

## 🧪 Tests

```bash
# Voir checklist complète
cat EMAIL_TESTS_CHECKLIST.md

# Tests rapides
# 1. Test chiffrement
php -r "require 'includes/EmailCrypto.php'; \$c = new EmailCrypto(); echo \$c->decrypt(\$c->encrypt('test')) === 'test' ? 'OK' : 'FAIL';"

# 2. Vérifier queue Redis
redis-cli ZCARD email:sync:queue

# 3. Ajouter job test
php -r "require 'includes/EmailSyncQueue.php'; (new EmailSyncQueue())->enqueue(1);"

# 4. Vérifier logs worker
tail -f logs/worker.log
```

---

## 📊 Monitoring Production

### Métriques clés

```bash
# Queue stats
redis-cli <<EOF
ZCARD email:sync:queue
KEYS email:sync:processing:*
ZCARD email:sync:failed
EOF
```

### SQL Queries

```sql
-- Comptes avec erreurs répétées
SELECT id, email, error_count, last_error 
FROM email_configurations 
WHERE error_count > 3;

-- Tokens expirés
SELECT id, email, oauth_token_expires_at 
FROM email_configurations 
WHERE oauth_provider != 'none' 
  AND oauth_token_expires_at < NOW();

-- Stats sync
SELECT 
  COUNT(*) as total_configs,
  SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
  SUM(CASE WHEN oauth_provider = 'google' THEN 1 ELSE 0 END) as gmail,
  SUM(CASE WHEN oauth_provider = 'microsoft' THEN 1 ELSE 0 END) as outlook
FROM email_configurations;
```

---

## 🛡️ Sécurité

### ✅ Implémenté

- [x] Chiffrement AES-256-CBC + HMAC pour tous credentials
- [x] Master key depuis env (jamais en clair)
- [x] CSRF protection (state parameter OAuth)
- [x] Tokens jamais loggés
- [x] Multi-tenant isolation (WHERE customer_id = ?)
- [x] Rate limiting respecté
- [x] Input validation sur tous endpoints

### 🔒 Best Practices

- Ne **jamais** committer `.env`
- Utiliser **mots de passe d'application** si OAuth impossible
- Rotation **MAIL_CRYPTO_KEY** requiert re-chiffrement tokens
- Limiter scopes OAuth au **strict minimum**
- Monitoring alertes sur `error_count > 5`

---

## 📈 Performance

### Benchmarks attendus

- **OAuth flow**: < 2s (dépend réseau)
- **Token refresh**: < 500ms
- **Sync 50 emails Gmail API**: < 5s
- **Sync 50 emails IMAP**: < 10s (dépend provider)
- **Queue throughput**: 10-20 jobs/min par worker

### Optimisations

- Workers parallèles (supervisord numprocs=2)
- Redis persistent connections
- Limit `EMAIL_SYNC_LIMIT=50` par défaut
- Rate limiting avec window tracking

---

## 🐛 Troubleshooting Rapide

| Problème | Solution |
|----------|----------|
| "MAIL_CRYPTO_KEY not set" | `php scripts/generate-key.php` puis copier dans `.env` |
| "Failed to connect to Redis" | `brew services start redis` (macOS) ou `sudo systemctl start redis` (Linux) |
| "Invalid state parameter" | Session expirée → recommencer flow OAuth |
| Token expiré | Clic "Reconnecter" ou auto-refresh échoue → vérifier `last_error` |
| Worker ne démarre pas | `chmod +x workers/email-sync-worker.php` + vérifier logs |
| Emails non synchronisés | Vérifier: 1) Workers actifs 2) Queue Redis 3) `last_error` config |

---

## 📞 Support

**Documentation complète:** `EMAIL_OAUTH_SETUP.md`  
**Tests détaillés:** `EMAIL_TESTS_CHECKLIST.md`  
**Logs:** `logs/worker.log`, `logs/email.log`

---

## ✅ Critères d'Acceptation

| Critère | Status | Notes |
|---------|--------|-------|
| OAuth Gmail E2E | ✅ | Flow complet implémenté |
| OAuth Outlook E2E | ✅ | Flow complet implémenté |
| IMAP custom support | ✅ | Hostinger + custom |
| Chiffrement AES-256 | ✅ | + HMAC authentication |
| Auto-refresh tokens | ✅ | Avec buffer 5min |
| Workers background | ✅ | Redis queue + retry |
| Rate limiting | ✅ | Backoff exponentiel |
| Multi-tenant | ✅ | Isolation par customer_id |
| Documentation | ✅ | Setup + Tests + Troubleshooting |
| Installation auto | ✅ | Script bash complet |

---

## 🎁 Bonus Inclus

- ✅ Script génération clé chiffrement
- ✅ Config Supervisord production
- ✅ .gitignore sécurisé
- ✅ Loader .env automatique
- ✅ Tests unitaires chiffrement
- ✅ Dead letter queue pour jobs échoués
- ✅ Métriques stats queue Redis
- ✅ Graceful shutdown workers

---

## 📦 Résumé Technique

**18 fichiers livrés** comprenant:
- 4 classes PHP (Crypto, GoogleOAuth, MicrosoftOAuth, Queue)
- 4 endpoints OAuth (connect + callback × 2)
- 1 worker background avec support 3 providers
- 1 migration SQL complète
- 3 scripts utilitaires (install, generate-key, env loader)
- 3 fichiers config (supervisord, .env.example, .gitignore)
- 2 documentations complètes (setup + tests)

**Technologies:**
- PHP 8.1+ (openssl, pdo, curl, redis, imap)
- MySQL 5.7+ / 8.0+
- Redis 6.0+
- OAuth 2.0 (RFC 6749)
- AES-256-CBC + HMAC-SHA256

**Prêt pour production** ✅

---

**🚀 Prochaines étapes:**

1. Exécuter `./install-email-oauth.sh`
2. Configurer credentials OAuth dans `.env`
3. Démarrer workers: `php workers/email-sync-worker.php`
4. Tester avec checklist: `EMAIL_TESTS_CHECKLIST.md`
5. Déployer en production avec supervisord

---

**Bon déploiement ! 🎉**
