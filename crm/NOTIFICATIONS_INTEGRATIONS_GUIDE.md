# Système de Notifications et d'Intégrations - CRM Intelligent

## 📋 Vue d'ensemble

Ce document décrit le système complet de **notifications** et d'**intégrations** ajouté au CRM Intelligent. Le système permet aux utilisateurs de recevoir des alertes en temps réel et de connecter leur CRM à de nombreux outils tiers.

---

## 🔔 Système de Notifications

### Tables de base de données

#### `crm_notifications`
Stocke toutes les notifications du système :
- **id** : Identifiant unique
- **customer_id** : ID du client
- **user_id** : ID de l'utilisateur (optionnel, NULL pour notifications globales)
- **type** : Type de notification (ex: lead_new, campaign_completed, integration_error)
- **title** : Titre de la notification
- **message** : Message détaillé
- **icon** : Icône FontAwesome (ex: fa-bell, fa-user-plus)
- **color** : Couleur (success, info, warning, danger)
- **link** : Lien vers la ressource concernée
- **is_read** : Statut de lecture (0 ou 1)
- **priority** : Priorité (low, medium, high, urgent)
- **created_at** : Date de création
- **read_at** : Date de lecture

### API Notifications

**Endpoint** : `api/notifications.php`

#### Actions disponibles :

1. **list** - Lister les notifications
```javascript
GET api/notifications.php?action=list&limit=50&offset=0&unread_only=true
```

2. **mark-read** - Marquer une notification comme lue
```javascript
POST api/notifications.php?action=mark-read&id=123
```

3. **mark-all-read** - Marquer toutes comme lues
```javascript
POST api/notifications.php?action=mark-all-read
```

4. **create** - Créer une notification
```javascript
POST api/notifications.php?action=create
Body: {
  "type": "lead_new",
  "title": "Nouveau lead chaud",
  "message": "Un lead de haute qualité a été détecté",
  "icon": "fa-user-plus",
  "color": "success",
  "link": "leads-view.php?id=123",
  "priority": "high"
}
```

5. **delete** - Supprimer une notification
```javascript
POST api/notifications.php?action=delete&id=123
```

6. **stats** - Statistiques des notifications
```javascript
GET api/notifications.php?action=stats
```

### Composants

#### 1. Topbar (`includes/topbar.php`)
Barre supérieure présente sur toutes les pages avec :
- Recherche globale
- Bouton notifications avec badge
- Dropdown des notifications récentes
- Accès rapide aux intégrations, IA, et paramètres

**Intégration dans une page :**
```php
<?php include 'includes/topbar.php'; ?>
```

#### 2. Page Notifications (`notifications.php`)
Page complète de gestion des notifications avec :
- Filtres par type (toutes, non lues, urgentes, leads, campagnes, intégrations)
- Actions (marquer comme lu, supprimer)
- Actions en masse (tout marquer comme lu, supprimer les lues)

### Exemples d'utilisation

#### Créer une notification depuis PHP
```php
// Via API interne
$stmt = $pdo->prepare("
    INSERT INTO crm_notifications 
    (customer_id, type, title, message, icon, color, link, priority)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $customer_id,
    'lead_new',
    'Nouveau lead qualifié',
    'Jean Dupont - Score: 90/100',
    'fa-user-plus',
    'success',
    'leads-view.php?id=5',
    'high'
]);
```

#### Créer une notification depuis JavaScript
```javascript
async function createNotification(data) {
    const response = await fetch('api/notifications.php?action=create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    });
    
    return await response.json();
}

// Utilisation
createNotification({
    type: 'campaign_completed',
    title: 'Campagne terminée',
    message: 'Votre campagne "Promo Mars" est terminée avec 45% d\'ouverture',
    icon: 'fa-envelope',
    color: 'info',
    link: 'campaigns-stats.php?id=10',
    priority: 'medium'
});
```

---

## 🔌 Système d'Intégrations

### Tables de base de données

#### `integrations`
Stocke les intégrations disponibles et leur configuration :
- **id** : Identifiant unique
- **customer_id** : ID du client
- **integration_type** : Type (notion, google_forms, slack, etc.)
- **name** : Nom affiché
- **description** : Description
- **icon** : Icône FontAwesome
- **color** : Couleur du thème
- **is_active** : Actif (0 ou 1)
- **config** : Configuration JSON
- **api_key** : Clé API
- **api_secret** : Secret API
- **webhook_url** : URL webhook
- **last_sync** : Dernière synchronisation
- **sync_status** : Statut (never, success, error, syncing)
- **error_message** : Message d'erreur éventuel
- **created_at**, **updated_at**

#### `integration_logs`
Logs des synchronisations et actions :
- **id** : Identifiant
- **integration_id** : ID de l'intégration
- **action** : Action effectuée (sync, test, configure)
- **status** : Statut (success, error, warning)
- **message** : Message
- **data** : Données JSON
- **created_at** : Date

### Intégrations disponibles

Le système supporte 21 intégrations pré-configurées :

#### **Cloud & Stockage**
- 📄 **Notion** - Synchronisez vos données CRM
- 💾 **Dropbox** - Stockez vos fichiers
- 📁 **Google Drive** - Accédez à vos documents

#### **Productivité**
- 💬 **Slack** - Notifications et gestion
- 👥 **Microsoft Teams** - Collaboration
- 📹 **Zoom** - Planifiez vos réunions
- 📋 **Jira** - Synchronisez vos tickets
- 📊 **Trello** - Connectez vos boards
- 🐙 **GitHub** - Liez vos projets
- 🦊 **GitLab** - Intégration GitLab

#### **Marketing**
- 📱 **Meta Ads** - Campagnes Facebook/Instagram
- 🔍 **Google Ads** - Analysez vos campagnes
- 📧 **Mailchimp** - Synchronisez vos listes
- 📈 **HubSpot** - Synchronisation CRM

#### **Autres**
- 🔗 **LinkedIn** - Enrichissez vos leads
- 📝 **Google Forms** - Importez les réponses
- 📅 **Google Calendar** - Synchronisez vos événements
- 📅 **Outlook Calendar** - Calendrier Outlook
- 💳 **Stripe** - Gestion des paiements
- ⚡ **Zapier** - Automatisez vos workflows
- 🔧 **Webhooks** - Webhooks personnalisés

### API Intégrations

**Endpoint** : `api/integrations.php`

#### Actions disponibles :

1. **list** - Lister toutes les intégrations
```javascript
GET api/integrations.php?action=list
```

2. **get** - Obtenir une intégration spécifique
```javascript
GET api/integrations.php?action=get&id=5
```

3. **toggle** - Activer/désactiver
```javascript
POST api/integrations.php?action=toggle
Body: { id: 5, is_active: 1 }
```

4. **configure** - Configurer une intégration
```javascript
POST api/integrations.php?action=configure
Body: {
  id: 5,
  api_key: "sk_live_xxxxx",
  api_secret: "secret",
  webhook_url: "https://...",
  config: {
    channel: "#crm-notifications",
    auto_sync: true
  }
}
```

5. **sync** - Synchroniser
```javascript
POST api/integrations.php?action=sync
Body: { id: 5 }
```

6. **test** - Tester la connexion
```javascript
POST api/integrations.php?action=test
Body: { id: 5 }
```

7. **logs** - Récupérer les logs
```javascript
GET api/integrations.php?action=logs&id=5&limit=50
```

### Page Intégrations (`integrations.php`)

Interface complète de gestion des intégrations avec :
- Vue en grille de toutes les intégrations disponibles
- Filtres par catégorie (cloud, productivité, marketing)
- Statistiques (total, actives, dernière sync)
- Actions :
  - ⚡ Activer/désactiver
  - ⚙️ Configurer (API keys, webhooks)
  - 🔄 Synchroniser
  - 📊 Voir les logs

### Exemples d'utilisation

#### Vérifier les intégrations actives
```php
$stmt = $pdo->prepare("
    SELECT * FROM integrations 
    WHERE customer_id = ? AND is_active = 1
");
$stmt->execute([$customer_id]);
$activeIntegrations = $stmt->fetchAll();
```

#### Créer une notification lors d'une activation
```php
// Dans api/integrations.php, fonction createNotification()
createNotification($pdo, $customer_id, [
    'type' => 'integration_toggle',
    'title' => 'Intégration activée',
    'message' => "L'intégration Slack a été activée avec succès",
    'icon' => 'fa-check-circle',
    'color' => 'success',
    'link' => 'integrations.php'
]);
```

#### Synchroniser une intégration depuis le frontend
```javascript
async function syncIntegration(id) {
    const formData = new FormData();
    formData.append('id', id);
    
    const response = await fetch('api/integrations.php?action=sync', {
        method: 'POST',
        body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
        alert('Synchronisation réussie !');
    }
}
```

---

## 🚀 Déploiement

### 1. Migration de la base de données
```bash
mysql -u root -proot webitech < /Applications/MAMP/htdocs/PP/webitech/WEB/crm/migrations/create_notifications_system.sql
```

### 2. Vérification des tables
```sql
-- Vérifier que les tables existent
SHOW TABLES LIKE 'crm_notifications';
SHOW TABLES LIKE 'integrations';
SHOW TABLES LIKE 'integration_logs';

-- Compter les notifications
SELECT COUNT(*) FROM crm_notifications;

-- Lister les intégrations
SELECT integration_type, name, is_active FROM integrations;
```

### 3. Intégration dans les pages

Pour ajouter le système à une page existante :

```php
<?php
require_once __DIR__ . '/config/database.php';
session_start();
// ... votre code ...
?>
<!DOCTYPE html>
<html>
<head>
    <!-- ... vos liens CSS ... -->
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/topbar.php'; ?> <!-- ✅ AJOUTER CETTE LIGNE -->
            
            <!-- Votre contenu -->
        </div>
    </div>
</body>
</html>
```

---

## 📖 Types de notifications recommandés

| Type | Icône | Couleur | Utilisation |
|------|-------|---------|-------------|
| `lead_new` | fa-user-plus | success | Nouveau lead créé |
| `lead_hot` | fa-fire | danger | Lead chaud détecté |
| `campaign_completed` | fa-envelope | info | Campagne terminée |
| `campaign_error` | fa-exclamation-triangle | danger | Erreur de campagne |
| `integration_success` | fa-check-circle | success | Intégration réussie |
| `integration_error` | fa-times-circle | danger | Erreur d'intégration |
| `task_reminder` | fa-clock | warning | Rappel de tâche |
| `email_received` | fa-envelope | info | Email reçu |
| `payment_received` | fa-dollar-sign | success | Paiement reçu |
| `system_alert` | fa-bell | warning | Alerte système |

---

## 🎨 Personnalisation

### Couleurs disponibles
- **success** : Vert (succès, confirmations)
- **info** : Bleu (informations)
- **warning** : Orange (avertissements)
- **danger** : Rouge (erreurs, urgences)
- **primary** : Violet/bleu (neutre)
- **secondary** : Gris (secondaire)
- **dark** : Noir (sombre)

### Priorités
- **urgent** : Badge rouge, notification prioritaire
- **high** : Badge orange, haute priorité
- **medium** : Priorité normale (défaut)
- **low** : Basse priorité, badge gris

---

## 🔐 Sécurité

### Bonnes pratiques

1. **Isolation des données** : Les notifications et intégrations sont filtrées par `customer_id`
2. **Validation des entrées** : Toutes les données sont validées et échappées
3. **Chiffrement recommandé** : Les `api_key` et `api_secret` devraient être chiffrés en production
4. **Logs d'audit** : Toutes les actions sur les intégrations sont loggées dans `integration_logs`

### Amélioration future pour la production

```php
// Chiffrer les secrets
function encryptSecret($secret) {
    return openssl_encrypt($secret, 'AES-256-CBC', getenv('ENCRYPTION_KEY'), 0, getenv('ENCRYPTION_IV'));
}

// Déchiffrer
function decryptSecret($encrypted) {
    return openssl_decrypt($encrypted, 'AES-256-CBC', getenv('ENCRYPTION_KEY'), 0, getenv('ENCRYPTION_IV'));
}
```

---

## 📊 Monitoring

### Requêtes SQL utiles

```sql
-- Notifications non lues par client
SELECT customer_id, COUNT(*) as unread_count
FROM crm_notifications
WHERE is_read = 0
GROUP BY customer_id;

-- Intégrations actives
SELECT name, last_sync, sync_status
FROM integrations
WHERE is_active = 1;

-- Logs d'erreur des intégrations
SELECT i.name, il.action, il.message, il.created_at
FROM integration_logs il
JOIN integrations i ON il.integration_id = i.id
WHERE il.status = 'error'
ORDER BY il.created_at DESC
LIMIT 50;

-- Notifications par type (dernières 24h)
SELECT type, COUNT(*) as count
FROM crm_notifications
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY type
ORDER BY count DESC;
```

---

## ✅ Checklist d'implémentation

- [x] Tables de base de données créées
- [x] API notifications fonctionnelle
- [x] API intégrations fonctionnelle
- [x] Topbar avec dropdown notifications
- [x] Page notifications complète
- [x] Page intégrations complète
- [x] Badge dans le sidebar
- [x] Intégration automatique dans campaigns.php
- [x] 21 intégrations pré-configurées
- [x] Documentation complète

---

## 🎯 Prochaines étapes recommandées

1. **Ajouter le topbar aux autres pages** :
   - index.php
   - leads.php
   - contacts.php
   - customers.php
   - etc.

2. **Implémenter les vraies synchronisations** :
   - Connecter à l'API Slack réelle
   - Intégrer Google Calendar
   - Configurer les webhooks Zapier

3. **Notifications temps réel** :
   - Implémenter WebSockets ou SSE pour les notifications en temps réel
   - Ajouter des sons/vibrations pour les notifications urgentes

4. **Analytics** :
   - Dashboard des notifications lues/non lues
   - Statistiques d'utilisation des intégrations
   - Taux de succès des synchronisations

5. **Mobile** :
   - Optimiser le topbar pour mobile
   - Notifications push (PWA)

---

## 📞 Support

Pour toute question ou problème :
- Vérifier les logs : `integration_logs`
- Vérifier les notifications test dans la base de données
- Consulter la console du navigateur pour les erreurs JavaScript

---

**Date de création** : 26 février 2026  
**Version** : 1.0.0  
**Auteur** : Système CRM Intelligent
