# 🚀 Guide de Démarrage Rapide - ERP Webitech v2.0

## Installation et Configuration

### 1. Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Apache/MAMP avec mod_rewrite activé
- CRM Webitech déjà installé et fonctionnel

### 2. Installation

#### Étape 1 : Vérifier la structure des fichiers
Assurez-vous que tous les fichiers sont en place :
```
erp/
├── index.php                 ✓ Dashboard
├── missions.php              ✓ Module Missions (NOUVEAU)
├── shifts.php                ✓ Planning
├── employees.php             ✓ Personnel
├── companies.php             ✓ Entreprises
├── sales.php                 ✓ Ventes
├── payroll.php               ✓ Paies
├── reports.php               ✓ Rapports
├── erp_nav.php               ✓ Navigation
├── README.md                 ✓ Documentation
├── API_DOCUMENTATION.md      ✓ Doc API
├── api/
│   └── sync.php              ✓ API de synchronisation (NOUVEAU)
├── assets/
│   ├── css/
│   │   └── style.css         ✓ Styles modernes (NOUVEAU)
│   └── js/
│       └── sync.js           ✓ Client sync (NOUVEAU)
├── includes/
│   └── missions.php          ✓ Logique missions (NOUVEAU)
└── migrations/
    └── 003_erp_crm_sync.sql  ✓ Migration DB (NOUVEAU)
```

#### Étape 2 : Exécuter la migration de base de données
Connectez-vous à MySQL et exécutez :
```bash
mysql -u root -p votre_database < migrations/003_erp_crm_sync.sql
```

Ou via phpMyAdmin :
1. Ouvrir phpMyAdmin
2. Sélectionner votre base de données
3. Onglet "Importer"
4. Sélectionner `migrations/003_erp_crm_sync.sql`
5. Cliquer "Exécuter"

#### Étape 3 : Vérifier la base de données
```sql
-- Vérifier que les tables sont créées
SHOW TABLES LIKE 'erp_%';

-- Devrait afficher :
-- erp_shifts
-- erp_sales
-- erp_sync_logs
-- erp_employees
-- erp_payrolls
```

#### Étape 4 : Configurer les permissions
```bash
# Donner les permissions sur le dossier
chmod -R 755 /path/to/erp
chmod -R 777 /path/to/erp/logs  # Si vous créez un dossier logs
```

### 3. Premier Démarrage

#### Étape 1 : Accéder au Dashboard
Ouvrez votre navigateur et allez sur :
```
http://localhost/erp/index.php
```

Vous devriez voir :
- ✅ Design moderne avec glassmorphism
- ✅ 4 KPIs animés
- ✅ Actions rapides
- ✅ Sidebar avec nouveau menu

#### Étape 2 : Vérifier la synchronisation
1. Cliquer sur "Missions & Projets" dans le menu
2. Vous devriez voir vos missions du CRM
3. En bas à droite, l'indicateur de sync devrait apparaître

#### Étape 3 : Tester la synchronisation manuelle
Ouvrir la console du navigateur (F12) et taper :
```javascript
// Vérifier que le sync manager est chargé
console.log(window.syncManager);

// Forcer une synchronisation
syncManager.forceSync();

// Obtenir le statut
console.log(syncManager.getSyncStatus());
```

### 4. Vérification des Fonctionnalités

#### Module Missions
```
✓ Affichage des missions CRM
✓ Onglet Planning ERP
✓ Filtres avancés
✓ KPIs en temps réel
✓ Synchronisation automatique (5 min)
```

#### Dashboard
```
✓ KPIs animés avec hover effects
✓ Actions rapides
✓ Lien vers CRM
✓ Nouvelles embauches
✓ Dernières fiches de paie
```

#### Planning (Shifts)
```
✓ Vue calendrier moderne
✓ Mode employé / Mode société
✓ Création de créneaux
✓ Synchronisation avec missions
```

#### API de Synchronisation
```
✓ GET /api/sync.php?action=sync_missions
✓ GET /api/sync.php?action=sync_shifts
✓ GET /api/sync.php?action=sync_companies
✓ GET /api/sync.php?action=sync_sales
✓ GET /api/sync.php?action=get_stats
✓ POST /api/sync.php?action=create_shift_from_mission
```

### 5. Tests de Fonctionnement

#### Test 1 : Créer une mission dans le CRM
1. Aller dans le CRM → Missions
2. Créer une nouvelle mission
3. Retourner dans ERP → Missions & Projets
4. Attendre max 5 minutes OU forcer sync via console
5. ✅ La mission devrait apparaître

#### Test 2 : Créer un shift dans l'ERP
1. Aller dans Planning
2. Créer un nouveau créneau
3. Sauvegarder
4. ✅ Devrait apparaître immédiatement

#### Test 3 : API de synchronisation
Tester avec cURL :
```bash
# Test sync missions
curl "http://localhost/erp/api/sync.php?action=sync_missions" \
  -H "Cookie: PHPSESSID=votre_session_id"

# Test stats
curl "http://localhost/erp/api/sync.php?action=get_stats" \
  -H "Cookie: PHPSESSID=votre_session_id"
```

### 6. Dépannage

#### Problème : Modules stock/inventaire toujours visibles
```bash
# Vérifier qu'ils sont bien supprimés
ls -la stock.php inventory.php
# Devrait retourner : No such file or directory

# Si présents, les supprimer manuellement
rm -f stock.php inventory.php
```

#### Problème : Erreur 404 sur /api/sync.php
```bash
# Vérifier que le fichier existe
ls -la api/sync.php

# Vérifier les permissions
chmod 644 api/sync.php
```

#### Problème : Synchronisation ne fonctionne pas
1. Ouvrir console navigateur (F12)
2. Onglet Console
3. Chercher les erreurs
4. Vérifier que `window.syncManager` existe
5. Vérifier la session PHP :
```javascript
fetch('/erp/api/sync.php?action=get_stats')
  .then(res => res.json())
  .then(console.log);
```

#### Problème : Design pas appliqué
```bash
# Vider le cache du navigateur
# Ctrl+Shift+Delete (Chrome/Firefox)

# Vérifier que style.css est chargé
curl http://localhost/erp/assets/css/style.css | head -20
```

#### Problème : Erreurs SQL
```sql
-- Vérifier les tables
SHOW TABLES;

-- Vérifier la structure de erp_shifts
DESCRIBE erp_shifts;

-- Tester une requête de sync
SELECT COUNT(*) FROM missions;
SELECT COUNT(*) FROM erp_shifts;
```

### 7. Optimisations Post-Installation

#### Activer la compression GZIP
Dans `.htaccess` :
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript
</IfModule>
```

#### Activer le cache du navigateur
Dans `.htaccess` :
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

#### Optimiser MySQL
```sql
-- Analyser les tables
ANALYZE TABLE missions, erp_shifts, erp_employees, companies;

-- Optimiser les tables
OPTIMIZE TABLE missions, erp_shifts, erp_employees, companies;
```

### 8. Configuration Avancée

#### Changer l'intervalle de synchronisation
Dans `missions.php`, modifier :
```javascript
// De 5 minutes à 2 minutes
const syncManager = new ERPSyncManager({
    syncInterval: 2 * 60 * 1000 // 2 minutes
});
```

#### Activer le mode debug
Dans `missions.php` :
```javascript
const syncManager = new ERPSyncManager({
    debug: true  // Active les logs console
});
```

#### Désactiver la sync auto (manuel uniquement)
```javascript
const syncManager = new ERPSyncManager({
    autoSync: false
});

// Sync manuelle uniquement
document.getElementById('btnSync').addEventListener('click', () => {
    syncManager.forceSync();
});
```

### 9. Maintenance

#### Logs de synchronisation
```sql
-- Voir les dernières synchronisations
SELECT * FROM erp_sync_logs 
ORDER BY created_at DESC 
LIMIT 50;

-- Stats par type
SELECT 
    sync_type, 
    COUNT(*) as total,
    SUM(records_affected) as total_records
FROM erp_sync_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY sync_type;
```

#### Nettoyer les anciens logs (> 90 jours)
```sql
DELETE FROM erp_sync_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

#### Backup de la base
```bash
# Backup complet
mysqldump -u root -p votre_database > backup_$(date +%Y%m%d).sql

# Backup des tables ERP uniquement
mysqldump -u root -p votre_database \
    erp_shifts erp_employees erp_payrolls erp_sales erp_sync_logs \
    > backup_erp_$(date +%Y%m%d).sql
```

### 10. Support et Documentation

#### Documentation complète
- 📖 README.md - Vue d'ensemble et features
- 📡 API_DOCUMENTATION.md - Documentation API complète
- 🗂️ Ce fichier (QUICKSTART.md) - Guide de démarrage

#### Logs et Debug
```javascript
// Activer les logs détaillés
localStorage.setItem('erp_debug', 'true');

// Voir le statut de sync
console.log(syncManager.getSyncStatus());

// Voir les écouteurs actifs
console.log(syncManager.listeners);
```

#### Ressources
- Font Awesome Icons : https://fontawesome.com/icons
- CSS Gradients : https://cssgradient.io/
- Glassmorphism : https://css.glass/

### 11. Checklist de Vérification Finale

Avant de mettre en production :

```
□ Migration SQL exécutée avec succès
□ Tous les fichiers en place (voir structure ci-dessus)
□ Permissions fichiers configurées
□ Dashboard accessible et design moderne
□ Module Missions affiche les données CRM
□ Synchronisation automatique fonctionne
□ API répond correctement (test cURL)
□ Pas de fichiers stock/inventory
□ Navigation mise à jour (Missions visible)
□ Sidebar moderne avec lien CRM
□ KPIs animés sur le dashboard
□ Planning modernisé avec glassmorphism
□ Console navigateur sans erreur
□ Tests effectués sur Chrome/Firefox/Safari
□ Backup de la base effectué
□ Documentation lue et comprise
```

### 12. Prochaines Étapes

Une fois l'installation validée :

1. **Personnaliser les KPIs** selon vos besoins
2. **Ajuster les filtres** dans Missions
3. **Configurer les rapports** personnalisés
4. **Former les utilisateurs** aux nouvelles fonctionnalités
5. **Planifier la migration** des anciennes données
6. **Monitorer** les performances et la synchronisation

---

## 🎉 Félicitations !

Votre ERP Webitech v2.0 est maintenant opérationnel avec :
- ✅ Design moderne 2026
- ✅ Synchronisation ERP ↔ CRM
- ✅ Module Missions intégré
- ✅ API REST fonctionnelle
- ✅ Interface responsive

**Besoin d'aide ?**
- 📧 Email : support@webitech.com
- 📖 Documentation complète dans README.md
- 💬 Console debug : `syncManager.log('test')`

Bonne utilisation ! 🚀
