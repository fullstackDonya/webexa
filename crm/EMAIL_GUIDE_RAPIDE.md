# 📧 Guide Rapide - Synchronisation Email

## 🎯 Ce qui a été implémenté

### ✅ Fonctionnalités Complètes

1. **Synchronisation des emails entrants**
   - Connexion IMAP pour tous les fournisseurs (Gmail, Outlook, Hostinger, etc.)
   - Récupération automatique des emails avec corps, expéditeur, destinataire
   - Détection des pièces jointes
   - Gestion du statut lu/non lu
   - Déduplication intelligente des emails

2. **Boîte de réception complète**
   - Interface web moderne pour consulter vos emails
   - Filtres: tous, non lus, lus, avec pièces jointes
   - Recherche dans le sujet, l'expéditeur et le corps
   - Vue détaillée de chaque email
   - Marquage lu/non lu
   - Suppression d'emails
   - Extraction de leads depuis les emails

3. **Envoi d'emails**
   - Composition avec éditeur riche (HTML)
   - Support multi-comptes (sélection du compte expéditeur)
   - CC et BCC
   - Réponse et transfert d'emails
   - Auto-complétion des contacts depuis votre CRM
   - Envoi via SMTP sécurisé

4. **Sécurité maximale**
   - **Chiffrement AES-256-GCM** pour tous les mots de passe
   - **Dérivation de clés HKDF** pour isolation par contexte
   - Stockage sécurisé des tokens OAuth2
   - Aucun credential en clair dans la base

5. **Synchronisation automatique**
   - Script cron pour sync toutes les 5 minutes
   - Support multi-clients (traite tous les comptes)
   - Logs détaillés de chaque synchronisation
   - Gestion des erreurs et retry automatique

---

## 🚀 Installation Rapide (5 minutes)

### Étape 1: Installer les dépendances

```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm
chmod +x install-email-system.sh
./install-email-system.sh
```

Le script va:
- ✅ Vérifier PHP et les extensions requises
- ✅ Installer Composer et PHPMailer
- ✅ Créer le fichier `.env` avec une clé de chiffrement
- ✅ Créer le répertoire logs
- ✅ Configurer les permissions

### Étape 2: Créer les tables

```bash
mysql -u root webitech < database/email_tables.sql
```

Ou depuis phpMyAdmin:
1. Ouvrir http://localhost:8888/phpmyadmin
2. Sélectionner la base `webitech`
3. Onglet "Importer"
4. Sélectionner `database/email_tables.sql`
5. Cliquer "Exécuter"

### Étape 3: Configurer le premier compte

1. Accéder à http://localhost:8888/crm/email-settings.php
2. Cliquer sur "IMAP/SMTP Manuel"
3. Remplir:
   - **Email**: votre.email@gmail.com
   - **Mot de passe**: votre mot de passe (ou mot de passe d'application)
   - **Provider**: Gmail
   - **IMAP**: imap.gmail.com:993
   - **SMTP**: smtp.gmail.com:587
4. Enregistrer

### Étape 4: Première synchronisation

1. Aller sur http://localhost:8888/crm/email-inbox.php
2. Cliquer sur "Synchroniser"
3. Attendre quelques secondes
4. Vos emails apparaissent ! 🎉

---

## 📖 Utilisation Quotidienne

### Consulter les emails

**URL**: http://localhost:8888/crm/email-inbox.php

#### Navigation
- **Tous**: Affiche tous les emails
- **Non lus**: Filtre les emails non lus uniquement
- **Lus**: Filtre les emails déjà consultés
- **Pièces jointes**: Emails contenant des fichiers

#### Actions
- **Clic sur un email**: Ouvre la vue détaillée
- **Synchroniser**: Lance une sync manuelle
- **Nouveau**: Compose un nouvel email
- **Paramètres**: Gère les comptes email

### Envoyer un email

**URL**: http://localhost:8888/crm/email-compose.php

1. Sélectionner le compte expéditeur
2. Saisir le(s) destinataire(s) (auto-complétion disponible)
3. Ajouter CC/BCC si besoin
4. Rédiger le sujet et le message (éditeur riche)
5. Cliquer "Envoyer"

### Répondre à un email

1. Ouvrir l'email dans la boîte de réception
2. Cliquer "Répondre"
3. L'adresse du destinataire et le sujet sont pré-remplis
4. Rédiger votre réponse
5. Envoyer

---

## ⚙️ Configuration des Providers

### Gmail

#### Avec mot de passe d'application (recommandé)

1. Aller sur https://myaccount.google.com/security
2. Activer la vérification en 2 étapes
3. Générer un "Mot de passe d'application"
4. Utiliser ce mot de passe dans le CRM

**Paramètres:**
```
Email: votre.email@gmail.com
Mot de passe: xxxx xxxx xxxx xxxx (mot de passe d'app)
IMAP: imap.gmail.com:993
SMTP: smtp.gmail.com:587
```

#### Avec OAuth2 (avancé)

1. Créer un projet sur Google Cloud Console
2. Activer Gmail API
3. Créer des identifiants OAuth 2.0
4. Ajouter dans `.env`:
```
GOOGLE_CLIENT_ID=votre_client_id
GOOGLE_CLIENT_SECRET=votre_secret
```

### Outlook / Microsoft 365

**Paramètres:**
```
Email: votre.email@outlook.com
Mot de passe: votre_mot_de_passe
IMAP: outlook.office365.com:993
SMTP: smtp.office365.com:587
```

### Hostinger

**Paramètres:**
```
Email: contact@votredomaine.com
Mot de passe: votre_mot_de_passe
IMAP: imap.hostinger.com:993
SMTP: smtp.hostinger.com:465
```

### Autre Provider (cPanel, OVH, etc.)

Contactez votre hébergeur pour obtenir:
- Serveur IMAP et port (généralement 993)
- Serveur SMTP et port (généralement 587 ou 465)
- Type de chiffrement (SSL/TLS)

---

## 🤖 Synchronisation Automatique

### Configuration du Cron

Pour synchroniser automatiquement toutes les 5 minutes:

```bash
# Éditer crontab
crontab -e

# Ajouter cette ligne
*/5 * * * * /usr/bin/php /Applications/MAMP/htdocs/PP/webitech/WEB/crm/cron-email-sync.php >> /Applications/MAMP/htdocs/PP/webitech/WEB/crm/logs/email-sync.log 2>&1
```

### Test manuel

```bash
php /Applications/MAMP/htdocs/PP/webitech/WEB/crm/cron-email-sync.php
```

### Vérifier les logs

```bash
tail -f /Applications/MAMP/htdocs/PP/webitech/WEB/crm/logs/email-sync.log
```

---

## 🔧 Dépannage

### Problème: Connexion IMAP échoue

**Symptôme**: "Impossible de se connecter au serveur IMAP"

**Solutions**:
1. ✅ Vérifier email et mot de passe
2. ✅ Gmail: utiliser un mot de passe d'application
3. ✅ Vérifier serveur et port IMAP
4. ✅ Tester avec: `telnet imap.gmail.com 993`

### Problème: Envoi échoue

**Symptôme**: "Erreur lors de l'envoi"

**Solutions**:
1. ✅ Vérifier serveur et port SMTP
2. ✅ Vérifier authentification SMTP
3. ✅ Gmail: autoriser les applications moins sécurisées
4. ✅ Consulter les logs PHP

### Problème: Aucun email synchronisé

**Symptôme**: Boîte de réception vide après sync

**Solutions**:
1. ✅ Vérifier que le compte est actif (email-settings.php)
2. ✅ Lancer une sync manuelle
3. ✅ Consulter "Dernière Sync" et "Erreur" dans les paramètres
4. ✅ Vérifier les logs: `logs/email-sync.log`

### Problème: Extension IMAP manquante

**Symptôme**: "Call to undefined function imap_open"

**Solution pour MAMP**:
1. Éditer `/Applications/MAMP/bin/php/php8.x.x/conf/php.ini`
2. Décommenter: `extension=imap`
3. Redémarrer MAMP

---

## 📊 Statistiques et Monitoring

### Voir les stats dans la base

```sql
-- Nombre d'emails par compte
SELECT 
    ec.email,
    COUNT(*) as total_emails,
    SUM(CASE WHEN e.is_read = 0 THEN 1 ELSE 0 END) as unread
FROM emails e
JOIN email_configurations ec ON e.config_id = ec.id
GROUP BY ec.email;

-- Dernières synchronisations
SELECT 
    email,
    last_sync,
    TIMESTAMPDIFF(MINUTE, last_sync, NOW()) as minutes_ago,
    last_error
FROM email_configurations
WHERE is_active = 1
ORDER BY last_sync DESC;
```

### Vérifier les performances

```bash
# Nombre total d'emails
mysql -u root webitech -e "SELECT COUNT(*) as total FROM emails"

# Emails aujourd'hui
mysql -u root webitech -e "SELECT COUNT(*) as today FROM emails WHERE DATE(created_at) = CURDATE()"
```

---

## 🎯 Fonctionnalités Avancées

### Extraction de Leads

1. Ouvrir un email dans la boîte de réception
2. Cliquer sur "Extraire Lead"
3. Un nouveau lead est créé automatiquement dans votre CRM
4. Le lead contient l'email et le nom de l'expéditeur

### Recherche Avancée

Dans la boîte de réception:
1. Utiliser la barre de recherche
2. Recherche dans: sujet, expéditeur ET corps du message
3. Combinable avec les filtres (non lus, pièces jointes, etc.)

### Multi-comptes

Vous pouvez configurer plusieurs comptes email:
- Gmail personnel
- Outlook professionnel
- Email d'entreprise (Hostinger, etc.)

Dans la boîte de réception:
- Utilisez le menu déroulant pour filtrer par compte
- Ou affichez tous les emails de tous les comptes

---

## 📈 Prochaines Étapes

### Fonctionnalités à venir (roadmap)
- [ ] Support des pièces jointes dans l'envoi
- [ ] Templates d'emails pré-définis
- [ ] Signatures personnalisées
- [ ] Labels et dossiers
- [ ] Règles de filtrage automatique
- [ ] Notifications push
- [ ] Webhooks Gmail Push

---

## ✅ Checklist de Vérification

Avant de démarrer en production:

- [ ] Tables créées dans la base de données
- [ ] Fichier `.env` configuré avec `MAIL_CRYPTO_KEY`
- [ ] PHPMailer installé via Composer
- [ ] Au moins un compte email configuré
- [ ] Test de synchronisation manuelle réussi
- [ ] Test d'envoi d'email réussi
- [ ] Cron configuré (optionnel mais recommandé)
- [ ] Logs accessibles et lisibles
- [ ] Permissions correctes sur le répertoire logs

---

## 🆘 Support

### Logs utiles

- **Logs de synchronisation**: `logs/email-sync.log`
- **Logs PHP**: `/Applications/MAMP/logs/php_error.log`
- **Logs Apache**: `/Applications/MAMP/logs/apache_error.log`

### Commandes de diagnostic

```bash
# Vérifier PHP
php -v
php -m | grep -E "imap|openssl|pdo"

# Vérifier Composer
composer --version

# Test connexion IMAP
telnet imap.gmail.com 993

# Test connexion SMTP
telnet smtp.gmail.com 587

# Logs en temps réel
tail -f logs/email-sync.log
```

---

**Version**: 1.0.0  
**Date**: Février 2026  
**Statut**: ✅ Production Ready
