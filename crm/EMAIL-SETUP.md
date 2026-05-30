# 📧 Intégration Email - Guide d'Installation

## Vue d'ensemble

L'intégration email est **complètement optionnelle** pour le CRM. Le système fonctionne 100% sans cette fonctionnalité.

### Fonctionnalités disponibles:
- ✅ Connexion à Gmail, Outlook, Hostinger ou serveur personnalisé
- ✅ Synchronisation automatique des emails entrants
- ✅ Extraction automatique des leads depuis les emails
- ✅ Gestion des campaigns d'email
- ✅ Suivi des ouvertures et clics
- ✅ Stockage sécurisé des credentials (AES-256-CBC)

## Installation

### 1. Exécuter les migrations SQL

```sql
-- Copier le contenu de database/email_tables.sql
-- Et l'exécuter dans votre base de données
```

Ou via le terminal:
```bash
mysql -u root -p your_database < database/email_tables.sql
```

### 2. Configurer les variables d'environnement

Ajouter à votre fichier `.env`:
```env
ENCRYPTION_KEY=your-256-bit-encryption-key-here
IMAP_ENABLED=true
SMTP_ENABLED=true
```

### 3. Installer les dépendances PHP optionnelles

Pour une meilleure compatibilité, installer PHPMailer:
```bash
composer require phpmailer/phpmailer
```

## Configuration par Fournisseur

### Gmail

1. Aller sur [https://myaccount.google.com/security](https://myaccount.google.com/security)
2. Activer l'**authentification à deux facteurs**
3. Générer un **mot de passe d'application**:
   - Aller à: Mots de passe d'application
   - Sélectionner: Mail + Windows
   - Copier le mot de passe généré

4. Utiliser dans le CRM:
   - **Email**: votre@gmail.com
   - **Mot de passe**: le mot de passe d'application (16 caractères)
   - **Fournisseur**: Gmail

### Outlook / Office 365

1. Aller sur [https://account.microsoft.com/security](https://account.microsoft.com/security)
2. Créer un **mot de passe d'application**:
   - Sécurité > Paramètres de sécurité avancés
   - Mots de passe d'application
   - Générer pour Mail et Windows

3. Utiliser dans le CRM:
   - **Email**: votre@outlook.com
   - **Mot de passe**: le mot de passe d'application
   - **Fournisseur**: Outlook
   - **Serveur IMAP**: outlook.office365.com (Port 993)
   - **Serveur SMTP**: smtp.office365.com (Port 587)

### Hostinger

1. Accéder au **Panneau Hostinger**
2. Aller à: Email > Comptes Email
3. Les paramètres sont:
   - **Serveur IMAP**: imap.hostinger.com (Port 993)
   - **Serveur SMTP**: smtp.hostinger.com (Port 465 ou 587)
   - **Email**: votre adresse email complète
   - **Mot de passe**: mot de passe du compte email

### Serveur Personnalisé (Autre)

Si votre fournisseur n'est pas listé, utiliser l'option **Personnalisé**:
- Obtenir les paramètres de votre provider
- Remplir les champs IMAP/SMTP
- Les credentials restent les mêmes

## Utilisation

### Page Configuration Email

Accédez à: `email-settings.php` depuis la sidebar

**Fonctionnalités**:
- ✅ Ajouter plusieurs comptes email
- ✅ Synchroniser les emails manuellement
- ✅ Voir le statut des synchronisations
- ✅ Supprimer des configurations
- ✅ Activer/désactiver la synchronisation de leads

### API - Synchronisation Automatique

Pour mettre en place une synchronisation automatique, ajouter un Cron job:

```bash
# Toutes les heures
0 * * * * /usr/bin/php /path/to/crm/api/email-sync-cron.php

# Toutes les 30 minutes
*/30 * * * * /usr/bin/php /path/to/crm/api/email-sync-cron.php
```

### Envoyer un Email depuis le CRM

```php
<?php
require_once 'includes/verify_subscriptions.php';
require_once 'includes/EmailIntegration.php';

// Récupérer une configuration active
$stmt = $pdo->prepare("
    SELECT * FROM email_configurations 
    WHERE customer_id = ? AND is_active = 1 LIMIT 1
");
$stmt->execute([$customer_id]);
$config = $stmt->fetch();

if ($config) {
    $email_integration = new EmailIntegration($pdo, $config);
    
    $sent = $email_integration->sendEmail(
        'recipient@example.com',
        'Objet du message',
        '<h1>Contenu HTML</h1>',
        'sender@example.com'
    );
    
    if ($sent) {
        echo "Email envoyé avec succès!";
    } else {
        echo "Erreur lors de l'envoi";
    }
}
?>
```

## Sécurité

### Chiffrement des Mots de Passe

- Les mots de passe sont chiffrés avec **AES-256-CBC**
- La clé de chiffrement doit être définie dans `ENCRYPTION_KEY`
- Les mots de passe ne sont jamais stockés en clair
- Les données chiffrées sont stockées en LONGTEXT

### Bonnes Pratiques

1. **Ne jamais exposer les credentials**
2. **Utiliser des mots de passe d'application** plutôt que le mot de passe réel
3. **Activer 2FA** sur vos comptes email
4. **Régulièrement auditer** les connexions configurées
5. **Supprimer les configurations non utilisées**

## Troubleshooting

### "Impossible de se connecter au serveur IMAP"

- ✅ Vérifier les paramètres du serveur
- ✅ Vérifier que les ports sont corrects (généralement 993 pour IMAP)
- ✅ Vérifier que 2FA est activé (pour Gmail/Outlook)
- ✅ Utiliser un mot de passe d'application

### Emails ne sont pas synchronisés

- ✅ Vérifier que la synchronisation est activée
- ✅ Cliquer sur "Synchroniser" manuellement
- ✅ Vérifier les logs: `error_log()`
- ✅ S'assurer que `sync_leads` est activé

### "ENCRYPTION_KEY non défini"

- ✅ Ajouter la clé dans le fichier `.env`
- ✅ Générer une clé sécurisée: `openssl rand -hex 32`

## Base de Données

### Tables créées:

1. **email_configurations** - Stockage des configurations
2. **emails** - Emails synchronisés
3. **email_campaigns** - Campaigns d'email
4. **email_campaign_recipients** - Destinataires de campaigns
5. **email_templates** - Templates d'email
6. **email_sync_logs** - Logs des synchronisations

## Désactiver l'intégration email

Si vous ne voulez pas utiliser cette fonctionnalité:

1. **Ne pas exécuter** `database/email_tables.sql`
2. Les tables ne seront pas créées
3. Le système fonctionne normalement sans

Vous pouvez **réactiver** à tout moment en exécutant le script SQL.

## Support

Pour toute question ou problème:
- Vérifier les logs PHP
- Consulter la documentation du fournisseur email
- Tester la connexion IMAP manuellement

---

**Note**: Cette intégration est **entièrement optionnelle** et ne gêne pas le fonctionnement du CRM si elle n'est pas utilisée.
