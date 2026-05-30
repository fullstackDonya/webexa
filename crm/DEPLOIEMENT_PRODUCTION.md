# 🚀 Déploiement Production - Correction Synchronisation Email

## 🔴 Problème Identifié

L'erreur "Unexpected end of JSON input" et le code 500 sur `api/email-sync.php` sont causés par **l'absence du fichier `.env` sur le serveur de production**.

## ✅ Solution en 3 étapes

### Étape 1 : Vérifier la configuration actuelle

Accédez à ce script de diagnostic :
```
https://webitech.fr/crm/check-env.php
```

Ce script affichera :
- ✅/❌ Présence du fichier .env
- ✅/❌ Variables d'environnement chargées
- ✅/❌ Tables de base de données
- ✅/❌ Classes PHP requises

### Étape 2 : Déployer le fichier .env

#### Option A : Via FTP/SFTP (Recommandé)

1. **Connectez-vous à votre serveur** via FileZilla ou WinSCP
2. **Naviguez vers** : `/public_html/crm/` (ou le dossier où se trouve votre CRM)
3. **Uploadez le fichier** `.env` depuis votre version locale
4. **Vérifiez les permissions** : `chmod 644 .env` (lisible par PHP mais pas accessible via web)

#### Option B : Via cPanel File Manager

1. Connectez-vous à **cPanel**
2. Ouvrez **File Manager**
3. Naviguez vers le dossier `/public_html/crm/`
4. Cliquez sur **Upload** et uploadez `.env`
5. Clic droit sur `.env` → **Change Permissions** → `644`

#### Option C : Via SSH (si disponible)

```bash
# Connexion SSH
ssh votreuser@webitech.fr

# Naviguer vers le dossier CRM
cd /home/votreuser/public_html/crm/

# Créer le fichier .env
nano .env
```

Copiez-collez le contenu suivant :

```bash
# Clé de chiffrement - CRITIQUE
MAIL_CRYPTO_KEY=lRPNfnE8UDIqnJDMIKUfxsJgwNcNT7t5AU1TpfDuwOo=

# Database (déjà configuré probablement)
DB_HOST=localhost
DB_NAME=u800069496_webitech
DB_USER=u800069496_admin
DB_PASS=Donyadev2024.

# Google OAuth
GOOGLE_CLIENT_ID=540482971416-5ugc4j990h3e53kqrla2j6k12ql13p7o.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=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
GOOGLE_REDIRECT_URI=https://webitech.fr/crm/oauth/google/callback.php
GOOGLE_SCOPES=openid email profile https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/gmail.modify

# Microsoft OAuth (si utilisé)
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_REDIRECT_URI=https://webitech.fr/crm/oauth/microsoft/callback
MICROSOFT_TENANT=common
```

Sauvegarder (CTRL+X, Y, Enter) puis :

```bash
# Sécuriser les permissions
chmod 644 .env

# Vérifier
cat .env | grep MAIL_CRYPTO_KEY
```

### Étape 3 : Protection du fichier .env

**CRITIQUE** : Empêcher l'accès direct via navigateur

Ajoutez dans `/crm/.htaccess` :

```apache
# Bloquer l'accès au .env
<Files ".env">
    Require all denied
</Files>

# Alternative
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>
```

## 🧪 Tests après déploiement

### 1. Vérifier le diagnostic
```
https://webitech.fr/crm/check-env.php
```

Vous devriez voir :
- ✅ Fichier .env trouvé
- ✅ MAIL_CRYPTO_KEY définie (valide, 32 bytes)
- ✅ Toutes les tables existent
- ✅ Chiffrement/déchiffrement fonctionne

### 2. Tester la synchronisation

Dans `email-settings.php`, cliquez sur **Synchroniser** pour un compte configuré.

Ouvrez la console navigateur (F12) → Onglet Network :
- L'appel à `/api/email-sync.php` doit retourner **200 OK**
- La réponse doit être un JSON valide :
```json
{
  "success": true,
  "message": "Synchronisation terminée",
  "stats": {
    "emails_new": 5,
    "emails_synced": 5
  }
}
```

### 3. Vérifier l'inbox

```
https://webitech.fr/crm/email-inbox.php
```

Vous devriez voir les emails synchronisés.

## 🔍 Dépannage

### Erreur persiste après .env ?

1. **Vérifier les logs PHP** :
   - cPanel → Errors
   - `/var/log/php_errors.log`

2. **Vider le cache PHP** (si opcache activé) :
   ```bash
   # Via SSH
   php -r "opcache_reset();"
   
   # Ou redémarrer PHP-FPM
   sudo systemctl restart php-fpm
   ```

3. **Tester EmailCrypto manuellement** :
   
   Créez `/crm/test-crypto.php` :
   ```php
   <?php
   require_once __DIR__ . '/includes/env.php';
   require_once __DIR__ . '/includes/EmailCrypto.php';
   
   header('Content-Type: application/json');
   
   try {
       $crypto = new EmailCrypto();
       $test = "secret-password";
       $encrypted = $crypto->encrypt($test);
       $decrypted = $crypto->decrypt($encrypted);
       
       echo json_encode([
           'success' => $test === $decrypted,
           'key_loaded' => !empty(getenv('MAIL_CRYPTO_KEY')),
           'message' => $test === $decrypted ? 'OK' : 'KO'
       ]);
   } catch (Exception $e) {
       echo json_encode([
           'success' => false,
           'error' => $e->getMessage()
       ]);
   }
   ```
   
   Accédez à `https://webitech.fr/crm/test-crypto.php`

4. **Régénérer la clé** (si nécessaire) :
   ```bash
   # Générer nouvelle clé
   php -r "echo 'MAIL_CRYPTO_KEY=' . base64_encode(random_bytes(32)) . PHP_EOL;"
   ```
   
   ⚠️ **ATTENTION** : Si vous changez la clé, vous devrez reconfigurer tous les comptes email.

### Impossible d'uploader .env ?

Créez-le directement via cPanel File Manager :
1. File Manager → Dossier `/crm/`
2. **+ File** → Nommer `.env`
3. Clic droit → **Edit**
4. Coller le contenu
5. **Save**

## 📋 Checklist finale

- [ ] Fichier `.env` présent sur le serveur
- [ ] `MAIL_CRYPTO_KEY` définie (32 bytes base64)
- [ ] Permissions `.env` = 644
- [ ] `.htaccess` protège `.env`
- [ ] `check-env.php` affiche tout en vert
- [ ] `api/email-sync.php` retourne 200 + JSON
- [ ] Emails visibles dans `email-inbox.php`
- [ ] Supprimez `check-env.php` et `test-crypto.php` après validation

## 🔒 Sécurité post-déploiement

```bash
# Supprimer les scripts de diagnostic
rm /crm/check-env.php
rm /crm/test-crypto.php  # si créé

# Vérifier que .env n'est pas accessible
curl https://webitech.fr/crm/.env
# Doit retourner 403 Forbidden
```

## 📞 Support

Si le problème persiste après ces étapes :
1. Vérifiez les logs PHP du serveur
2. Assurez-vous que l'extension `openssl` est activée
3. Vérifiez que `allow_url_fopen` est à `On`
4. Contactez votre hébergeur pour vérifier la configuration PHP
