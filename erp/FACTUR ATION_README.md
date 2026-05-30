# 📄 Système de Facturation Conforme - Documentation Complète

## 🎯 Vue d'ensemble

Système complet de **ventes et facturation** conforme aux **lois françaises 2026** avec intégration comptable automatique.

---

## ✅ Conformité Légale

### Réglementations respectées :
- ✅ **Article 242 nonies A du CGI** : Numérotation séquentielle obligatoire sans rupture
- ✅ **Décret 2022-1299** : Facturation électronique obligatoire
- ✅ **Mentions obligatoires** :
  - Pénalités de retard (10% par défaut)
  - Indemnité forfaitaire de recouvrement (40€ minimum)
  - Conditions d'escompte
  - SIRET/SIREN émetteur
  - Numéro TVA intracommunautaire
- ✅ **Anti-backdating** : Impossible d'antidater une facture
- ✅ **Conservation 10 ans** : Audit trail complet
- ✅ **Format Factur-X** : Prêt pour PDF + XML EN 16931

---

## 📦 Fichiers Créés

### 1. Base de Données

#### **`erp/migrations/004_create_invoices_system.sql`** (580 lignes)
Tables créées :
- `erp_invoices` : Factures principales avec tous les champs légaux
- `erp_invoice_items` : Lignes de facture détaillées
- `erp_invoice_sequences` : Numérotation séquentielle obligatoire
- `erp_vat_rates` : Taux de TVA français (20%, 10%, 5.5%, 2.1%, 0%)
- `erp_invoice_audit` : Historique complet des modifications
- `erp_invoice_payments` : Paiements reçus

**Triggers automatiques** :
1. `before_invoice_insert` : Validation anti-backdating + calcul échéance
2. `after_invoice_item_insert/update/delete` : Recalcul automatique des totaux
3. `after_payment_insert` : Mise à jour du statut de paiement

**Vue dashboard** :
- `erp_invoices_dashboard` : Statistiques par période

**Commande d'installation** :
```bash
mysql -u root -proot webitech_crm < erp/migrations/004_create_invoices_system.sql
```

#### **`erp/migrations/005_accounting_integration.sql`** (350 lignes)
Intégration comptable automatique :
- Trigger `after_invoice_paid` : Création automatique de transaction bancaire
- Procédure `sync_paid_invoices_to_accounting()` : Synchronisation manuelle
- Vue `erp_accounting_revenue` : CA par période
- Vue `erp_vat_declaration` : Déclaration de TVA
- Fonctions `get_annual_revenue()` et `get_vat_collected()`
- Événement quotidien : Mise à jour des factures en retard
- Table `erp_invoice_documents` : Liaison avec documents scannés

**Commande d'installation** :
```bash
mysql -u root -proot webitech_crm < erp/migrations/005_accounting_integration.sql
```

---

### 2. Backend PHP

#### **`erp/api/InvoiceGenerator.php`** (730 lignes)
Classe principale de génération de factures.

**Méthodes principales** :
```php
$invoiceGen = new InvoiceGenerator($pdo, $customerId);

// Générer numéro séquentiel
$number = $invoiceGen->generateInvoiceNumber('FA'); // FA2026-0001

// Créer facture depuis une vente
$invoiceId = $invoiceGen->createInvoiceFromSale($saleId, ['vat_rate' => 20.00]);

// Créer facture personnalisée
$invoiceId = $invoiceGen->createCustomInvoice($invoiceData, $items);

// Envoyer facture
$invoiceGen->sendInvoice($invoiceId);

// Enregistrer paiement
$paymentId = $invoiceGen->recordPayment($invoiceId, $paymentData);

// Annuler facture
$invoiceGen->cancelInvoice($invoiceId, $reason);

// Récupérer facture
$invoice = $invoiceGen->getInvoice($invoiceId);

// Lister factures
$invoices = $invoiceGen->listInvoices(['status' => 'sent']);

// Statistiques
$stats = $invoiceGen->getStatistics('month');
```

**Calculs automatiques** :
- Total HT par ligne (quantité × prix - remise)
- TVA par ligne (HT × taux)
- Total TTC (HT + TVA)
- Regroupement TVA par taux
- Validation des montants

#### **`erp/api/invoices.php`** (260 lignes)
API REST complète pour la gestion des factures.

**Endpoints disponibles** :

| Méthode | URL | Description |
|---------|-----|-------------|
| GET | `/api/invoices.php` | Liste des factures (filtres: status, payment_status, date) |
| GET | `/api/invoices.php?action=get&id=X` | Détails d'une facture |
| GET | `/api/invoices.php?action=stats&period=month` | Statistiques (day/week/month/year) |
| GET | `/api/invoices.php?action=vat-rates` | Taux de TVA disponibles |
| POST | `/api/invoices.php?action=from-sale` | Créer facture depuis vente |
| POST | `/api/invoices.php?action=custom` | Créer facture personnalisée |
| POST | `/api/invoices.php?action=send&id=X` | Envoyer facture |
| POST | `/api/invoices.php?action=payment&id=X` | Enregistrer paiement |
| POST | `/api/invoices.php?action=cancel&id=X` | Annuler facture |
| POST | `/api/invoices.php?action=update-overdue` | Mettre à jour factures en retard (cron) |

**Exemples d'utilisation** :

```javascript
// Créer facture depuis vente
const fd = new FormData();
fd.append('sale_id', 123);
fd.append('vat_rate', 20.00);
fetch('api/invoices.php?action=from-sale', { method: 'POST', body: fd });

// Enregistrer paiement
fd.append('amount', 1200.00);
fd.append('payment_method', 'transfer');
fd.append('payment_date', '2026-02-20');
fetch('api/invoices.php?action=payment&id=456', { method: 'POST', body: fd });

// Statistiques
fetch('api/invoices.php?action=stats&period=month')
  .then(r => r.json())
  .then(data => console.log(data.stats));
```

---

### 3. Frontend

#### **`erp/sales.php`** (modifié)
Interface de gestion des ventes avec génération automatique de facture.

**Nouveautés ajoutées** :
- ✅ Checkbox "Générer une facture automatiquement"
- ✅ Sélecteur de taux de TVA (20%, 10%, 5.5%, 2.1%, 0%)
- ✅ Badge "Facturée" sur les ventes avec facture
- ✅ Bouton "Voir facture" pour accéder directement à la facture

**Processus de création** :
1. Remplir le formulaire de vente
2. Cocher "Générer une facture"
3. Choisir le taux de TVA applicable
4. Enregistrer → Vente + Facture créées automatiquement

#### **`erp/assets/js/sales.js`** (modifié)
JavaScript pour l'interface des ventes.

**Fonctionnalités ajoutées** :
- Affichage/masquage du sélecteur TVA selon checkbox
- Création automatique de facture après la vente
- Notification de succès/erreur
- Indicateur de chargement pendant la création
- Affichage du badge "Facturée" dans le tableau

---

## 🚀 Utilisation

### 1. Installation

```bash
# 1. Exécuter les migrations SQL
cd /Applications/MAMP/htdocs/PP/webitech/WEB/erp/migrations

mysql -u root -proot webitech_crm < 004_create_invoices_system.sql
mysql -u root -proot webitech_crm < 005_accounting_integration.sql

# 2. Vérifier que tout est OK
mysql -u root -proot webitech_crm -e "
  SELECT COUNT(*) as factures FROM erp_invoices;
  SELECT COUNT(*) as taux_tva FROM erp_vat_rates;
  SHOW TRIGGERS LIKE '%invoice%';
"
```

### 2. Créer une vente avec facture

1. Aller sur : `https://localhost/erp/sales.php`
2. Cliquer sur "Nouvelle Vente"
3. Remplir :
   - Produit
   - Employé
   - Quantité
   - Prix total
4. Cocher "Générer une facture automatiquement"
5. Choisir le taux de TVA (20% par défaut)
6. Enregistrer

**Résultat** :
- ✅ Vente créée dans `erp_sales`
- ✅ Facture créée dans `erp_invoices` avec numéro séquentiel (FA2026-0001)
- ✅ Ligne de facture dans `erp_invoice_items` avec calculs HT/TVA/TTC
- ✅ Lien vente ↔ facture (`erp_sales.invoice_id`)
- ✅ Audit enregistré dans `erp_invoice_audit`

### 3. Enregistrer un paiement

**Via API** :
```javascript
const fd = new FormData();
fd.append('amount', 1200.00);
fd.append('payment_method', 'transfer');
fd.append('payment_date', '2026-02-20');
fd.append('reference', 'VIR-123456');

fetch('api/invoices.php?action=payment&id=1', {
  method: 'POST',
  body: fd
}).then(r => r.json());
```

**Résultat** :
- ✅ Paiement enregistré dans `erp_invoice_payments`
- ✅ Facture marquée "paid" automatiquement (trigger)
- ✅ Transaction bancaire créée dans `erp_bank_transactions` (trigger)
- ✅ Audit enregistré

### 4. Synchroniser la comptabilité

**Synchroniser manuellement toutes les factures payées** :
```sql
CALL sync_paid_invoices_to_accounting(1); -- 1 = customer_id
```

**Vérifier les transactions créées** :
```sql
SELECT * FROM erp_bank_transactions
WHERE category = 'Ventes de produits'
ORDER BY transaction_date DESC;
```

### 5. Requêtes comptables utiles

**Chiffre d'affaires du mois** :
```sql
SELECT * FROM erp_accounting_revenue 
WHERE customer_id = 1 
  AND period = '2026-02'
LIMIT 1;
```

**Déclaration de TVA** :
```sql
SELECT vat_rate, 
       SUM(total_base_ht) as base_ht,
       SUM(total_vat_amount) as tva_collectee
FROM erp_vat_declaration
WHERE customer_id = 1 
  AND period = '2026-02'
GROUP BY vat_rate;
```

**CA annuel** :
```sql
SELECT get_annual_revenue(1, 2026) as ca_2026;
```

**Factures en retard** :
```sql
SELECT invoice_number, client_name, total_ttc, due_date,
       DATEDIFF(CURDATE(), due_date) as jours_retard
FROM erp_invoices
WHERE customer_id = 1
  AND payment_status = 'overdue'
ORDER BY due_date;
```

**Top 10 clients** :
```sql
SELECT client_name, 
       COUNT(*) as nb_factures,
       SUM(total_ttc) as ca_total
FROM erp_invoices
WHERE customer_id = 1
  AND payment_status = 'paid'
  AND YEAR(paid_date) = 2026
GROUP BY client_name
ORDER BY ca_total DESC
LIMIT 10;
```

---

## 📊 Structure des Données

### Table `erp_invoices` (principale)

**Champs essentiels** :
```sql
invoice_number      VARCHAR(50)      -- FA2026-0001 (unique, séquentiel)
invoice_type        ENUM             -- sale, credit_note, advance, proforma
issue_date          DATE             -- Date d'émission
due_date            DATE             -- Date d'échéance (issue_date + 30j par défaut)

client_name         VARCHAR(255)     -- Nom du client (obligatoire)
client_siret        VARCHAR(14)      -- SIRET si entreprise française
client_vat_number   VARCHAR(20)      -- N° TVA intracommunautaire
client_address      TEXT             -- Adresse complète

total_ht            DECIMAL(15,2)    -- Total HT (calculé auto)
total_tva           DECIMAL(15,2)    -- Total TVA (calculé auto)
total_ttc           DECIMAL(15,2)    -- Total TTC = HT + TVA (calculé auto)
vat_details         JSON             -- [{rate: 20, base_ht: 1000, amount: 200}]

payment_status      ENUM             -- unpaid, partial, paid, overdue, cancelled
paid_amount         DECIMAL(15,2)    -- Montant déjà payé
paid_date           DATE             -- Date de paiement complet

late_fee_rate       DECIMAL(5,2)     -- 10.00% (mention obligatoire)
recovery_indemnity  DECIMAL(10,2)    -- 40.00€ (mention obligatoire)

status              ENUM             -- draft, sent, viewed, paid, cancelled, archived
```

### Table `erp_invoice_items` (lignes)

**Champs** :
```sql
invoice_id          INT              -- Référence à la facture
description         TEXT             -- Description du produit/service
quantity            DECIMAL(10,3)    -- Quantité
unit                VARCHAR(20)      -- Unité (unité, heure, jour, kg...)
unit_price_ht       DECIMAL(15,2)    -- Prix unitaire HT

discount_rate       DECIMAL(5,2)     -- Taux de remise (%)
discount_amount     DECIMAL(15,2)    -- Montant de remise

total_ht            DECIMAL(15,2)    -- Total HT ligne
vat_rate            DECIMAL(5,2)     -- Taux TVA (20, 10, 5.5, 2.1, 0)
vat_amount          DECIMAL(15,2)    -- Montant TVA
total_ttc           DECIMAL(15,2)    -- Total TTC ligne
```

---

## 🔧 Personnalisation

### Modifier le préfixe des factures

```php
// Par défaut: FA2026-0001
$number = $invoiceGen->generateInvoiceNumber('FA');

// Personnalisé: FACT2026-0001
$number = $invoiceGen->generateInvoiceNumber('FACT');

// Personnalisé: INV2026-0001
$number = $invoiceGen->generateInvoiceNumber('INV');
```

### Modifier les mentions légales par défaut

```sql
-- Dans la table erp_invoices, modifier DEFAULT values :
ALTER TABLE erp_invoices 
  ALTER COLUMN late_fee_rate SET DEFAULT 12.00,
  ALTER COLUMN recovery_indemnity SET DEFAULT 50.00;
```

Ou au moment de la création :

```php
$invoiceData = [
  'late_fee_rate' => 12.00,      // Augmenter à 12%
  'recovery_indemnity' => 50.00,  // Augmenter à 50€
  'payment_terms' => 'Paiement à 45 jours',
  // ...
];
```

### Ajouter un nouveau taux de TVA

```sql
INSERT INTO erp_vat_rates (rate, label, description, country, is_active)
VALUES (8.50, 'Corse', 'Taux particulier Corse', 'FR', 1);
```

---

## 🔐 Sécurité et Conformité

### Anti-backdating
Le trigger `before_invoice_insert` **EMPÊCHE** la création de factures antidatées :
```sql
IF NEW.issue_date < CURDATE() AND NEW.status != 'draft' THEN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT = 'Date d\'émission ne peut pas être antérieure';
END IF;
```

### Numérotation sans rupture
La table `erp_invoice_sequences` garantit une numérotation **séquentielle sans trou** :
- Verrouillage `FOR UPDATE` pendant la génération
- Incrémentation atomique
- Historique complet des numéros générés

### Audit trail
Toutes les modifications sont tracées dans `erp_invoice_audit` :
- Action effectuée (created, updated, sent, paid, cancelled)
- Utilisateur ayant effectué l'action
- Anciennes et nouvelles valeurs (JSON)
- Adresse IP et User-Agent
- Date/heure exacte

### Conservation 10 ans
Les factures ne sont **JAMAIS supprimées**, seulement archivées :
```sql
UPDATE erp_invoices SET status = 'archived' WHERE ...;
-- Jamais: DELETE FROM erp_invoices ...
```

---

## 📈 Monitoring et Maintenance

### Dashboard statistiques

```javascript
// Stats du mois en cours
fetch('api/invoices.php?action=stats&period=month')
  .then(r => r.json())
  .then(data => {
    console.log('Factures totales:', data.stats.total_invoices);
    console.log('Montant total:', data.stats.total_amount);
    console.log('Montant payé:', data.stats.paid_amount);
    console.log('En attente:', data.stats.outstanding_amount);
    console.log('En retard:', data.stats.overdue_count);
  });
```

### Cron pour factures en retard

**Automatique** (événement MySQL activé) :
```sql
-- S'exécute tous les jours automatiquement
-- Voir: CREATE EVENT update_overdue_invoices_daily
```

**Manuel** (via API) :
```bash
curl -X POST "https://votre-site.com/erp/api/invoices.php?action=update-overdue"
```

**Manuel** (SQL direct) :
```sql
UPDATE erp_invoices
SET payment_status = 'overdue'
WHERE payment_status IN ('unpaid', 'partial')
  AND due_date < CURDATE()
  AND status NOT IN ('cancelled', 'archived');
```

---

## 🌐 Déploiement en Production

### 1. Copier les fichiers sur Hostinger

```bash
# Via FTP/SFTP, copier:
/erp/migrations/004_create_invoices_system.sql
/erp/migrations/005_accounting_integration.sql
/erp/api/InvoiceGenerator.php
/erp/api/invoices.php
/erp/sales.php (modifié)
/erp/assets/js/sales.js (modifié)
```

### 2. Exécuter les migrations

**Via phpMyAdmin** :
1. Se connecter à phpMyAdmin Hostinger
2. Sélectionner la base `webitech_crm`
3. Onglet "SQL"
4. Copier/coller le contenu de `004_create_invoices_system.sql`
5. Exécuter
6. Répéter avec `005_accounting_integration.sql`

**Via SSH** (si disponible) :
```bash
ssh user@votre-serveur
cd /chemin/vers/erp/migrations
mysql -u db_user -p db_name < 004_create_invoices_system.sql
mysql -u db_user -p db_name < 005_accounting_integration.sql
```

### 3. Vérifier

```sql
-- Vérifier les tables
SHOW TABLES LIKE 'erp_invoice%';

-- Vérifier les triggers
SHOW TRIGGERS WHERE `Table` = 'erp_invoices';

-- Vérifier les taux de TVA
SELECT * FROM erp_vat_rates;

-- Tester la génération de numéro
INSERT INTO erp_invoice_sequences (customer_id, invoice_year, current_number)
VALUES (1, 2026, 0);
```

### 4. Configuration du cron

**Si Hostinger supporte les cron jobs** :
```bash
# Tous les jours à 2h du matin
0 2 * * * curl -X POST "https://webitech.fr/erp/api/invoices.php?action=update-overdue"
```

---

## 🎓 Exemples Complets

### Exemple 1 : Créer une vente et générer une facture

```javascript
// 1. Créer la vente
const fdSale = new FormData();
fdSale.append('product_id', 5);
fdSale.append('employee_id', 3);
fdSale.append('quantity', 2);
fdSale.append('total_price', 1000.00);

const resSale = await fetch('sales.php?action=create', {
  method: 'POST',
  body: fdSale
}).then(r => r.json());

// 2. Créer la facture
const fdInvoice = new FormData();
fdInvoice.append('sale_id', resSale.id);
fdInvoice.append('vat_rate', 20.00);

const resInvoice = await fetch('api/invoices.php?action=from-sale', {
  method: 'POST',
  body: fdInvoice
}).then(r => r.json());

console.log('Facture créée:', resInvoice.invoice_id);
```

### Exemple 2 : Créer une facture personnalisée

```javascript
const data = {
  client_name: 'ACME Corp',
  client_siret: '12345678901234',
  client_address: '123 Rue de la Paix',
  client_city: 'Paris',
  client_postal_code: '75001',
  payment_terms: 'Paiement à 30 jours',
  items: [
    {
      description: 'Développement site web',
      quantity: 1,
      unit: 'forfait',
      unit_price_ht: 5000.00,
      vat_rate: 20.00,
      discount_rate: 0
    },
    {
      description: 'Hébergement annuel',
      quantity: 12,
      unit: 'mois',
      unit_price_ht: 50.00,
      vat_rate: 20.00,
      discount_rate: 10 // 10% de remise
    }
  ]
};

const res = await fetch('api/invoices.php?action=custom', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(data)
}).then(r => r.json());

console.log('Facture créée:', res.invoice_id);
// Montant total: 5000 + (600 - 60) = 5540€ HT
// TVA 20%: 1108€
// Total TTC: 6648€
```

### Exemple 3 : Workflow complet

```php
<?php
// 1. Créer l'instance
$invoiceGen = new InvoiceGenerator($pdo, $customerId);

// 2. Créer une facture depuis une vente
$invoiceId = $invoiceGen->createInvoiceFromSale($saleId);

// 3. Envoyer la facture au client
$invoiceGen->sendInvoice($invoiceId);

// 4. Plus tard: enregistrer un paiement
$paymentData = [
    'amount' => 6648.00,
    'payment_method' => 'transfer',
    'payment_date' => date('Y-m-d'),
    'reference' => 'VIR-2026-02-20'
];
$paymentId = $invoiceGen->recordPayment($invoiceId, $paymentData);

// 5. Vérifier le statut
$invoice = $invoiceGen->getInvoice($invoiceId);
echo "Statut: " . $invoice['payment_status']; // "paid"

// 6. La transaction bancaire a été créée automatiquement (trigger)
// 7. Le chiffre d'affaires est inclus dans les vues comptables
?>
```

---

## 📞 Support

Pour toute question ou problème :
1. Vérifier les logs : `logs/document_scanner_errors.log` (ou créer un log dédié)
2. Vérifier les triggers SQL : `SHOW TRIGGERS;`
3. Vérifier les contraintes : `SHOW CREATE TABLE erp_invoices;`
4. Tester l'API : `curl -X GET "http://localhost/erp/api/invoices.php"`

---

## 🎉 Conclusion

Vous disposez maintenant d'un **système de facturation professionnel et conforme** aux lois françaises 2026 avec :

✅ Numérotation séquentielle automatique sans rupture  
✅ Calculs automatiques HT/TVA/TTC  
✅ Mentions légales obligatoires  
✅ Anti-backdating  
✅ Audit trail complet (10 ans)  
✅ Intégration comptable automatique  
✅ API REST complète  
✅ Interface utilisateur moderne  
✅ Synchronisation bancaire  
✅ Déclaration de TVA facilitée  

**Prêt pour la production !** 🚀
