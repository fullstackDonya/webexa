# Système d'Onboarding Moderne - Webitech CRM/ERP

## 📋 Vue d'ensemble

Ce système d'onboarding moderne remplace l'ancien système de modals par un processus professionnel multi-étapes inspiré de Monday.com. Il permet aux utilisateurs de configurer leur profil, leur entreprise et leurs modules en quelques étapes simples.

## ✨ Fonctionnalités

### 🎯 Processus Multi-étapes
1. **Bienvenue** - Introduction au système
2. **Profil** - Informations personnelles (nom, email, téléphone, adresse...)
3. **Entreprise** - Détails de l'organisation (nom, SIRET, secteur, CA...)
4. **Modules** - Sélection des outils CRM/ERP/Marketing/Support
5. **Finalisation** - Récapitulatif et validation

### 🔄 Synchronisation Automatique
- Les données du **customer** sont automatiquement copiées vers **companies**
- Pas besoin de remplir deux fois les mêmes informations
- Synchronisation bidirectionnelle lors des mises à jour

### ⚙️ Page de Paramètres
- Les utilisateurs peuvent modifier leurs informations à tout moment
- Accessible via le menu sidebar (icône engrenage)
- Sections: Profil, Entreprise, Modules

### 🎨 Design Moderne
- Interface inspirée de Monday.com
- Animations fluides et transitions
- Responsive mobile-first
- Dégradés et glassmorphism

## 📦 Installation

### 1. Exécuter la Migration

```bash
cd /Applications/MAMP/htdocs/PP/webitech/WEB/crm
php run_onboarding_migration.php
```

Cette migration va :
- ✅ Ajouter `onboarding_completed` à la table `users`
- ✅ Ajouter `first_name` et `last_name` à la table `users`
- ✅ Créer la table `user_settings`
- ✅ Ajouter les colonnes manquantes à `customers` (position, address, city, postal_code)
- ✅ Ajouter les colonnes manquantes à `companies` (industry, siret, vat_number, employee_count, annual_revenue)
- ✅ Marquer les utilisateurs existants comme ayant complété l'onboarding s'ils ont un customer_id

### 2. Vérifier l'Installation

Le script de migration affiche un rapport complet :
```
✨ Migration terminée!
   • Requêtes réussies: 15
   • Requêtes ignorées: 0

🔍 Vérification de l'installation...
   ✅ Colonne users.onboarding_completed
   ✅ Table user_settings
   ✅ Colonne companies.industry

🎉 Toutes les vérifications sont passées avec succès!
```

## 🚀 Utilisation

### Pour les Nouveaux Utilisateurs

1. Après l'inscription/connexion, redirection automatique vers `setup-wizard.php`
2. Suivre les 5 étapes du wizard
3. À la fin, redirection vers `index.php`

### Pour les Utilisateurs Existants

- Marqués automatiquement comme ayant complété l'onboarding
- Peuvent accéder aux paramètres via `settings.php`
- Lien dans la sidebar : **Paramètres** (icône engrenage)

### Modifier les Paramètres

1. Cliquer sur **Paramètres** dans la sidebar
2. Modifier les sections :
   - **Profil Personnel** - Nom, email, téléphone, adresse
   - **Entreprise** - Nom société, SIRET, TVA, secteur, CA
   - **Modules Activés** - CRM, ERP, Projets, Marketing, Support, Analytics
3. Cliquer sur **Enregistrer** pour chaque section

## 📁 Structure des Fichiers

```
crm/
├── setup-wizard.php                 # Wizard d'onboarding multi-étapes
├── settings.php                     # Page de paramètres utilisateur
├── run_onboarding_migration.php     # Script de migration
├── api/
│   ├── setup-wizard.php            # API pour le wizard
│   └── settings.php                # API pour les paramètres
├── migrations/
│   └── 004_onboarding_system.sql   # Migration SQL
└── includes/
    └── index.php                   # Redirection vers wizard si nécessaire
```

## 🔧 API Endpoints

### `/api/setup-wizard.php` (POST)
Enregistre les données du wizard

**Payload:**
```json
{
  "profile": {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "phone": "+33 6 12 34 56 78",
    "position": "CEO",
    "address": "123 Rue Example",
    "city": "Paris",
    "postal_code": "75001"
  },
  "company": {
    "company_name": "ACME Corp",
    "siret": "123 456 789 00012",
    "vat_number": "FR12345678901",
    "website": "https://acme.com",
    "industry": "technology",
    "employee_count": "11-50",
    "annual_revenue": "500k-1m"
  },
  "modules": ["crm", "erp", "marketing"]
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Configuration complétée avec succès",
  "customer_id": 123
}
```

### `/api/settings.php` (POST)

#### Sauvegarder le Profil
```json
{
  "action": "save_profile",
  "data": {
    "first_name": "John",
    "last_name": "Doe",
    ...
  }
}
```

#### Sauvegarder l'Entreprise
```json
{
  "action": "save_company",
  "data": {
    "company_name": "ACME Corp",
    ...
  }
}
```

#### Sauvegarder les Modules
```json
{
  "action": "save_modules",
  "modules": ["crm", "erp", "analytics"]
}
```

## 🔄 Synchronisation Customer → Companies

Lorsqu'un utilisateur met à jour son profil dans `settings.php`, les données sont automatiquement synchronisées :

### Données Synchronisées
- ✅ Email
- ✅ Téléphone
- ✅ Adresse
- ✅ Ville
- ✅ Code Postal

### Logique de Synchronisation
1. Update `customers` table
2. Si une `company` existe pour ce `customer_id` → update
3. Sinon → create nouvelle company avec les données du customer

## 🎨 Personnalisation

### Modifier les Modules Disponibles

Éditer `setup-wizard.php` ligne ~940 :

```html
<div class="module-card selected" data-module="crm">
  <div class="module-icon">
    <i class="fas fa-users"></i>
  </div>
  <div class="module-name">CRM</div>
  <div class="module-desc">Description</div>
</div>
```

### Ajouter un Module
1. Ajouter une carte dans le wizard
2. Ajouter la même dans `settings.php`
3. Le module sera sauvegardé dans `user_settings.enabled_modules`

### Modifier les Secteurs d'Activité

Éditer les options dans `setup-wizard.php` et `settings.php` :

```html
<option value="new_industry">Nouveau Secteur</option>
```

## 🔐 Sécurité

- ✅ Vérification d'authentification sur toutes les pages
- ✅ Filtrage par `customer_id` pour l'isolation multi-tenant
- ✅ Prepared statements PDO pour éviter les injections SQL
- ✅ Validation côté serveur des données
- ✅ Transactions SQL pour garantir la cohérence

## 📊 Base de Données

### Table `users`
```sql
- id INT PRIMARY KEY
- email VARCHAR(255)
- first_name VARCHAR(100)          -- NOUVEAU
- last_name VARCHAR(100)            -- NOUVEAU
- customer_id INT
- onboarding_completed TINYINT(1)   -- NOUVEAU
- created_at TIMESTAMP
- updated_at TIMESTAMP              -- NOUVEAU
```

### Table `user_settings`
```sql
- id INT PRIMARY KEY
- user_id INT
- setting_key VARCHAR(100)          -- Ex: 'enabled_modules'
- setting_value TEXT                -- Ex: '["crm","erp"]'
- created_at TIMESTAMP
- updated_at TIMESTAMP
```

### Table `customers`
```sql
- id INT PRIMARY KEY
- name VARCHAR(255)
- email VARCHAR(255)
- phone VARCHAR(50)
- position VARCHAR(100)             -- NOUVEAU
- address VARCHAR(255)              -- NOUVEAU
- city VARCHAR(100)                 -- NOUVEAU
- postal_code VARCHAR(20)           -- NOUVEAU
- status VARCHAR(50)
- created_at TIMESTAMP
- updated_at TIMESTAMP              -- NOUVEAU
```

### Table `companies`
```sql
- id INT PRIMARY KEY
- name VARCHAR(255)
- email VARCHAR(255)
- phone VARCHAR(50)
- website VARCHAR(255)              -- NOUVEAU
- industry VARCHAR(100)             -- NOUVEAU
- siret VARCHAR(50)                 -- NOUVEAU
- vat_number VARCHAR(50)            -- NOUVEAU
- employee_count VARCHAR(20)        -- NOUVEAU
- annual_revenue VARCHAR(50)        -- NOUVEAU
- address VARCHAR(255)              -- NOUVEAU
- city VARCHAR(100)                 -- NOUVEAU
- postal_code VARCHAR(20)           -- NOUVEAU
- customer_id INT
- status VARCHAR(50)
- is_active TINYINT(1)
- created_at TIMESTAMP
- updated_at TIMESTAMP              -- NOUVEAU
```

## 🐛 Dépannage

### L'utilisateur est bloqué en boucle sur setup-wizard.php
```sql
-- Marquer manuellement comme complété
UPDATE users SET onboarding_completed = 1 WHERE id = USER_ID;
```

### Les modules ne s'affichent pas
```sql
-- Vérifier les modules enregistrés
SELECT * FROM user_settings WHERE user_id = USER_ID AND setting_key = 'enabled_modules';

-- Insérer manuellement si nécessaire
INSERT INTO user_settings (user_id, setting_key, setting_value) 
VALUES (USER_ID, 'enabled_modules', '["crm","erp"]');
```

### Les données customer ne se synchronisent pas vers companies
- Vérifier que `customer_id` est bien renseigné dans `users`
- Vérifier que la company existe avec le bon `customer_id`
- Consulter les logs PHP pour les erreurs SQL

## 📝 Notes Importantes

1. **CRM Module Obligatoire** - Le module CRM est toujours activé et ne peut pas être désactivé
2. **Backward Compatibility** - Les utilisateurs existants avec un `customer_id` sont automatiquement marqués comme ayant complété l'onboarding
3. **Skip Option** - Les utilisateurs peuvent passer certaines étapes du wizard (sauf la première)
4. **Mobile Responsive** - Le wizard et la page settings sont entièrement responsive

## 🎯 Prochaines Étapes

- [ ] Tests complets du wizard sur différents navigateurs
- [ ] Ajouter des validations côté client plus avancées
- [ ] Implémenter l'upload de logo d'entreprise
- [ ] Ajouter des préférences utilisateur (langue, timezone...)
- [ ] Créer un dashboard d'administration pour gérer les modules globalement

## 💡 Support

Pour toute question ou problème :
1. Consulter les logs PHP : `/Applications/MAMP/logs/php_error.log`
2. Vérifier la console JavaScript du navigateur
3. Tester l'API directement avec Postman ou curl

---

**Version:** 1.0.0  
**Date:** 28 février 2026  
**Auteur:** Webitech  
**Licence:** Propriétaire
