# 📧 CRM - Email Integration avec OAuth2

Intégration email sécurisée multi-tenant pour Gmail, Outlook/Microsoft 365 et providers IMAP/SMTP personnalisés.

## ✨ Fonctionnalités

- ✅ **OAuth2 pour Gmail** (Google API)
- ✅ **OAuth2 pour Outlook** (Microsoft Graph)
- ✅ **IMAP/SMTP pour providers custom** (Hostinger, cPanel, etc.)
- ✅ **Chiffrement AES-256-CBC + HMAC** pour credentials
- ✅ **Workers background** avec Redis pour synchronisation
- ✅ **Auto-refresh des tokens** OAuth
- ✅ **Rate limiting** et backoff exponentiel
- ✅ **Webhooks** (Gmail Pub/Sub + Microsoft Graph notifications)
- ✅ **Multi-tenant** avec isolation par customer_id

---

## 🚀 Installation Rapide

### 1. Prérequis

```bash
# PHP 8.1+ avec extensions
php -m | grep -E "openssl|pdo|curl|redis|imap"

# Redis pour queue
brew install redis  # macOS
# ou: apt-get install redis-server  # Linux

# Supervisord pour workers (optionnel mais recommandé)
brew install supervisor  # macOS
# ou: apt-get install supervisor  # Linux
```

### 2. Configuration

```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé de chiffrement
php -r "echo 'MAIL_CRYPTO_KEY=' . base64_encode(random_bytes(32)) . PHP_EOL;" >> .env

# Éditer .env et remplir les credentials OAuth
nano .env
```

### 3. Migrations DB

```bash
# Exécuter la migration OAuth
mysql -u root webitech < database/migrations/001_add_oauth_support.sql

# Vérifier
mysql -u root webitech -e "DESCRIBE email_configurations;"
```

### 4. Configurer OAuth

#### **Gmail (Google Cloud Console)**

1. Allez sur [console.cloud.google.com](https://console.cloud.google.com)
2. Créez un nouveau projet ou sélectionnez-en un
3. Activez **Gmail API**
4. Configurez l'**OAuth consent screen**
   - Type: External (ou Internal si GSuite)
   - Nom de l'app: "CRM Email Integration"
   - Email support: votre email
   - Developer contact: votre email
   - Scopes: ajoutez les scopes Gmail nécessaires
5. Créez des **credentials OAuth 2.0**
   - Type: Application Web
   - Redirect URI: `http://localhost/crm/oauth/google/callback` (ajustez selon votre URL)
6. Copiez **Client ID** et **Client Secret** dans `.env`

```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://localhost/crm/oauth/google/callback
```

#### **Outlook (Azure Portal)**

1. Allez sur [portal.azure.com](https://portal.azure.com)
2. **Azure Active Directory** → **App registrations** → **New registration**
3. Nom: "CRM Email Integration"
4. Supported account types: **Multitenant** (ou Single tenant)
5. Redirect URI: `http://localhost/crm/oauth/microsoft/callback`
6. Après création:
   - **Overview**: copiez **Application (client) ID**
   - **Certificates & secrets**: créez un **Client secret**
   - **API permissions**: ajoutez `Mail.Read`, `Mail.Send`, `offline_access`
   - **Grant admin consent** si requis
7. Copiez dans `.env`

```env
MICROSOFT_CLIENT_ID=your-application-id
MICROSOFT_CLIENT_SECRET=your-client-secret
MICROSOFT_REDIRECT_URI=http://localhost/crm/oauth/microsoft/callback
MICROSOFT_TENANT=common
```

### 5. Démarrer Redis

```bash
# macOS
brew services start redis

# Linux
sudo systemctl start redis

# Vérifier
redis-cli ping
# PONG
```

### 6. Lancer les Workers

#### Option A: Supervisord (Production recommandée)

```bash
# Copier la config
sudo cp config/supervisord.conf /etc/supervisor/conf.d/crm-email-workers.conf

# Recharger supervisord
sudo supervisorctl reread
sudo supervisorctl update

# Démarrer
sudo supervisorctl start crm-workers:*

# Vérifier le statut
sudo supervisorctl status
```

#### Option B: Manuellement (Développement)

```bash
# Terminal 1
php workers/email-sync-worker.php

# Terminal 2 (optionnel - worker 2)
php workers/email-sync-worker.php
```

---

## 📖 Utilisation

### Connecter un compte Gmail

1. Allez sur **CRM → Paramètres Email**
2. Cliquez sur **"Connecter Gmail"**
3. Authentifiez-vous avec Google
4. Autorisez les permissions
5. ✅ Redirection automatique vers le CRM

### Connecter un compte Outlook

1. Allez sur **CRM → Paramètres Email**
2. Cliquez sur **"Connecter Outlook"**
3. Authentifiez-vous avec Microsoft
4. Autorisez les permissions
5. ✅ Redirection automatique vers le CRM

### Ajouter un compte IMAP/SMTP custom

1. Cliquez sur **"IMAP/SMTP Manuel"**
2. Sélectionnez le provider (Hostinger, Custom, etc.)
3. Entrez vos credentials
4. Pour Gmail/Outlook sans OAuth: utilisez un **mot de passe d'application**

### Synchroniser les emails

#### Automatique (recommandé)

La synchronisation se fait automatiquement toutes les 5 minutes via les workers.

#### Manuelle

Cliquez sur **"Synchroniser"** sur la carte du compte.

---

## 🔧 API Endpoints

### OAuth Google

```
GET  /oauth/google/connect.php     - Initie le flow OAuth
GET  /oauth/google/callback.php    - Callback après autorisation
```

### OAuth Microsoft

```
GET  /oauth/microsoft/connect.php  - Initie le flow OAuth
GET  /oauth/microsoft/callback.php - Callback après autorisation
```

### Email Integration API

```
POST /api/email-integration.php
  - action: sync       → Ajouter sync job à la queue
  - action: delete     → Supprimer configuration
  - config_id: int     → ID configuration
```

---

## 🛡️ Sécurité

### Chiffrement

Tous les tokens OAuth et mots de passe sont chiffrés avec:
- **AES-256-CBC** pour confidentialité
- **HMAC-SHA256** pour intégrité
- **Master key** stockée dans variable d'environnement

```php
// Exemple d'utilisation
$crypto = new EmailCrypto();
$encrypted = $crypto->encrypt('mon-token-secret');
$decrypted = $crypto->decrypt($encrypted);
```

### Protection CSRF

Tous les flows OAuth utilisent un **state parameter** unique stocké en session.

### Rate Limiting

- Gmail API: max 60 requêtes/minute par défaut
- Microsoft Graph: max 60 requêtes/minute par défaut
- Backoff exponentiel en cas d'erreur (1min → 5min → 15min)

---

## ⚙️ Workers & Queue

### Architecture

```
Web Request → Redis Queue → Worker Pool → Gmail/Outlook/IMAP → Database
```

### Commandes

```bash
# Voir stats queue
redis-cli
> ZCARD email:sync:queue          # Jobs en attente
> KEYS email:sync:processing:*    # Jobs en cours
> ZCARD email:sync:failed         # Jobs échoués

# Vider la queue (⚠️ DANGER)
php -r "require 'includes/EmailSyncQueue.php'; (new EmailSyncQueue())->clear();"
```

### Logs

```bash
# Worker logs
tail -f logs/worker.log

# Email sync logs
tail -f logs/email.log

# PHP errors
tail -f /Applications/MAMP/logs/php_error.log
```

---

## 🔍 Troubleshooting

### Erreur: "MAIL_CRYPTO_KEY not set"

```bash
# Générer une nouvelle clé
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"

# Ajouter dans .env
MAIL_CRYPTO_KEY=votre-clé-générée
```

### Erreur: "Failed to connect to Redis"

```bash
# Vérifier que Redis tourne
redis-cli ping

# Démarrer Redis
brew services start redis
# ou: sudo systemctl start redis
```

### Token OAuth expiré

1. Allez sur la configuration du compte
2. Cliquez **"Reconnecter"**
3. Réautorisez l'application

### Emails ne se synchronisent pas

```bash
# Vérifier les workers
sudo supervisorctl status

# Vérifier la queue
redis-cli ZCARD email:sync:queue

# Vérifier les logs
tail -f logs/worker.log

# Ajouter manuellement un job
php -r "
require 'includes/EmailSyncQueue.php';
\$queue = new EmailSyncQueue();
\$queue->enqueue(1, ['priority' => 'high']);
"
```

---

## 📊 Monitoring

### Métriques à surveiller

- **Queue size**: `ZCARD email:sync:queue`
- **Processing jobs**: `KEYS email:sync:processing:*`
- **Failed jobs**: `ZCARD email:sync:failed`
- **Error count par config**: `SELECT id, email, error_count FROM email_configurations WHERE error_count > 3`

### Alertes recommandées

```sql
-- Comptes avec erreurs répétées
SELECT id, email, last_error, error_count 
FROM email_configurations 
WHERE error_count > 5;

-- Tokens expirés
SELECT id, email, oauth_token_expires_at 
FROM email_configurations 
WHERE oauth_provider != 'none' 
  AND oauth_token_expires_at < NOW();
```

---

## 🧪 Tests

### Checklist de tests manuels

#### ✅ OAuth Gmail
- [ ] Flow complet de connexion
- [ ] Refresh automatique du token
- [ ] Synchronisation emails
- [ ] Revocation et suppression

#### ✅ OAuth Outlook
- [ ] Flow complet de connexion
- [ ] Refresh automatique du token
- [ ] Synchronisation emails
- [ ] Revocation et suppression

#### ✅ IMAP/SMTP
- [ ] Connexion Hostinger
- [ ] Connexion custom IMAP
- [ ] Synchronisation emails
- [ ] Gestion erreurs auth

#### ✅ Workers
- [ ] Job queue fonctionne
- [ ] Retry avec backoff
- [ ] Dead letter queue
- [ ] Graceful shutdown

---

## 📝 Variables d'Environnement

| Variable | Description | Exemple |
|----------|-------------|---------|
| `MAIL_CRYPTO_KEY` | ⚠️ **Requis** - Clé chiffrement 256-bit | `base64_encoded_key` |
| `GOOGLE_CLIENT_ID` | Client ID Google OAuth | `xxx.apps.googleusercontent.com` |
| `GOOGLE_CLIENT_SECRET` | Secret Google OAuth | `GOCSPX-xxx` |
| `MICROSOFT_CLIENT_ID` | App ID Microsoft Azure | `uuid` |
| `MICROSOFT_CLIENT_SECRET` | Secret Microsoft Azure | `xxx` |
| `REDIS_HOST` | Hôte Redis | `127.0.0.1` |
| `REDIS_PORT` | Port Redis | `6379` |
| `EMAIL_SYNC_INTERVAL` | Intervalle sync (secondes) | `300` |
| `EMAIL_SYNC_LIMIT` | Max emails par sync | `50` |

---

## 🎯 Roadmap

- [ ] Webhooks Gmail (Pub/Sub push notifications)
- [ ] Webhooks Microsoft (Change Notifications)
- [ ] Support AWS SES / SendGrid pour envoi
- [ ] Dashboard analytics emails
- [ ] AI classification emails → leads
- [ ] Réponses automatiques (templates)
- [ ] Attachments sync

---

## 📚 Documentation Complémentaire

- [Gmail API](https://developers.google.com/gmail/api)
- [Microsoft Graph Mail API](https://learn.microsoft.com/en-us/graph/api/resources/mail-api-overview)
- [OAuth 2.0 RFC](https://oauth.net/2/)
- [Redis Queue Patterns](https://redis.io/docs/manual/patterns/distributed-locks/)

---

## 🆘 Support

**Problèmes connus**: Voir [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)  
**Issues**: GitHub Issues  
**Email**: support@votre-domaine.com

---

## 📜 Licence

Proprietary - Usage interne uniquement
