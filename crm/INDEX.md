# 📚 Documentation Index - Email Integration OAuth2

Bienvenue dans la documentation de l'intégration email OAuth2 !

---

## 🚀 Par où commencer ?

### Nouveau sur le projet ?
👉 **[QUICK_START.md](QUICK_START.md)** - Installation en 5 minutes

### Installation complète ?
👉 **[EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md)** - Guide détaillé configuration

### Développeur / Architecte ?
👉 **[ARCHITECTURE.md](ARCHITECTURE.md)** - Diagrammes et design patterns

### QA / Testeur ?
👉 **[EMAIL_TESTS_CHECKLIST.md](EMAIL_TESTS_CHECKLIST.md)** - Tests complets

### Chef de projet ?
👉 **[LIVRABLE_EMAIL_OAUTH.md](LIVRABLE_EMAIL_OAUTH.md)** - Vue d'ensemble livrables

---

## 📂 Organisation de la Documentation

```
docs/
├── QUICK_START.md              ⚡ Démarrage rapide (5 min)
├── EMAIL_OAUTH_SETUP.md        📖 Guide installation complet
├── ARCHITECTURE.md             🏗️ Architecture technique
├── EMAIL_TESTS_CHECKLIST.md    ✅ Checklist de tests
├── LIVRABLE_EMAIL_OAUTH.md     📦 Résumé livrables
└── INDEX.md                    📚 Ce fichier
```

---

## 🎯 Cas d'usage

### Je veux juste tester rapidement
1. Lire [QUICK_START.md](QUICK_START.md)
2. Exécuter `./install-email-oauth.sh`
3. Configurer credentials dans `.env`
4. Tester sur `email-settings.php`

### Je dois configurer OAuth Google
1. Section "OAuth Gmail" dans [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md#gmail-google-cloud-console)
2. Suivre étapes Google Cloud Console
3. Copier credentials dans `.env`

### Je dois configurer OAuth Microsoft
1. Section "OAuth Outlook" dans [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md#outlook-azure-portal)
2. Suivre étapes Azure Portal
3. Copier credentials dans `.env`

### Je veux comprendre l'architecture
1. Lire [ARCHITECTURE.md](ARCHITECTURE.md)
2. Voir diagrammes de flux OAuth
3. Comprendre worker architecture

### Je dois effectuer les tests
1. Ouvrir [EMAIL_TESTS_CHECKLIST.md](EMAIL_TESTS_CHECKLIST.md)
2. Cocher chaque test au fur et à mesure
3. Noter bugs trouvés dans tableau

### Je veux voir ce qui a été livré
1. Consulter [LIVRABLE_EMAIL_OAUTH.md](LIVRABLE_EMAIL_OAUTH.md)
2. Section "Fichiers Livrés" liste tout
3. Critères d'acceptation en bas

---

## 🔧 Commandes Rapides

```bash
# Installation
./install-email-oauth.sh

# Générer clé chiffrement
php scripts/generate-key.php

# Démarrer worker
php workers/email-sync-worker.php

# Vérifier queue Redis
redis-cli ZCARD email:sync:queue

# Voir logs
tail -f logs/worker.log

# Stats queue
php -r "require 'includes/EmailSyncQueue.php'; print_r((new EmailSyncQueue())->getStats());"

# Test chiffrement
php -r "require 'includes/EmailCrypto.php'; \$c = new EmailCrypto(); echo \$c->decrypt(\$c->encrypt('test'));"
```

---

## 📊 Fichiers Techniques Clés

| Fichier | Description | Doc |
|---------|-------------|-----|
| `includes/EmailCrypto.php` | Chiffrement AES-256 | [ARCHITECTURE.md](ARCHITECTURE.md#-chiffrement-des-tokens) |
| `includes/GoogleOAuth.php` | OAuth Google | [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md#gmail-google-cloud-console) |
| `includes/MicrosoftOAuth.php` | OAuth Microsoft | [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md#outlook-azure-portal) |
| `includes/EmailSyncQueue.php` | Queue Redis | [ARCHITECTURE.md](ARCHITECTURE.md#️-worker-architecture) |
| `workers/email-sync-worker.php` | Worker background | [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md#6-lancer-les-workers) |
| `database/migrations/001_add_oauth_support.sql` | Migration DB | [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md#3-migrations-db) |

---

## 🆘 Troubleshooting

| Problème | Solution | Doc Détaillée |
|----------|----------|---------------|
| "MAIL_CRYPTO_KEY not set" | `php scripts/generate-key.php` | [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md#troubleshooting-rapide) |
| "Redis connection failed" | `brew services start redis` | [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md#5-démarrer-redis) |
| Token expiré | Clic "Reconnecter" | [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md#reconnecter-si-token-expiré) |
| Worker ne démarre pas | `chmod +x workers/*.php` | [ARCHITECTURE.md](ARCHITECTURE.md#️-worker-architecture) |
| Emails non sync | Vérifier logs + queue | [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md#monitoring-production) |

---

## 📞 Support & Resources

**Documentation:**
- Quick Start: [QUICK_START.md](QUICK_START.md)
- Setup Complet: [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md)
- Architecture: [ARCHITECTURE.md](ARCHITECTURE.md)
- Tests: [EMAIL_TESTS_CHECKLIST.md](EMAIL_TESTS_CHECKLIST.md)
- Livrables: [LIVRABLE_EMAIL_OAUTH.md](LIVRABLE_EMAIL_OAUTH.md)

**APIs Externes:**
- [Gmail API Docs](https://developers.google.com/gmail/api)
- [Microsoft Graph Mail](https://learn.microsoft.com/en-us/graph/api/resources/mail-api-overview)
- [OAuth 2.0 Spec](https://oauth.net/2/)

**Logs:**
- Workers: `logs/worker.log`
- Email sync: `logs/email.log`
- PHP errors: `/Applications/MAMP/logs/php_error.log`

---

## ✅ Checklist Pré-Production

Avant de déployer en production :

- [ ] Migration SQL exécutée
- [ ] `.env` configuré avec toutes variables
- [ ] `MAIL_CRYPTO_KEY` généré et sécurisé
- [ ] OAuth Google credentials configurés
- [ ] OAuth Microsoft credentials configurés
- [ ] Redis installé et démarré
- [ ] Workers démarrés avec supervisord
- [ ] Tests OAuth Gmail passés (voir [EMAIL_TESTS_CHECKLIST.md](EMAIL_TESTS_CHECKLIST.md))
- [ ] Tests OAuth Outlook passés
- [ ] Tests IMAP custom passés
- [ ] Monitoring en place (logs, queue stats)
- [ ] Backups DB configurés
- [ ] `.env` exclu de Git (vérifier `.gitignore`)

---

## 🎓 Apprentissage

### Ordre de lecture recommandé

1. **Débutant** → [QUICK_START.md](QUICK_START.md)
2. **Utilisateur** → [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md)
3. **Développeur** → [ARCHITECTURE.md](ARCHITECTURE.md)
4. **QA** → [EMAIL_TESTS_CHECKLIST.md](EMAIL_TESTS_CHECKLIST.md)
5. **Manager** → [LIVRABLE_EMAIL_OAUTH.md](LIVRABLE_EMAIL_OAUTH.md)

### Concepts clés à maîtriser

- OAuth 2.0 Authorization Code Flow
- Chiffrement AES-256-CBC + HMAC
- Redis priority queues
- Background workers (PHP CLI)
- Token refresh automatique
- Rate limiting strategies
- Multi-tenant isolation

---

## 🔄 Mises à Jour

**Version actuelle:** 1.0.0

**Changelog:**
- ✅ OAuth Google complet
- ✅ OAuth Microsoft complet
- ✅ Chiffrement AES-256
- ✅ Workers Redis
- ✅ Rate limiting
- ✅ Documentation complète

**Roadmap future:**
- 🔜 Webhooks Gmail Pub/Sub
- 🔜 Webhooks Microsoft Graph
- 🔜 AI email classification
- 🔜 Email sending

---

**Bonne lecture ! 📖**

*Pour toute question, consultez d'abord la section troubleshooting dans [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md)*
