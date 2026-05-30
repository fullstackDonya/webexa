# ⚡ Quick Start - 5 Minutes

## 🎯 Installation Express

```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm
./install-email-oauth.sh
```

## 🔑 Générer la clé de chiffrement

```bash
php scripts/generate-key.php
# Copier la ligne MAIL_CRYPTO_KEY=... dans .env
```

## ⚙️ Configurer OAuth (Gmail)

1. **Google Cloud Console:** https://console.cloud.google.com
2. Créer projet → Activer Gmail API
3. OAuth Consent Screen → External
4. Credentials → OAuth 2.0 Client ID
5. Redirect: `http://localhost/crm/oauth/google/callback`
6. Copier dans `.env`:
   ```
   GOOGLE_CLIENT_ID=xxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=GOCSPX-xxx
   ```

## ⚙️ Configurer OAuth (Outlook)

1. **Azure Portal:** https://portal.azure.com
2. App registrations → New → Multitenant
3. API permissions: Mail.Read, Mail.Send, offline_access
4. Certificates & secrets → New secret
5. Redirect: `http://localhost/crm/oauth/microsoft/callback`
6. Copier dans `.env`:
   ```
   MICROSOFT_CLIENT_ID=uuid
   MICROSOFT_CLIENT_SECRET=secret
   ```

## 🚀 Démarrer Redis + Worker

```bash
# Redis
brew services start redis

# Worker (terminal dédié)
php workers/email-sync-worker.php
```

## ✅ Tester

1. Aller: `http://localhost/crm/email-settings.php`
2. Clic **"Connecter Gmail"**
3. Autoriser
4. ✅ Compte connecté !

---

📚 **Doc complète:** [EMAIL_OAUTH_SETUP.md](EMAIL_OAUTH_SETUP.md)  
✅ **Tests:** [EMAIL_TESTS_CHECKLIST.md](EMAIL_TESTS_CHECKLIST.md)  
📦 **Livrable:** [LIVRABLE_EMAIL_OAUTH.md](LIVRABLE_EMAIL_OAUTH.md)
