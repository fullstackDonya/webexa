# Guide de Résolution des Erreurs 404 CRM

## Problème Identifié
Les erreurs 404 sur les fichiers API indiquent un problème de configuration du serveur ou de structure des dossiers.

## Solutions à Tester (dans l'ordre)

### 1. Vérification Immédiate
- Ouvrir dans le navigateur : `http://localhost:8888/PP/webitech/WEB/crm/api/test.php`
- Résultat attendu : `{"status":"ok","message":"API endpoint working"}`

### 2. Diagnostic Complet
- Cliquer sur "Diagnostic Complet" dans le dashboard
- Ou ouvrir : `http://localhost:8888/PP/webitech/WEB/crm/api/diagnostic.php`

### 3. Installation Base de Données
- Cliquer sur "Installer CRM" dans le dashboard
- Ou ouvrir : `http://localhost:8888/PP/webitech/WEB/crm/api/install-db.php`

### 4. Vérification Structure
```
/Applications/MAMP/htdocs/PP/webitech/WEB/crm/
├── api/
│   ├── test.php ✓
│   ├── diagnostic.php ✓
│   ├── db-check.php ✓
│   ├── install-db.php ✓
│   ├── kpis.php ✓
│   ├── recent-activities.php ✓
│   ├── sales-data.php ✓
│   ├── source-data.php ✓
│   └── dashboard-data.php ✓
├── assets/
│   ├── css/style.css ✓
│   └── js/
│       ├── dashboard.js ✓
│       └── powerbi-integration.js ✓
├── config/
│   └── database.php ✓
├── includes/
│   ├── auth.php ✓
│   ├── header.php ✓
│   └── sidebar.php ✓
├── database/
│   └── crm_schema.sql ✓
├── index.php ✓
└── .htaccess ✓
```

### 5. Configuration MAMP
- Vérifier que le port Apache est bien 8888
- Vérifier que PHP est activé
- Redémarrer MAMP si nécessaire

### 6. Permissions (si sur macOS/Linux)
```bash
chmod -R 755 /Applications/MAMP/htdocs/PP/webitech/WEB/crm/
chmod -R 644 /Applications/MAMP/htdocs/PP/webitech/WEB/crm/api/*.php
```

### 7. Test des URLs
- Base: `http://localhost:8888/PP/webitech/WEB/crm/`
- API Test: `http://localhost:8888/PP/webitech/WEB/crm/api/test.php`
- Diagnostic: `http://localhost:8888/PP/webitech/WEB/crm/api/diagnostic.php`

## Fonctionnalités Implémentées

### Pages CRM Principales
- ✅ **Dashboard** (`index.php`) - Tableau de bord principal avec KPIs
- ✅ **Contacts** (`contacts.php`) - Gestion des contacts
- ✅ **Ajout Contact** (`contacts-add.php`) - Formulaire d'ajout de contact
- ✅ **Import Contacts** (`contacts-import.php`) - Import en masse de contacts
- ✅ **Leads** (`leads.php`) - Gestion des prospects
- ✅ **Ajout Lead** (`leads-add.php`) - Formulaire d'ajout de prospect
- ✅ **Scoring Leads** (`leads-scoring.php`) - Système de notation des prospects
- ✅ **Opportunités** (`opportunities.php`) - Pipeline des ventes
- ✅ **Prévisions** (`opportunities-forecast.php`) - Prévisions de ventes
- ✅ **Analytics Opportunités** (`opportunities-analytics.php`) - Analyse des opportunités
- ✅ **Clients** (`customers.php`) - Base clients
- ✅ **Ajout Client** (`customers-add.php`) - Formulaire d'ajout de client
- ✅ **IA Insights** (`ai-insights.php`) - Analyses par intelligence artificielle
- ✅ **Campagnes** (`campaigns.php`) - Gestion des campagnes marketing
- ✅ **Campagnes Email** (`campaigns-email.php`) - Campagnes emailing
- ✅ **Automatisation** (`campaigns-automation.php`) - Marketing automation

### Analytics & Reporting
- ✅ **Analytics Ventes** (`analytics-sales.php`) - Analyses des ventes
- ✅ **Analytics Clients** (`analytics-customer.php`) - Analyses comportementales
- ✅ **Analytics Entonnoir** (`analytics-funnel.php`) - Analyse du tunnel de vente

### Intégration Power BI
- ✅ **Power BI Ventes** (`powerbi-sales.php`) - Rapports ventes Power BI
- ✅ **Power BI Clients** (`powerbi-customers.php`) - Rapports clients Power BI
- ✅ **Power BI Performance** (`powerbi-performance.php`) - Tableau de performance
- ✅ **Power BI Prédictions** (`powerbi-predictions.php`) - Analyses prédictives

### Dashboard Intelligent
- ✅ Section diagnostic avec installation automatique
- ✅ Boutons de vérification et installation BD
- ✅ Gestion d'erreurs améliorée en JavaScript
- ✅ Notifications utilisateur
- ✅ Mode démo Power BI

### API Backend
- ✅ Endpoints pour KPIs, activités, ventes, sources
- ✅ Gestion d'erreurs robuste
- ✅ Scripts de diagnostic et installation
- ✅ Configuration CORS

### Base de Données
- ✅ Schéma complet CRM (users, companies, contacts, opportunities, etc.)
- ✅ Installation automatisée
- ✅ Données de test

### Interface Utilisateur
- ✅ Dashboard responsive avec Bootstrap 5
- ✅ Graphiques Chart.js
- ✅ Intégration Power BI (mode démo)
- ✅ Sidebar et header

## Prochaines Étapes
1. Tester l'URL test.php
2. Exécuter le diagnostic complet
3. Installer la base de données si nécessaire
4. Vérifier le fonctionnement du dashboard
5. Configurer Power BI (optionnel)

## Problèmes Courants
- **404 sur API**: Vérifier la configuration Apache/MAMP
- **Erreur DB**: Installer le schéma avec install-db.php
- **Erreurs JS**: Vérifier la console navigateur
- **Power BI**: Mode démo disponible sans configuration Azure
