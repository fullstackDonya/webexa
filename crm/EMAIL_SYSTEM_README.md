# 📧 Système de Gestion Email Complet - CRM

## 🎯 Fonctionnalités

### ✅ Synchronisation des Emails Entrants
- Connexion IMAP pour tous les fournisseurs (Gmail, Outlook, Hostinger, etc.)
- Support OAuth2 pour Gmail et Microsoft 365
- Synchronisation automatique via cron (toutes les 5 minutes)
- Récupération des emails, pièces jointes, statuts de lecture
- Déduplication automatique des emails

### ✅ Boîte de Réception Complète
- Interface web moderne pour consulter vos emails
- Filtres: tous, non lus, lus, avec pièces jointes
- Recherche full-text dans sujet, expéditeur et corps
- Affichage détaillé des emails avec historique
- Marquage lu/non lu
- Extraction automatique de leads depuis les emails

### ✅ Envoi d'Emails
- Interface de composition avec éditeur riche (Summernote)
- Support multi-comptes
- Réponse et transfert d'emails
- CC et BCC
- Auto-complétion des contacts
- Envoi via SMTP avec authentification sécurisée

### ✅ Sécurité
- **Chiffrement AES-256-GCM** pour tous les credentials
- **Dérivation de clés HKDF** pour isolation par contexte
- Stockage sécurisé des tokens OAuth2
- Refresh automatique des tokens expirés
- Logs d'erreurs détaillés

---

## 🚀 Installation

### 1. Prérequis

```bash
# PHP 8.0+ avec extensions requises
php -m | grep -E "openssl|pdo|imap|mbstring"

# Composer pour les dépendances
curl -sS https://getcomposer.org/installer | php
```

### 2. Installer les dépendances

```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm

# Installer PHPMailer
composer require phpmailer/phpmailer

# Ou si composer.json n'existe pas encore
composer init
composer require phpmailer/phpmailer
```

### 3. Configuration de la base de données

```bash
# Créer les tables email
mysql -u root webitech < database/email_tables.sql

# Ou depuis PHP/MySQL
# Aller sur: http://localhost:8888/phpmyadmin
# Sélectionner la base 'webitech'
# Importer le fichier database/email_tables.sql
```

### 4. Configuration de l'environnement

Ajoutez ces variables dans votre fichier `.env`:

```bash
# Clé de chiffrement (IMPORTANT: à générer une seule fois)
MAIL_CRYPTO_KEY=

# Générer la clé:
php -r "echo 'MAIL_CRYPTO_KEY=' . base64_encode(random_bytes(32)) . PHP_EOL;"

# OAuth2 Gmail (optionnel)
GOOGLE_CLIENT_ID=votre_client_id
GOOGLE_CLIENT_SECRET=votre_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8888/crm/oauth-callback.php

# OAuth2 Microsoft/Outlook (optionnel)
MICROSOFT_CLIENT_ID=votre_app_id
MICROSOFT_CLIENT_SECRET=votre_secret
MICROSOFT_REDIRECT_URI=http://localhost:8888/crm/oauth-callback.php
```

### 5. Configurer la synchronisation automatique (Cron)

#### Sur macOS/Linux:

```bash
# Éditer crontab
crontab -e

# Ajouter cette ligne (sync toutes les 5 minutes)
*/5 * * * * /usr/bin/php /Applications/MAMP/htdocs/PP/webitech/WEB/crm/cron-email-sync.php >> /Applications/MAMP/htdocs/PP/webitech/WEB/crm/logs/email-sync.log 2>&1

# Créer le répertoire logs
mkdir -p /Applications/MAMP/htdocs/PP/webitech/WEB/crm/logs
chmod 755 /Applications/MAMP/htdocs/PP/webitech/WEB/crm/logs
```

#### Test manuel du cron:

```bash
php /Applications/MAMP/htdocs/PP/webitech/WEB/crm/cron-email-sync.php
```

---

## 📖 Utilisation

### Configurer un Compte Email

1. Aller dans **Paramètres Email** (email-settings.php)
2. Cliquer sur **"Ajouter un Email"** ou **"Connecter Gmail/Outlook"**
3. Remplir les informations:
   - **Email**: votre adresse email
   - **Mot de passe**: mot de passe ou mot de passe d'application
   - **Serveur IMAP**: imap.gmail.com (port 993)
   - **Serveur SMTP**: smtp.gmail.com (port 587)
4. Tester la connexion
5. Activer la synchronisation

### Synchroniser les Emails

#### Manuellement:
- Aller dans **Boîte de Réception** (email-inbox.php)
- Cliquer sur **"Synchroniser"**

#### Automatiquement:
- Le cron s'exécute toutes les 5 minutes
- Vérifier les logs: `logs/email-sync.log`

### Consulter les Emails

1. Aller dans **Boîte de Réception** (email-inbox.php)
2. Utiliser les filtres:
   - **Tous**: tous les emails
   - **Non lus**: emails non lus uniquement
   - **Lus**: emails déjà lus
   - **Pièces jointes**: emails avec fichiers joints
3. Cliquer sur un email pour le lire
4. Actions disponibles:
   - Marquer comme lu/non lu
   - Répondre
   - Transférer
   - Supprimer
   - Extraire un lead

### Envoyer un Email

1. Cliquer sur **"Nouveau"** dans la boîte de réception
2. Composer votre email:
   - Sélectionner le compte expéditeur
   - Destinataires (avec auto-complétion)
   - Sujet et corps (éditeur riche)
   - CC/BCC si nécessaire
3. Cliquer sur **"Envoyer"**

---

## 🔐 Configuration OAuth2

### Gmail (Google API)

1. Aller sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créer un nouveau projet
3. Activer **Gmail API**
4. Créer des identifiants OAuth 2.0:
   - Type: Application Web
   - URI de redirection: `http://localhost:8888/crm/oauth-callback.php`
5. Copier Client ID et Client Secret dans `.env`

### Outlook/Microsoft 365

1. Aller sur [Azure Portal](https://portal.azure.com/)
2. **Azure Active Directory** > **App registrations** > **New registration**
3. Ajouter les permissions:
   - `Mail.Read`
   - `Mail.Send`
   - `Mail.ReadWrite`
4. Créer un secret client
5. Copier Application ID et Secret dans `.env`

---

## 🛠️ Configuration Avancée

### Providers Email Supportés

#### Gmail
```
IMAP: imap.gmail.com:993 (SSL)
SMTP: smtp.gmail.com:587 (TLS)
Note: Activer "Accès moins sécurisé" ou utiliser mot de passe d'application
```

#### Outlook/Office 365
```
IMAP: outlook.office365.com:993 (SSL)
SMTP: smtp.office365.com:587 (TLS)
```

#### Hostinger
```
IMAP: imap.hostinger.com:993 (SSL)
SMTP: smtp.hostinger.com:465 (SSL)
```

#### Autre Provider (cPanel, etc.)
```
Vérifiez auprès de votre hébergeur pour:
- Serveur IMAP et port
- Serveur SMTP et port
- Type de chiffrement (SSL/TLS)
```

---

## 📊 Structure de la Base de Données

### Tables Principales

- **email_configurations**: Comptes email configurés
- **emails**: Emails synchronisés (reçus)
- **emails_sent**: Emails envoyés depuis le CRM
- **email_attachments**: Pièces jointes
- **email_campaigns**: Campagnes email marketing
- **email_sync_logs**: Historique des synchronisations

---

## 🐛 Dépannage

### Problème: Connexion IMAP échoue

**Solutions:**
1. Vérifier les credentials (email/mot de passe)
2. Vérifier serveur IMAP et port
3. Gmail: activer "Accès moins sécurisé" ou utiliser mot de passe d'application
4. Vérifier que l'extension PHP IMAP est activée: `php -m | grep imap`

### Problème: Envoi d'email échoue

**Solutions:**
1. Vérifier serveur SMTP et port
2. Vérifier authentification SMTP
3. Tester avec telnet: `telnet smtp.gmail.com 587`
4. Vérifier les logs d'erreurs PHP

### Problème: Cron ne se lance pas

**Solutions:**
1. Vérifier que le script est exécutable: `chmod +x cron-email-sync.php`
2. Tester manuellement: `php cron-email-sync.php`
3. Vérifier les logs cron: `grep CRON /var/log/syslog`
4. Vérifier le path PHP: `which php`

### Problème: Chiffrement échoue

**Solutions:**
1. Vérifier que `MAIL_CRYPTO_KEY` est définie dans `.env`
2. La clé doit être en base64 et de 32 bytes
3. Régénérer si nécessaire (attention: anciennes données inaccessibles)

---

## 📈 Performance

### Optimisations Recommandées

1. **Index Database**: Déjà créés dans email_tables.sql
2. **Limite de sync**: Par défaut 100 emails par sync (configurable)
3. **Fréquence cron**: 5 minutes recommandé (ajustable)
4. **Rétention**: Configurer `retention_days` pour nettoyer les vieux emails

### Monitoring

```bash
# Vérifier les logs de sync
tail -f logs/email-sync.log

# Statistiques
mysql -u root webitech -e "
  SELECT 
    COUNT(*) as total_emails,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
    MAX(email_date) as last_email
  FROM emails;
"
```

---

## 🔄 Mise à Jour

Pour mettre à jour vers la dernière version:

```bash
# Backup de la base
mysqldump -u root webitech > backup_$(date +%Y%m%d).sql

# Exécuter les nouvelles migrations SQL si nécessaire
mysql -u root webitech < database/email_tables.sql

# Mettre à jour les dépendances
composer update
```

---

## 📞 Support

Pour toute question ou problème:
1. Vérifier cette documentation
2. Consulter les logs: `logs/email-sync.log` et PHP error log
3. Tester les connexions manuellement
4. Vérifier la configuration `.env`

---

## 🎉 Roadmap

### Fonctionnalités à venir
- [ ] Support des pièces jointes dans l'envoi
- [ ] Templates d'emails prédéfinis
- [ ] Signatures email personnalisées
- [ ] Règles de filtrage automatique
- [ ] Labels/Dossiers personnalisés
- [ ] Recherche avancée avec filtres complexes
- [ ] Export des emails en PDF
- [ ] Notifications push pour nouveaux emails
- [ ] Intégration avec les campagnes marketing

---

**Version:** 1.0.0  
**Dernière mise à jour:** Février 2026  
**Auteur:** Équipe CRM Webitech
