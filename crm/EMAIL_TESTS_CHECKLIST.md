# ✅ Checklist de Tests - Email Integration OAuth2

## 📋 Pré-requis

- [ ] MySQL base de données avec migration `001_add_oauth_support.sql` exécutée
- [ ] Redis installé et démarré (`redis-cli ping` = PONG)
- [ ] Fichier `.env` configuré avec `MAIL_CRYPTO_KEY` généré
- [ ] Credentials OAuth Google configurés dans Google Cloud Console
- [ ] Credentials OAuth Microsoft configurés dans Azure Portal
- [ ] Workers démarrés (supervisord ou manuellement)

---

## 🧪 Tests Gmail OAuth

### 1. Configuration OAuth Google

- [ ] **Google Cloud Console** → Projet créé
- [ ] **Gmail API** activée
- [ ] **OAuth Consent Screen** configuré (avec privacy policy URL)
- [ ] **Credentials** créés (type Web Application)
- [ ] **Redirect URI** ajouté: `http://localhost/crm/oauth/google/callback`
- [ ] **Scopes** ajoutés: `gmail.readonly`, `gmail.send`, `gmail.modify`
- [ ] Variables `.env` remplies:
  ```
  GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
  GOOGLE_CLIENT_SECRET=GOCSPX-xxx
  GOOGLE_REDIRECT_URI=http://localhost/crm/oauth/google/callback
  ```

### 2. Flow de Connexion

- [ ] Clic sur bouton **"Connecter Gmail"** sur `email-settings.php`
- [ ] Redirection vers page de consentement Google
- [ ] Sélection du compte Gmail
- [ ] Acceptation des permissions demandées
- [ ] Redirection vers callback CRM
- [ ] Message de succès affiché: "Gmail account connected successfully"
- [ ] Configuration apparaît dans la liste avec:
  - [ ] Icône Gmail (fab fa-google)
  - [ ] Email affiché correctement
  - [ ] Badge "Actif" vert
  - [ ] Méthode: "🔐 OAuth2"
  - [ ] Token: Badge vert "OK"

### 3. Vérification Base de Données

```sql
SELECT id, email, provider, oauth_provider, connection_method, 
       oauth_token_expires_at, is_active 
FROM email_configurations 
WHERE provider = 'gmail' 
ORDER BY id DESC LIMIT 1;
```

**Attendu:**
- [ ] `oauth_provider` = `'google'`
- [ ] `connection_method` = `'oauth'`
- [ ] `oauth_access_token` et `oauth_refresh_token` sont non NULL (chiffrés)
- [ ] `oauth_token_expires_at` est dans le futur (~1h)
- [ ] `is_active` = `1`

### 4. Synchronisation Emails

**Option A: Manuelle**
- [ ] Clic sur bouton **"Synchroniser"** sur la carte Gmail
- [ ] Vérifier logs worker: `tail -f logs/worker.log`
- [ ] Job ajouté à la queue Redis: `redis-cli ZCARD email:sync:queue`
- [ ] Worker traite le job (voir logs)
- [ ] Emails importés dans table `emails`:

```sql
SELECT COUNT(*) as email_count 
FROM emails 
WHERE config_id = (SELECT id FROM email_configurations WHERE provider = 'gmail' LIMIT 1);
```

**Option B: Via Worker (automatique)**
- [ ] Ajouter job manuellement:
  ```php
  php -r "
  require 'includes/EmailSyncQueue.php';
  \$queue = new EmailSyncQueue();
  \$queue->enqueue(1, ['priority' => 'high']); // 1 = config_id Gmail
  "
  ```
- [ ] Vérifier queue: `redis-cli ZRANGE email:sync:queue 0 -1`
- [ ] Worker traite dans les 5 secondes
- [ ] Logs montrent fetch réussi

### 5. Refresh Token

- [ ] Modifier manuellement `oauth_token_expires_at` dans le passé:
  ```sql
  UPDATE email_configurations 
  SET oauth_token_expires_at = DATE_SUB(NOW(), INTERVAL 1 HOUR) 
  WHERE provider = 'gmail' LIMIT 1;
  ```
- [ ] Relancer sync
- [ ] Vérifier logs: doit voir "Refreshing token..."
- [ ] Nouveau token obtenu et sauvegardé
- [ ] `oauth_token_expires_at` mis à jour dans le futur

### 6. Erreurs & Reconnexion

- [ ] Revoquer accès manuellement sur [myaccount.google.com/permissions](https://myaccount.google.com/permissions)
- [ ] Tenter sync
- [ ] `last_error` rempli dans DB
- [ ] `error_count` incrémenté
- [ ] Badge "Expiré" rouge affiché sur UI
- [ ] Bouton **"Reconnecter"** apparaît
- [ ] Clic sur "Reconnecter" → nouveau flow OAuth
- [ ] Après reconnexion: erreurs effacées

---

## 🧪 Tests Outlook OAuth

### 1. Configuration OAuth Microsoft

- [ ] **Azure Portal** → App registration créée
- [ ] **API Permissions** ajoutées:
  - [ ] `Mail.Read`
  - [ ] `Mail.Send`
  - [ ] `Mail.ReadWrite`
  - [ ] `offline_access`
- [ ] **Admin consent** accordé (si requis)
- [ ] **Client secret** créé
- [ ] **Redirect URI** ajouté: `http://localhost/crm/oauth/microsoft/callback`
- [ ] Variables `.env` remplies:
  ```
  MICROSOFT_CLIENT_ID=uuid-here
  MICROSOFT_CLIENT_SECRET=secret-here
  MICROSOFT_REDIRECT_URI=http://localhost/crm/oauth/microsoft/callback
  MICROSOFT_TENANT=common
  ```

### 2. Flow de Connexion

- [ ] Clic sur **"Connecter Outlook"**
- [ ] Redirection vers Microsoft login
- [ ] Authentification réussie
- [ ] Permissions acceptées
- [ ] Redirection callback
- [ ] Message succès: "Outlook account connected successfully"
- [ ] Configuration visible avec:
  - [ ] Icône Microsoft (fab fa-microsoft)
  - [ ] Email Outlook/Office 365
  - [ ] Méthode: "🔐 OAuth2"

### 3. Vérification Base de Données

```sql
SELECT id, email, provider, oauth_provider, connection_method 
FROM email_configurations 
WHERE provider = 'outlook' 
ORDER BY id DESC LIMIT 1;
```

**Attendu:**
- [ ] `oauth_provider` = `'microsoft'`
- [ ] Tokens chiffrés présents
- [ ] Expiration correcte

### 4. Synchronisation Emails

- [ ] Sync manuelle fonctionne
- [ ] Emails Microsoft Graph récupérés
- [ ] Stockés dans table `emails` avec bon `config_id`

### 5. Token Refresh

- [ ] Expirer token manuellement
- [ ] Sync déclenche refresh automatique
- [ ] Nouveau `refresh_token` peut être retourné par Microsoft (remplace l'ancien)

---

## 🧪 Tests IMAP/SMTP Custom

### 1. Hostinger

- [ ] Clic sur **"IMAP/SMTP Manuel"**
- [ ] Sélection provider "Hostinger"
- [ ] Serveurs auto-remplis:
  - IMAP: `imap.hostinger.com:993`
  - SMTP: `smtp.hostinger.com:587`
- [ ] Email et password remplis
- [ ] Connexion réussie
- [ ] Mot de passe chiffré stocké (vérifier que ce n'est pas en clair)

### 2. Custom IMAP

- [ ] Provider: "Personnalisé (IMAP/SMTP)"
- [ ] Saisie manuelle serveurs
- [ ] Test connexion IMAP
- [ ] Sync fonctionne

---

## 🧪 Tests Workers & Queue

### 1. Queue Redis

**Stats**
```bash
redis-cli
> ZCARD email:sync:queue         # Pending jobs
> KEYS email:sync:processing:*   # Currently processing
> ZCARD email:sync:failed        # Failed/dead letter
```

- [ ] Job ajouté visible dans queue
- [ ] Worker consomme job
- [ ] Job disparaît de queue après traitement

### 2. Retry & Backoff

- [ ] Provoquer erreur (mauvais credentials)
- [ ] Job échoue
- [ ] `retry_count` incrémenté
- [ ] Job re-schedulé avec backoff (1min, 5min, 15min)
- [ ] Après 3 échecs: job dans dead letter queue

### 3. Worker Graceful Shutdown

- [ ] Worker en cours de traitement
- [ ] Envoyer SIGTERM: `kill -TERM <pid>`
- [ ] Worker finit job en cours
- [ ] Worker s'arrête proprement
- [ ] Message "shutting down" dans logs

### 4. Supervisord

```bash
sudo supervisorctl status
```

- [ ] Workers démarrés automatiquement
- [ ] 2 processus actifs (numprocs=2)
- [ ] Auto-restart en cas de crash
- [ ] Logs rotatifs (10MB max)

---

## 🧪 Tests Sécurité

### 1. Chiffrement

**Test unitaire**
```php
php -r "
require 'includes/EmailCrypto.php';
\$crypto = new EmailCrypto();
\$plaintext = 'mon-token-secret-12345';
\$encrypted = \$crypto->encrypt(\$plaintext);
echo 'Encrypted: ' . substr(\$encrypted, 0, 50) . '...' . PHP_EOL;
\$decrypted = \$crypto->decrypt(\$encrypted);
echo 'Decrypted: ' . \$decrypted . PHP_EOL;
assert(\$plaintext === \$decrypted, 'Encryption/Decryption failed');
echo 'OK' . PHP_EOL;
"
```

- [ ] Chiffrement/déchiffrement OK
- [ ] Pas de token en clair dans logs
- [ ] Pas de token en clair dans base de données

### 2. CSRF Protection

- [ ] Tenter callback OAuth sans state
- [ ] Erreur: "Invalid state parameter"
- [ ] Tenter avec state modifié
- [ ] Erreur: "CSRF attack"

### 3. Multi-tenant Isolation

- [ ] Créer 2 customers différents
- [ ] Connecter Gmail pour customer 1
- [ ] Se connecter en tant que customer 2
- [ ] Vérifier que config Gmail customer 1 n'apparaît pas
- [ ] Tenter API avec config_id de customer 1
- [ ] Accès refusé (implémenter ACL si pas fait)

---

## 🧪 Tests Performance

### 1. Sync 100+ Emails

- [ ] Compte avec >100 emails non lus
- [ ] Sync complète en <30s
- [ ] Pas de timeout
- [ ] Limite `EMAIL_SYNC_LIMIT` respectée

### 2. Rate Limiting

- [ ] Lancer 10 syncs simultanées
- [ ] Vérifier rate limit respecté (60/min)
- [ ] Pas de HTTP 429 (Too Many Requests)
- [ ] Si 429: backoff appliqué

---

## ✅ Checklist Finale

### Configuration
- [x] Migration SQL exécutée
- [x] `.env` configuré
- [x] Redis démarré
- [x] Workers démarrés

### OAuth Google
- [ ] Flow connexion OK
- [ ] Sync emails OK
- [ ] Refresh token OK
- [ ] Reconnexion OK

### OAuth Microsoft
- [ ] Flow connexion OK
- [ ] Sync emails OK
- [ ] Refresh token OK

### IMAP Custom
- [ ] Connexion Hostinger OK
- [ ] Sync IMAP OK

### Workers
- [ ] Queue Redis OK
- [ ] Job processing OK
- [ ] Retry/backoff OK
- [ ] Supervisord OK

### Sécurité
- [ ] Chiffrement OK
- [ ] CSRF protection OK
- [ ] Multi-tenant isolation OK

### Production Readiness
- [ ] Logs configurés
- [ ] Monitoring en place
- [ ] Alertes définies
- [ ] Documentation à jour

---

## 📊 Critères d'Acceptation

| Critère | Status | Notes |
|---------|--------|-------|
| OAuth Gmail fonctionne E2E | ⬜ | |
| OAuth Outlook fonctionne E2E | ⬜ | |
| IMAP custom fonctionne | ⬜ | |
| Tokens chiffrés (jamais en clair) | ⬜ | |
| Auto-refresh tokens OAuth | ⬜ | |
| Workers consomment queue | ⬜ | |
| Retry avec backoff | ⬜ | |
| Rate limiting respecté | ⬜ | |
| Multi-tenant isolé | ⬜ | |
| Logs exploitables | ⬜ | |

---

## 🐛 Bugs Trouvés

| ID | Description | Sévérité | Status | Fix |
|----|-------------|----------|--------|-----|
| 1  |             |          |        |     |

---

**Date des tests:** _______________  
**Testeur:** _______________  
**Version:** 1.0.0
