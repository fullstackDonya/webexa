# 🔧 Guide de Résolution des Problèmes Email

## Problème : Synchronisation retourne 200 mais 0 emails

### 🔍 Diagnostic

Votre configuration email pour `contact@webitech.fr` est **incomplète** :

❌ **Serveur IMAP vide** - Le champ `imap_server` n'est pas rempli  
❌ **Serveur SMTP vide** - Le champ `smtp_host` n'est pas rempli

### ✅ Solution en 3 étapes

#### 1️⃣ Accéder à la page de diagnostic
```
https://webitech.fr/crm/email-diagnostic.php
```

Vous verrez les problèmes exacts de votre configuration.

#### 2️⃣ Cliquer sur "Réparer la configuration"
Ou accédez directement à :
```
https://webitech.fr/crm/email-fix-config.php
```

#### 3️⃣ Choisir votre fournisseur

**Pour Gmail :**
- Serveur IMAP : `imap.gmail.com:993`
- Serveur SMTP : `smtp.gmail.com:587`
- ⚠️ Utilisez un "Mot de passe d'application" (pas votre mot de passe normal)

**Pour Outlook/Office365 :**
- Serveur IMAP : `outlook.office365.com:993`
- Serveur SMTP : `smtp.office365.com:587`

**Pour OVH :**
- Serveur IMAP : `ssl0.ovh.net:993`
- Serveur SMTP : `ssl0.ovh.net:587`

**Pour Webitech :**
- Serveur IMAP : `mail.webitech.fr:993`
- Serveur SMTP : `mail.webitech.fr:587`

### 📝 Ou correction manuelle via SQL

Si vous préférez corriger directement en base de données :

```sql
-- Voir le fix-email-config.sql
-- Remplacez les valeurs puis exécutez :

UPDATE email_configurations 
SET 
    imap_server = 'mail.webitech.fr',  -- Votre serveur IMAP
    imap_port = 993,
    smtp_host = 'mail.webitech.fr',    -- Votre serveur SMTP
    smtp_port = 587,
    provider = 'custom'
WHERE email = 'contact@webitech.fr' AND customer_id = 2;
```

### 🎯 Vérification post-configuration

Après avoir mis à jour :

1. Rafraîchir le diagnostic : `email-diagnostic.php`
2. Vérifier que les serveurs sont maintenant affichés
3. Cliquer sur "Tester la connexion IMAP"
4. Si le test réussit, aller dans `email-inbox.php` et cliquer "Synchroniser"

### 🚨 Notes importantes

**Pour Gmail :**
- Activez l'accès IMAP dans Gmail (Paramètres > Transfert et POP/IMAP)
- Créez un "Mot de passe d'application" sur votre compte Google
- N'utilisez PAS votre mot de passe normal

**Pour Outlook :**
- Activez l'accès IMAP dans les paramètres Outlook
- Si 2FA est activé, créez un mot de passe d'application

**Sécurité :**
- Les mots de passe sont chiffrés dans la base de données
- Ne partagez jamais vos identifiants email

### 📚 Fichiers créés pour vous aider

- `email-diagnostic.php` - Diagnostic complet de la configuration
- `email-fix-config.php` - Interface de réparation rapide
- `fix-email-config.sql` - Script SQL de réparation
- `setup-smtp-test.sql` - Exemple de configuration SMTP complète

### ❓ Besoin d'aide ?

Si après ces étapes la synchronisation ne fonctionne toujours pas :

1. Vérifiez les logs PHP dans `/Applications/MAMP/logs/php_error.log`
2. Les messages d'erreur détaillés y seront enregistrés
3. Recherchez "IMAP connection failed" pour voir l'erreur exacte
