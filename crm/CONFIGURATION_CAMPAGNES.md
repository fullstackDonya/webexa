# Configuration Automatique des Expéditeurs pour les Campagnes

## 📋 Vue d'ensemble

Ce système lie automatiquement les paramètres de connexion Email et WhatsApp configurés dans `email-settings.php` et `whatsapp-settings.php` aux campagnes créées dans `campaigns.php`. L'expéditeur est désormais automatiquement défini en fonction de la configuration active du client.

## 🎯 Fonctionnalités

✅ **Liaison automatique** : Les campagnes utilisent les configurations email/WhatsApp actives
✅ **Changement dynamique** : Si l'utilisateur change de compte email ou WhatsApp, les nouvelles campagnes utilisent automatiquement le nouveau compte
✅ **Multi-canal** : Support pour les campagnes Email et WhatsApp
✅ **Historique préservé** : Les campagnes existantes conservent leurs configurations d'origine

## 📁 Fichiers créés/modifiés

### Nouveaux fichiers

1. **`database/migrations/003_link_campaigns_to_configs.sql`**
   - Migration SQL pour ajouter les colonnes nécessaires
   - Ajoute : `email_config_id`, `whatsapp_config_id`, `channel`

2. **`includes/campaign-config-helper.php`**
   - Fonctions helper pour récupérer les configurations actives
   - Gestion automatique de l'expéditeur

### Fichiers modifiés

1. **`includes/send-campaign.php`**
   - Utilise automatiquement les configurations liées
   - Support multi-canal (Email/WhatsApp)

## 🚀 Installation

### Étape 1 : Exécuter la migration SQL

```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm
mysql -u root webitech < database/migrations/003_link_campaigns_to_configs.sql
```

Ou via phpMyAdmin :
1. Ouvrir phpMyAdmin
2. Sélectionner la base `webitech`
3. Aller dans l'onglet "SQL"
4. Copier-coller le contenu de `database/migrations/003_link_campaigns_to_configs.sql`
5. Exécuter

### Étape 2 : Vérifier les tables

Vérifiez que les nouvelles colonnes ont été ajoutées :

```sql
DESCRIBE campaigns;
```

Vous devriez voir :
- `email_config_id` (INT, NULL)
- `whatsapp_config_id` (INT, NULL)
- `channel` (VARCHAR(20), default 'email')

## 📝 Comment ça fonctionne

### 1. Configuration Email/WhatsApp

L'utilisateur configure ses comptes dans :
- **Email** : `email-settings.php` → Table `email_configurations`
- **WhatsApp** : `whatsapp-settings.php` → Table `whatsapp_configurations`

### 2. Création de campagne

Quand une campagne est créée dans `campaigns-add.php` :

```php
// Inclure le helper
require_once 'includes/campaign-config-helper.php';

// Récupérer les configurations actives
$email_configs = get_active_email_configs($pdo, $customer_id);
$whatsapp_configs = get_active_whatsapp_configs($pdo, $customer_id);

// Définir automatiquement la configuration par défaut
$default_config = !empty($email_configs) ? $email_configs[0] : null;

// Récupérer automatiquement l'expéditeur
$sender = get_sender_from_config($pdo, $customer_id, 'email', $config_id);
```

### 3. Envoi de campagne

Lors de l'envoi via `includes/send-campaign.php` :

```php
function send_campaign(PDO $pdo, array $campaign) {
    // Récupération automatique de la config
    $channel = $campaign['channel'] ?? 'email';
    $config_id = $campaign['email_config_id'] ?? null;
    
    // Récupération de l'expéditeur depuis la config
    $sender = get_sender_from_config($pdo, $customer_id, $channel, $config_id);
    
    // Utilisation pour l'envoi
    $fromEmail = $sender['sender_email'];
    $fromName = $sender['sender_name'];
}
```

## 🔧 Intégration dans campaigns-add.php

Pour intégrer dans le formulaire d'ajout de campagne, ajoutez après la ligne 12 :

```php
// Inclure le helper
require_once __DIR__ '/includes/campaign-config-helper.php';

// Récupérer les configurations actives
$email_configs = get_active_email_configs($pdo, $customer_id);
$whatsapp_configs = get_active_whatsapp_configs($pdo, $customer_id);

// Définir les valeurs par défaut
$default_email_config = !empty($email_configs) ? $email_configs[0] : null;
$default_sender_name = $default_email_config ? explode('@', $default_email_config['email'])[0] : 'Mon Entreprise';
$default_sender_email = $default_email_config ? $default_email_config['email'] : 'noreply@monentreprise.com';
```

### Ajouter au tableau $old (ligne 15) :

```php
$old = [
    'campaign_name' => $_POST['campaign_name'] ?? '',
    'campaign_type' => $_POST['campaign_type'] ?? 'newsletter',
    'campaign_subject' => $_POST['campaign_subject'] ?? '',
    'channel' => $_POST['channel'] ?? 'email',  // NOUVEAU
    'email_config_id' => $_POST['email_config_id'] ?? ($default_email_config['id'] ?? null),  // NOUVEAU
    'whatsapp_config_id' => $_POST['whatsapp_config_id'] ?? null,  // NOUVEAU
    'sender_name' => $_POST['sender_name'] ?? $default_sender_name,  // MODIFIÉ
    'sender_email' => $_POST['sender_email'] ?? $default_sender_email,  // MODIFIÉ
    // ... reste du tableau
];
```

### Dans le traitement POST (ligne 40) :

```php
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['campaign_name'] ?? '');
    $type = $_POST['campaign_type'] ?? '';
    $subject = trim($_POST['campaign_subject'] ?? '');
    $channel = $_POST['channel'] ?? 'email';  // NOUVEAU
    $email_config_id = !empty($_POST['email_config_id']) ? intval($_POST['email_config_id']) : null;  // NOUVEAU
    $whatsapp_config_id = !empty($_POST['whatsapp_config_id']) ? intval($_POST['whatsapp_config_id']) : null;  // NOUVEAU
    
    // Récupérer l'expéditeur depuis la configuration
    $sender = get_sender_from_config($pdo, $customer_id, $channel, 
        $channel === 'email' ? $email_config_id : $whatsapp_config_id);
    
    $senderName = !empty($_POST['sender_name']) ? trim($_POST['sender_name']) : $sender['sender_name'];
    $senderEmail = !empty($_POST['sender_email']) ? trim($_POST['sender_email']) : $sender['sender_email'];
    
    // ... reste du code
}
```

### Dans l'ajout des colonnes (ligne 84) :

```php
$ensureColumn($pdo, 'campaigns', 'email_config_id', "email_config_id INT DEFAULT NULL");
$ensureColumn($pdo, 'campaigns', 'whatsapp_config_id', "whatsapp_config_id INT DEFAULT NULL");
$ensureColumn($pdo, 'campaigns', 'channel', "channel VARCHAR(20) DEFAULT 'email'");
```

### Dans la requête INSERT (ligne 165) :

```php
$stmt = $pdo->prepare('INSERT INTO campaigns (
    customer_id, name, subject, type, channel, 
    email_config_id, whatsapp_config_id,  
    status, recipients, recipients_emails, recipients_count, 
    open_rate, click_rate, scheduled_at, 
    sender_name, sender_email, audience
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

$stmt->execute([
    $customer_id ?? 0,
    $name,
    $subject,
    $type,
    $channel,  // NOUVEAU
    $email_config_id,  // NOUVEAU
    $whatsapp_config_id,  // NOUVEAU
    $status,
    $recipients_count,
    $recipients_emails_json,
    $recipients_count,
    0.0,
    0.0,
    $scheduled_at,
    $senderName,
    $senderEmail,
    (is_array($company_ids) && count($company_ids)) ? 'companies' : $audience
]);
```

### Ajouter dans le formulaire HTML (après la ligne 280) :

```html
<!-- Sélection du canal et de la configuration -->
<div class="row mb-3">
    <div class="col-md-6">
        <label for="channel" class="form-label">Canal de communication</label>
        <select class="form-control" id="channel" name="channel">
            <option value="email" <?php if($old['channel']==='email') echo 'selected'; ?>>📧 Email</option>
            <?php if(!empty($whatsapp_configs)): ?>
            <option value="whatsapp" <?php if($old['channel']==='whatsapp') echo 'selected'; ?>>📱 WhatsApp</option>
            <?php endif; ?>
        </select>
    </div>
    <div class="col-md-6">
        <div id="email-config-select" style="display:none;">
            <label for="email_config_id" class="form-label">Configuration Email</label>
            <select class="form-control" id="email_config_id" name="email_config_id">
                <option value="">-- Sélectionner --</option>
                <?php foreach($email_configs as $config): ?>
                <option value="<?php echo $config['id']; ?>" 
                    <?php if($old['email_config_id'] == $config['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($config['email']); ?> (<?php echo $config['provider']; ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="whatsapp-config-select" style="display:none;">
            <label for="whatsapp_config_id" class="form-label">Configuration WhatsApp</label>
            <select class="form-control" id="whatsapp_config_id" name="whatsapp_config_id">
                <option value="">-- Sélectionner --</option>
                <?php foreach($whatsapp_configs as $config): ?>
                <option value="<?php echo $config['id']; ?>" 
                    <?php if($old['whatsapp_config_id'] == $config['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($config['display_phone_number']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    <strong>Info :</strong> L'expéditeur sera automatiquement défini selon la configuration sélectionnée.
    Les champs ci-dessous peuvent être personnalisés si nécessaire.
</div>
```

### Ajouter le JavaScript (avant </body>) :

```javascript
<script>
// Gestion du changement de canal
document.getElementById('channel').addEventListener('change', function() {
    const channel = this.value;
    document.getElementById('email-config-select').style.display = channel === 'email' ? 'block' : 'none';
    document.getElementById('whatsapp-config-select').style.display = channel === 'whatsapp' ? 'block' : 'none';
});

// Déclencher au chargement
document.getElementById('channel').dispatchEvent(new Event('change'));

// Mise à jour automatique de l'expéditeur quand une config est sélectionnée
document.getElementById('email_config_id')?.addEventListener('change', function() {
    if (this.value) {
        // TODO: Récupérer l'email via AJAX et mettre à jour sender_email
    }
});
</script>
```

## ✅ Test de fonctionnement

### Test 1 : Configuration Email

1. Allez dans `email-settings.php`
2. Connectez un compte Gmail (par exemple : `commercial@monentreprise.com`)
3. Allez dans `campaigns-add.php`
4. Créez une nouvelle campagne
5. Vérifiez que l'expéditeur est automatiquement `commercial@monentreprise.com`

### Test 2 : Changement de compte

1. Dans `email-settings.php`, désactivez le compte actuel
2. Ajoutez un nouveau compte (par exemple : `marketing@monentreprise.com`)
3. Créez une nouvelle campagne
4. Vérifiez que l'expéditeur est automatiquement `marketing@monentreprise.com`

### Test 3 : WhatsApp

1. Configurez un compte WhatsApp Business dans `whatsapp-settings.php`
2. Dans `campaigns-add.php`, sélectionnez le canal "WhatsApp"
3. Vérifiez que la configuration WhatsApp apparaît dans la liste

## 🔍 Vérification SQL

Pour vérifier que tout fonctionne :

```sql
-- Voir les campagnes avec leurs configurations
SELECT 
    c.id,
    c.name,
    c.channel,
    c.sender_email,
    ec.email as config_email,
    wc.display_phone_number as whatsapp_number
FROM campaigns c
LEFT JOIN email_configurations ec ON c.email_config_id = ec.id
LEFT JOIN whatsapp_configurations  wc ON c.whatsapp_config_id = wc.id
ORDER BY c.created_at DESC
LIMIT 10;
```

## 📞 Support

Si vous rencontrez des problèmes :

1. Vérifiez que la migration SQL a été exécutée
2. Vérifiez les logs d'erreur PHP
3. Vérifiez que les tables `email_configurations` et `whatsapp_configurations` existent
4. Vérifiez que `includes/campaign-config-helper.php` est bien inclus

## 🔄 Prochaines étapes (optionnel)

- [ ] Interface UI complète dans campaigns-add.php
- [ ] API REST pour récupérer les configs en AJAX
- [ ] Support SMTP réel avec PHPMailer
- [ ] Support WhatsApp Business API complet
- [ ] Historique des changements de configuration
- [ ] Tests unitaires

---

**Date de création** : 18 février 2026  
**Version** : 1.0.0  
**Auteur** : Système de gestion de campagnes CRM
