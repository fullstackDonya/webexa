# 📧 Système de Templates d'Emails - Guide Simple

## Comment ça marche

### 3 façons d'utiliser les templates :

#### 1️⃣ **Dans email-editor.php** (Le plus simple !)
```
Email-editor → Panneau gauche "Templates Prêts à l'emploi"
→ Cliquer sur un template
→ Sujet et contenu auto-remplis ✅
```

#### 2️⃣ **Dans campaigns-automation.php** (Rapidement)
```
Campaigns → Automatisation IA → Créer une automatisation
→ Cliquer "📧 Templates"
→ Choisir un template
→ Utiliser ce template ✅
```

#### 3️⃣ **Par API** (Pour développeurs)
```php
require_once 'includes/email_templates.php';
$content = generate_email_content('lead_welcome', [
    'first_name' => 'Jean',
    'email' => 'jean@example.com'
]);
```

---

## 15 Templates disponibles

### 🌱 Lead Nurturing
- Bienvenue - Lead
- Suivi 1 - 3 jours après
- Suivi 2 - 7 jours après

### 💰 Sales
- Demande de Démo Produit
- Demande de Tarification
- Rappel - Essai Gratuit

### ⭐ Customer Success
- Onboarding - Bienvenue Client
- Annonce - Nouvelle Fonctionnalité
- Win-Back - Nous vous manquez

### 🎉 Promotion
- Invitation Événement
- Promotion Saisonnière
- Programme de Parrainage

### 🤝 Partenariat
- Proposition de Partenariat Stratégique

---

## Variables de personnalisation

Les templates supportent ces variables qui se remplacent automatiquement :

```
{FIRST_NAME}   → Prénom du lead
{LAST_NAME}    → Nom du lead
{EMAIL}        → Email du lead
{COMPANY}      → Nom de l'entreprise
{DATE}         → Date du jour (dd/mm/yyyy)
{YEAR}         → Année en cours
```

**Exemple:**
```
Template: "Bienvenue {FIRST_NAME} !"
Envoyé à Jean → "Bienvenue Jean !"
```

---

## Fichiers du système

```
crm/
├── includes/
│   ├── email_templates.php ..................... Core (15 templates)
│   ├── template_suggestions_modal.php ......... Modal pour campaigns-automation
│   └── templates_alert_banner.php ............. Banneau optionnel
│
├── api/
│   └── email-templates.php .................... API REST
│
├── email-editor.php ............................ Intégration complète ✅
├── campaigns-automation.php .................... Modal intégrée ✅
└── includes/sidebar.php ........................ Menu mis à jour
```

---

## Cas d'usage rapides

### Créer un email de bienvenue (30 secondes)
1. Ouvrez `email-editor.php`
2. Dans le panneau gauche, cliquez sur "Bienvenue - Lead"
3. Modifiez si nécessaire
4. Enregistrez ✅

### Automatiser un suivi (1 minute)
1. Allez dans Campaigns → Automatisation IA
2. Cliquez "Créer une automatisation"
3. Cliquez "📧 Templates"
4. Choisissez "Suivi 1 - 3 jours après"
5. Créez l'automatisation ✅

### Envoyer une promotion spéciale (2 minutes)
1. Ouvrez `email-editor.php`
2. Choisissez "Promotion Saisonnière"
3. Personnalisez le contenu
4. Envoyez à vos contacts ✅

---

## Comment ajouter vos propres templates

Éditez `includes/email_templates.php` et ajoutez un nouveau template :

```php
'mon_template' => [
    'name' => 'Mon Template',
    'category' => 'promotion',
    'subject' => 'Votre sujet ici',
    'body' => '<p>Votre contenu HTML ici...</p>'
],
```

---

## Dépannage

**Q: Le template n'apparaît pas dans email-editor.php**
A: Vérifiez que `includes/email_templates.php` est présent

**Q: Les variables ne se remplacent pas**
A: Vérifiez la casse exacte: `{FIRST_NAME}` pas `{first_name}`

**Q: L'API retourne une erreur**
A: Vérifiez l'authentification (session) et le customer_id

---

## Points clés

✅ **15 templates prêts à l'emploi** - Conçus par des experts  
✅ **Intégration simple** - Dans email-editor.php et campaigns-automation.php  
✅ **Personnalisation** - Variables automatiques avec vos données  
✅ **Extensible** - Ajoutez vos propres templates facilement  
✅ **Sans dépendances** - Pur PHP, pas de libraires externes  

---

**Vous êtes prêt ! Commencez par email-editor.php 🚀**
