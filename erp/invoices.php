<?php
session_start();
require_once __DIR__ . '/../crm/config/database.php';

// Vérifier l'authentification
if (!isset($_SESSION['customer_id'])) {
    header('Location: ../crm/index.php');
    exit;
}

$customerId = $_SESSION['customer_id'];

// Récupérer les informations de l'entreprise
$stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les factures
$stmt = $pdo->prepare("
    SELECT i.*, 
           (SELECT COUNT(*) FROM erp_invoice_items WHERE invoice_id = i.id) as items_count,
           (SELECT COUNT(*) FROM erp_invoice_payments WHERE invoice_id = i.id) as payments_count,
           (SELECT SUM(amount) FROM erp_invoice_payments WHERE invoice_id = i.id) as total_paid
    FROM erp_invoices i
    WHERE i.customer_id = ?
    ORDER BY i.issue_date DESC, i.id DESC
");
$stmt->execute([$customerId]);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Factures - <?= htmlspecialchars($customer['name'] ?? 'ERP') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    

   

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px 30px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header h1 i {
            color: #667eea;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card .label {
            color: #718096;
            font-size: 0.875rem;
            margin-bottom: 8px;
        }

        .stat-card .value {
            color: #2d3748;
            font-size: 2rem;
            font-weight: bold;
        }

        .stat-card.primary .value { color: #667eea; }
        .stat-card.success .value { color: #48bb78; }
        .stat-card.warning .value { color: #ed8936; }
        .stat-card.danger .value { color: #f56565; }

        .filters {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filters input,
        .filters select {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            border-color: #667eea;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        thead th {
            padding: 18px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s;
        }

        tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        tbody td {
            padding: 18px;
            color: #2d3748;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge.draft { background: #e2e8f0; color: #4a5568; }
        .badge.sent { background: #bee3f8; color: #2c5282; }
        .badge.paid { background: #c6f6d5; color: #22543d; }
        .badge.unpaid { background: #fed7d7; color: #742a2a; }
        .badge.partial { background: #feebc8; color: #7c2d12; }
        .badge.overdue { background: #fc8181; color: #fff; }
        .badge.cancelled { background: #cbd5e0; color: #1a202c; }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
            color: white;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .modal-header h2 {
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #718096;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #2d3748;
        }

        .invoice-detail {
            padding: 20px 0;
        }

        .invoice-section {
            margin-bottom: 25px;
        }

        .invoice-section h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .invoice-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .invoice-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .invoice-field label {
            color: #718096;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .invoice-field .value {
            color: #2d3748;
            font-size: 1rem;
        }

        .invoice-items {
            margin-top: 20px;
        }

        .invoice-items table {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .invoice-items thead {
            background: #f7fafc;
            color: #2d3748;
        }

        .invoice-items th,
        .invoice-items td {
            padding: 12px;
            text-align: left;
        }

        .invoice-totals {
            margin-top: 20px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 10px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 1rem;
        }

        .total-row.final {
            border-top: 2px solid #667eea;
            padding-top: 15px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 1.3rem;
            color: #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .invoice-line-item {
            padding: 15px;
            margin-bottom: 15px;
            background: #f7fafc;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            position: relative;
        }

        .invoice-line-item .line-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .invoice-line-item .line-header h4 {
            color: #667eea;
            font-size: 0.9rem;
            margin: 0;
        }

        .invoice-line-item .remove-line {
            background: #f56565;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .invoice-line-item .remove-line:hover {
            background: #c53030;
        }

        .invoice-line-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
            gap: 10px;
            align-items: end;
        }

        .invoice-line-grid .form-group {
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .invoice-line-grid {
                grid-template-columns: 1fr;
            }
            .invoice-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 0.85rem;
            }

            thead th,
            tbody td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>
    <?php include 'erp_nav.php'; ?>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-file-invoice-dollar"></i>
                Gestion des Factures
            </h1>
            <div style="display: flex; gap: 10px;">
                <button id="btnTemplates" class="btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                    <i class="fas fa-palette"></i> Modèles de Facture
                </button>
                <button class="btn btn-primary" onclick="openCreateInvoiceModal()">
                    <i class="fas fa-plus"></i> Nouvelle Facture/Devis
                </button>
                <a href="sales.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour aux Ventes
                </a>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="label">Total Factures</div>
                <div class="value" id="statTotal">0</div>
            </div>
            <div class="stat-card success">
                <div class="label">Montant Payé</div>
                <div class="value" id="statPaid">0 €</div>
            </div>
            <div class="stat-card warning">
                <div class="label">En Attente</div>
                <div class="value" id="statUnpaid">0 €</div>
            </div>
            <div class="stat-card danger">
                <div class="label">En Retard</div>
                <div class="value" id="statOverdue">0</div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="filters">
            <input type="text" id="searchInput" placeholder="🔍 Rechercher une facture...">
            <select id="filterStatus">
                <option value="">Tous les statuts</option>
                <option value="draft">Brouillon</option>
                <option value="sent">Envoyée</option>
                <option value="cancelled">Annulée</option>
            </select>
            <select id="filterPayment">
                <option value="">Tous les paiements</option>
                <option value="unpaid">Non payée</option>
                <option value="partial">Partiellement payée</option>
                <option value="paid">Payée</option>
                <option value="overdue">En retard</option>
            </select>
            <input type="date" id="filterDateFrom" placeholder="Date début">
            <input type="date" id="filterDateTo" placeholder="Date fin">
        </div>

        <!-- Tableau des factures -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Échéance</th>
                        <th>Montant TTC</th>
                        <th>Statut</th>
                        <th>Paiement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="invoicesTableBody">
                    <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-file-invoice"></i>
                                <h3>Aucune facture</h3>
                                <p>Les factures créées depuis les ventes apparaîtront ici</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $invoice): ?>
                        <tr data-id="<?= $invoice['id'] ?>">
                            <td><strong><?= htmlspecialchars($invoice['invoice_number']) ?></strong></td>
                            <td><?= htmlspecialchars($invoice['client_name']) ?></td>
                            <td><?= date('d/m/Y', strtotime($invoice['issue_date'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($invoice['due_date'])) ?></td>
                            <td class="price-cell"><strong><?= number_format($invoice['total_ttc'], 2, ',', ' ') ?> €</strong></td>
                            <td><span class="badge <?= $invoice['status'] ?>"><?= ucfirst($invoice['status']) ?></span></td>
                            <td><span class="badge <?= $invoice['payment_status'] ?>"><?= ucfirst($invoice['payment_status']) ?></span></td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-icon btn-primary" onclick="viewInvoice(<?= $invoice['id'] ?>)" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-icon btn-success" onclick="downloadInvoice(<?= $invoice['id'] ?>)" title="Télécharger PDF">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <?php if ($invoice['payment_status'] !== 'paid'): ?>
                                    <button class="btn btn-icon btn-secondary" onclick="recordPayment(<?= $invoice['id'] ?>)" title="Enregistrer paiement">
                                        <i class="fas fa-euro-sign"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Détails Facture -->
    <div id="invoiceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-file-invoice"></i> Détails de la Facture</h2>
                <button class="close-modal" onclick="closeInvoiceModal()">&times;</button>
            </div>
            <div id="invoiceDetailContent" class="invoice-detail">
                <!-- Contenu dynamique -->
            </div>
        </div>
    </div>

    <!-- Modal Création Facture/Devis -->
    <div id="createInvoiceModal" class="modal">
        <div class="modal-content" style="max-width: 1000px;">
            <div class="modal-header">
                <h2><i class="fas fa-file-invoice-dollar"></i> Nouvelle Facture/Devis</h2>
                <button class="close-modal" onclick="closeCreateInvoiceModal()">&times;</button>
            </div>
            <form id="createInvoiceForm" onsubmit="submitCreateInvoice(event)">
                <!-- Type de document -->
                <div class="invoice-section">
                    <h3>Type de document</h3>
                    <div class="form-group">
                        <label>Type *</label>
                        <select id="invoiceType" required>
                            <option value="invoice">Facture</option>
                            <option value="quote">Devis</option>
                            <option value="proforma">Facture Proforma</option>
                            <option value="credit_note">Avoir</option>
                        </select>
                    </div>
                </div>

                <!-- Informations du client -->
                <div class="invoice-section">
                    <h3>Informations Client</h3>
                    <div class="invoice-grid">
                        <div class="form-group">
                            <label>Nom du client *</label>
                            <input type="text" id="clientName" required placeholder="Nom de l'entreprise ou du particulier">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="clientEmail" placeholder="email@client.com">
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="tel" id="clientPhone" placeholder="01 23 45 67 89">
                        </div>
                        <div class="form-group">
                            <label>SIRET</label>
                            <input type="text" id="clientSiret" placeholder="123 456 789 00010">
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Adresse</label>
                            <input type="text" id="clientAddress" placeholder="Rue, numéro">
                        </div>
                        <div class="form-group">
                            <label>Code postal</label>
                            <input type="text" id="clientPostalCode" placeholder="75001">
                        </div>
                        <div class="form-group">
                            <label>Ville</label>
                            <input type="text" id="clientCity" placeholder="Paris">
                        </div>
                        <div class="form-group">
                            <label>Pays</label>
                            <input type="text" id="clientCountry" value="France">
                        </div>
                        <div class="form-group">
                            <label>N° TVA intracommunautaire</label>
                            <input type="text" id="clientVatNumber" placeholder="FR 12 345 678 901">
                        </div>
                    </div>
                </div>

                <!-- Dates -->
                <div class="invoice-section">
                    <h3>Dates et échéances</h3>
                    <div class="invoice-grid">
                        <div class="form-group">
                            <label>Date d'émission *</label>
                            <input type="date" id="issueDate" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Date d'échéance</label>
                            <input type="date" id="dueDate">
                        </div>
                        <div class="form-group">
                            <label>Date de livraison/prestation</label>
                            <input type="date" id="deliveryDate">
                        </div>
                        <div class="form-group">
                            <label>Conditions de paiement</label>
                            <select id="paymentTerms">
                                <option value="Paiement comptant">Paiement comptant</option>
                                <option value="Paiement à 15 jours">Paiement à 15 jours</option>
                                <option value="Paiement à 30 jours" selected>Paiement à 30 jours</option>
                                <option value="Paiement à 45 jours">Paiement à 45 jours</option>
                                <option value="Paiement à 60 jours">Paiement à 60 jours</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Lignes de facture -->
                <div class="invoice-section">
                    <h3>Lignes de produits/services</h3>
                    <div id="invoiceLines">
                        <!-- Les lignes seront ajoutées dynamiquement -->
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addInvoiceLine()" style="margin-top: 10px;">
                        <i class="fas fa-plus"></i> Ajouter une ligne
                    </button>
                </div>

                <!-- Totaux -->
                <div class="invoice-section">
                    <div class="invoice-totals">
                        <div class="total-row">
                            <span>Total HT:</span>
                            <span id="displayTotalHT"><strong>0,00 €</strong></span>
                        </div>
                        <div class="total-row">
                            <span>Total TVA:</span>
                            <span id="displayTotalVAT"><strong>0,00 €</strong></span>
                        </div>
                        <div class="total-row final">
                            <span>Total TTC:</span>
                            <span id="displayTotalTTC">0,00 €</span>
                        </div>
                    </div>
                </div>

                <!-- Notes et conditions -->
                <div class="invoice-section">
                    <h3>Notes et conditions</h3>
                    <div class="form-group">
                        <label>Conditions d'escompte</label>
                        <input type="text" id="discountTerms" placeholder="Ex: 2% d'escompte si paiement sous 8 jours">
                    </div>
                    <div class="form-group">
                        <label>Notes internes (non visibles sur la facture)</label>
                        <textarea id="internalNotes" rows="2" placeholder="Notes pour usage interne..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Notes client (visibles sur la facture)</label>
                        <textarea id="clientNotes" rows="3" placeholder="Notes visibles sur la facture..."></textarea>
                    </div>
                </div>

                <!-- Boutons -->
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateInvoiceModal()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer le document
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Paiement -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-money-bill-wave"></i> Enregistrer un Paiement</h2>
                <button class="close-modal" onclick="closePaymentModal()">&times;</button>
            </div>
            <form id="paymentForm" onsubmit="submitPayment(event)">
                <input type="hidden" id="paymentInvoiceId">
                
                <div class="form-group">
                    <label>Montant du paiement (€) *</label>
                    <input type="number" id="paymentAmount" step="0.01" min="0.01" required>
                </div>

                <div class="form-group">
                    <label>Date du paiement *</label>
                    <input type="date" id="paymentDate" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label>Méthode de paiement *</label>
                    <select id="paymentMethod" required>
                        <option value="transfer">Virement</option>
                        <option value="card">Carte bancaire</option>
                        <option value="check">Chèque</option>
                        <option value="cash">Espèces</option>
                        <option value="paypal">PayPal</option>
                        <option value="other">Autre</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Référence (optionnel)</label>
                    <input type="text" id="paymentReference" placeholder="Ex: Virement XXXX">
                </div>

                <div class="form-group">
                    <label>Notes (optionnel)</label>
                    <textarea id="paymentNotes" rows="3" placeholder="Notes supplémentaires..."></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Enregistrer le Paiement
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Données des factures
        const invoicesData = <?= json_encode($invoices) ?>;

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            updateStatistics();
            setupFilters();
        });

        // Mise à jour des statistiques
        function updateStatistics() {
            const stats = {
                total: 0,
                paid: 0,
                unpaid: 0,
                overdue: 0
            };

            invoicesData.forEach(invoice => {
                stats.total++;
                
                if (invoice.payment_status === 'paid') {
                    stats.paid += parseFloat(invoice.total_ttc);
                } else if (invoice.payment_status === 'unpaid' || invoice.payment_status === 'partial') {
                    const remaining = parseFloat(invoice.total_ttc) - (parseFloat(invoice.total_paid) || 0);
                    stats.unpaid += remaining;
                }
                
                if (invoice.payment_status === 'overdue') {
                    stats.overdue++;
                }
            });

            document.getElementById('statTotal').textContent = stats.total;
            document.getElementById('statPaid').textContent = stats.paid.toFixed(2).replace('.', ',') + ' €';
            document.getElementById('statUnpaid').textContent = stats.unpaid.toFixed(2).replace('.', ',') + ' €';
            document.getElementById('statOverdue').textContent = stats.overdue;
        }

        // Configuration des filtres
        function setupFilters() {
            const searchInput = document.getElementById('searchInput');
            const filterStatus = document.getElementById('filterStatus');
            const filterPayment = document.getElementById('filterPayment');
            const filterDateFrom = document.getElementById('filterDateFrom');
            const filterDateTo = document.getElementById('filterDateTo');

            [searchInput, filterStatus, filterPayment, filterDateFrom, filterDateTo].forEach(element => {
                element.addEventListener('change', applyFilters);
                element.addEventListener('input', applyFilters);
            });
        }

        // Appliquer les filtres
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value;
            const paymentFilter = document.getElementById('filterPayment').value;
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo = document.getElementById('filterDateTo').value;

            const rows = document.querySelectorAll('#invoicesTableBody tr[data-id]');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const invoiceId = row.getAttribute('data-id');
                const invoice = invoicesData.find(inv => inv.id == invoiceId);

                let show = true;

                // Filtre recherche
                if (searchTerm && !text.includes(searchTerm)) {
                    show = false;
                }

                // Filtre statut
                if (statusFilter && invoice.status !== statusFilter) {
                    show = false;
                }

                // Filtre paiement
                if (paymentFilter && invoice.payment_status !== paymentFilter) {
                    show = false;
                }

                // Filtre date début
                if (dateFrom && invoice.issue_date < dateFrom) {
                    show = false;
                }

                // Filtre date fin
                if (dateTo && invoice.issue_date > dateTo) {
                    show = false;
                }

                row.style.display = show ? '' : 'none';
            });
        }

        // Voir les détails d'une facture
        function viewInvoice(invoiceId) {
            fetch(`api/invoices.php?action=get&id=${invoiceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayInvoiceDetails(data.invoice);
                        document.getElementById('invoiceModal').classList.add('active');
                    } else {
                        alert('Erreur: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement de la facture');
                });
        }

        // Afficher les détails de la facture
        function displayInvoiceDetails(invoice) {
            const vatDetails = typeof invoice.vat_details === 'string' 
                ? JSON.parse(invoice.vat_details) 
                : invoice.vat_details;

            let html = `
                <div class="invoice-section">
                    <h3>Informations générales</h3>
                    <div class="invoice-grid">
                        <div class="invoice-field">
                            <label>Numéro</label>
                            <div class="value"><strong>${invoice.invoice_number}</strong></div>
                        </div>
                        <div class="invoice-field">
                            <label>Type</label>
                            <div class="value">${invoice.invoice_type}</div>
                        </div>
                        <div class="invoice-field">
                            <label>Date d'émission</label>
                            <div class="value">${formatDate(invoice.issue_date)}</div>
                        </div>
                        <div class="invoice-field">
                            <label>Date d'échéance</label>
                            <div class="value">${formatDate(invoice.due_date)}</div>
                        </div>
                        <div class="invoice-field">
                            <label>Statut</label>
                            <div class="value"><span class="badge ${invoice.status}">${invoice.status}</span></div>
                        </div>
                        <div class="invoice-field">
                            <label>Paiement</label>
                            <div class="value"><span class="badge ${invoice.payment_status}">${invoice.payment_status}</span></div>
                        </div>
                    </div>
                </div>

                <div class="invoice-section">
                    <h3>Client</h3>
                    <div class="invoice-grid">
                        <div class="invoice-field">
                            <label>Nom</label>
                            <div class="value">${invoice.client_name}</div>
                        </div>
                        <div class="invoice-field">
                            <label>Email</label>
                            <div class="value">${invoice.client_email || '-'}</div>
                        </div>
                        <div class="invoice-field">
                            <label>Adresse</label>
                            <div class="value">${invoice.client_address || '-'}</div>
                        </div>
                        <div class="invoice-field">
                            <label>Ville</label>
                            <div class="value">${invoice.client_postal_code || ''} ${invoice.client_city || ''}</div>
                        </div>
                        ${invoice.client_siret ? `
                        <div class="invoice-field">
                            <label>SIRET</label>
                            <div class="value">${invoice.client_siret}</div>
                        </div>
                        ` : ''}
                        ${invoice.client_vat_number ? `
                        <div class="invoice-field">
                            <label>N° TVA</label>
                            <div class="value">${invoice.client_vat_number}</div>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <div class="invoice-section">
                    <h3>Lignes de facture</h3>
                    <div class="invoice-items">
                        <table>
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Qté</th>
                                    <th>PU HT</th>
                                    <th>TVA</th>
                                    <th>Total TTC</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${invoice.items.map(item => `
                                    <tr>
                                        <td>${item.description}</td>
                                        <td>${item.quantity} ${item.unit}</td>
                                        <td>${formatPrice(item.unit_price_ht)} €</td>
                                        <td>${item.vat_rate}%</td>
                                        <td><strong>${formatPrice(item.total_ttc)} €</strong></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="invoice-totals">
                    <div class="total-row">
                        <span>Total HT:</span>
                        <span><strong>${formatPrice(invoice.total_ht)} €</strong></span>
                    </div>
                    ${vatDetails && vatDetails.length > 0 ? vatDetails.map(vat => `
                        <div class="total-row">
                            <span>TVA ${vat.rate}%:</span>
                            <span>${formatPrice(vat.amount)} €</span>
                        </div>
                    `).join('') : ''}
                    <div class="total-row final">
                        <span>Total TTC:</span>
                        <span>${formatPrice(invoice.total_ttc)} €</span>
                    </div>
                    ${invoice.paid_amount > 0 ? `
                        <div class="total-row" style="color: #48bb78;">
                            <span>Montant payé:</span>
                            <span>${formatPrice(invoice.paid_amount)} €</span>
                        </div>
                        <div class="total-row" style="color: #ed8936;">
                            <span>Reste à payer:</span>
                            <span>${formatPrice(invoice.total_ttc - invoice.paid_amount)} €</span>
                        </div>
                    ` : ''}
                </div>

                ${invoice.payments && invoice.payments.length > 0 ? `
                <div class="invoice-section">
                    <h3>Historique des paiements</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Méthode</th>
                                <th>Référence</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${invoice.payments.map(payment => `
                                <tr>
                                    <td>${formatDate(payment.payment_date)}</td>
                                    <td><strong>${formatPrice(payment.amount)} €</strong></td>
                                    <td>${payment.payment_method}</td>
                                    <td>${payment.reference || '-'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                ` : ''}

                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 25px;">
                    <button class="btn btn-success" onclick="downloadInvoice(${invoice.id})">
                        <i class="fas fa-download"></i> Télécharger PDF
                    </button>
                    ${invoice.payment_status !== 'paid' ? `
                        <button class="btn btn-primary" onclick="closeInvoiceModal(); recordPayment(${invoice.id})">
                            <i class="fas fa-euro-sign"></i> Enregistrer Paiement
                        </button>
                    ` : ''}
                </div>
            `;

            document.getElementById('invoiceDetailContent').innerHTML = html;
        }

        // Télécharger la facture en PDF
        function downloadInvoice(invoiceId) {
            window.open('invoice-pdf.php?id=' + invoiceId, '_blank');
        }

        // Ouvrir le modal de paiement
        function recordPayment(invoiceId) {
            const invoice = invoicesData.find(inv => inv.id == invoiceId);
            
            if (invoice) {
                const remaining = parseFloat(invoice.total_ttc) - (parseFloat(invoice.total_paid) || 0);
                document.getElementById('paymentInvoiceId').value = invoiceId;
                document.getElementById('paymentAmount').value = remaining.toFixed(2);
                document.getElementById('paymentAmount').max = remaining.toFixed(2);
                document.getElementById('paymentModal').classList.add('active');
            }
        }

        // Soumettre le paiement
        function submitPayment(event) {
            event.preventDefault();

            const formData = new FormData();
            formData.append('action', 'payment');
            formData.append('id', document.getElementById('paymentInvoiceId').value);
            formData.append('amount', document.getElementById('paymentAmount').value);
            formData.append('payment_date', document.getElementById('paymentDate').value);
            formData.append('payment_method', document.getElementById('paymentMethod').value);
            formData.append('reference', document.getElementById('paymentReference').value);
            formData.append('notes', document.getElementById('paymentNotes').value);

            fetch('api/invoices.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Paiement enregistré avec succès !');
                    closePaymentModal();
                    location.reload();
                } else {
                    alert('Erreur: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'enregistrement du paiement');
            });
        }

        // Fermer les modals
        function closeInvoiceModal() {
            document.getElementById('invoiceModal').classList.remove('active');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
            document.getElementById('paymentForm').reset();
        }

        function closeTemplatesModal() {
            document.getElementById('templatesModal').classList.remove('active');
        }

        // Utilitaires
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR');
        }

        function formatPrice(price) {
            return parseFloat(price).toFixed(2).replace('.', ',');
        }

        // Fermer les modals en cliquant en dehors
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });

        // =============================================
        // GESTION CRÉATION FACTURE/DEVIS
        // =============================================

        let lineCounter = 0;
        const VAT_RATES = [0, 2.1, 5.5, 10, 20]; // Taux de TVA disponibles

        // Ouvrir le modal de création
        function openCreateInvoiceModal() {
            document.getElementById('createInvoiceModal').classList.add('active');
            document.getElementById('invoiceLines').innerHTML = '';
            lineCounter = 0;
            addInvoiceLine(); // Ajouter une première ligne
            calculateTotals();
        }

        // Fermer le modal de création
        function closeCreateInvoiceModal() {
            document.getElementById('createInvoiceModal').classList.remove('active');
            document.getElementById('createInvoiceForm').reset();
        }

        // Ajouter une ligne de facture
        function addInvoiceLine() {
            lineCounter++;
            const lineId = `line-${lineCounter}`;
            
            const lineHtml = `
                <div class="invoice-line-item" id="${lineId}">
                    <div class="line-header">
                        <h4><i class="fas fa-box"></i> Ligne ${lineCounter}</h4>
                        <button type="button" class="remove-line" onclick="removeLine('${lineId}')">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </div>
                    <div class="invoice-line-grid">
                        <div class="form-group">
                            <label>Description *</label>
                            <input type="text" class="line-description" required placeholder="Description du produit/service">
                        </div>
                        <div class="form-group">
                            <label>Quantité *</label>
                            <input type="number" class="line-quantity" step="0.01" min="0.01" value="1" required onchange="calculateTotals()">
                        </div>
                        <div class="form-group">
                            <label>Prix unitaire HT *</label>
                            <input type="number" class="line-price" step="0.01" min="0" required placeholder="0.00" onchange="calculateTotals()">
                        </div>
                        <div class="form-group">
                            <label>TVA (%) *</label>
                            <select class="line-vat" required onchange="calculateTotals()">
                                ${VAT_RATES.map(rate => `<option value="${rate}" ${rate === 20 ? 'selected' : ''}>${rate}%</option>`).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Total TTC</label>
                            <input type="text" class="line-total" readonly style="background: #e2e8f0; font-weight: bold;" value="0,00 €">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 10px;">
                        <label>Unité</label>
                        <input type="text" class="line-unit" placeholder="pièce, heure, jour, kg..." value="pièce">
                    </div>
                </div>
            `;
            
            document.getElementById('invoiceLines').insertAdjacentHTML('beforeend', lineHtml);
            calculateTotals();
        }

        // Supprimer une ligne
        function removeLine(lineId) {
            const line = document.getElementById(lineId);
            if (line) {
                line.remove();
                calculateTotals();
                // Renumérote les lignes
                document.querySelectorAll('.invoice-line-item').forEach((item, index) => {
                    item.querySelector('.line-header h4').innerHTML = `<i class="fas fa-box"></i> Ligne ${index + 1}`;
                });
            }
        }

        // Calculer les totaux
        function calculateTotals() {
            let totalHT = 0;
            let totalVAT = 0;
            
            document.querySelectorAll('.invoice-line-item').forEach(line => {
                const quantity = parseFloat(line.querySelector('.line-quantity').value) || 0;
                const price = parseFloat(line.querySelector('.line-price').value) || 0;
                const vatRate = parseFloat(line.querySelector('.line-vat').value) || 0;
                
                const lineHT = quantity * price;
                const lineVAT = lineHT * (vatRate / 100);
                const lineTTC = lineHT + lineVAT;
                
                line.querySelector('.line-total').value = formatPrice(lineTTC) + ' €';
                
                totalHT += lineHT;
                totalVAT += lineVAT;
            });
            
            const totalTTC = totalHT + totalVAT;
            
            document.getElementById('displayTotalHT').innerHTML = '<strong>' + formatPrice(totalHT) + ' €</strong>';
            document.getElementById('displayTotalVAT').innerHTML = '<strong>' + formatPrice(totalVAT) + ' €</strong>';
            document.getElementById('displayTotalTTC').innerHTML = formatPrice(totalTTC) + ' €';
        }

        // Soumettre la création de facture
        function submitCreateInvoice(event) {
            event.preventDefault();
            
            // Collecter les données
            const lines = [];
            let isValid = true;
            
            document.querySelectorAll('.invoice-line-item').forEach(line => {
                const description = line.querySelector('.line-description').value.trim();
                const quantity = parseFloat(line.querySelector('.line-quantity').value);
                const price = parseFloat(line.querySelector('.line-price').value);
                const vat = parseFloat(line.querySelector('.line-vat').value);
                const unit = line.querySelector('.line-unit').value.trim() || 'pièce';
                
                if (!description || quantity <= 0 || price < 0) {
                    isValid = false;
                    return;
                }
                
                lines.push({
                    description: description,
                    quantity: quantity,
                    unit_price_ht: price,
                    vat_rate: vat,
                    unit: unit
                });
            });
            
            if (!isValid || lines.length === 0) {
                alert('Veuillez remplir correctement toutes les lignes de facture.');
                return;
            }
            
            // Calculer la date d'échéance si non définie
            const issueDate = document.getElementById('issueDate').value;
            let dueDate = document.getElementById('dueDate').value;
            
            if (!dueDate && issueDate) {
                const date = new Date(issueDate);
                const paymentTerms = document.getElementById('paymentTerms').value;
                const days = parseInt(paymentTerms.match(/\d+/)?.[0]) || 30;
                date.setDate(date.getDate() + days);
                dueDate = date.toISOString().split('T')[0];
            }
            
            const invoiceData = {
                invoice_type: document.getElementById('invoiceType').value,
                issue_date: issueDate,
                due_date: dueDate,
                delivery_date: document.getElementById('deliveryDate').value || null,
                client_name: document.getElementById('clientName').value,
                client_email: document.getElementById('clientEmail').value || '',
                client_phone: document.getElementById('clientPhone').value || '',
                client_address: document.getElementById('clientAddress').value || '',
                client_postal_code: document.getElementById('clientPostalCode').value || '',
                client_city: document.getElementById('clientCity').value || '',
                client_country: document.getElementById('clientCountry').value || 'France',
                client_siret: document.getElementById('clientSiret').value || '',
                client_vat_number: document.getElementById('clientVatNumber').value || '',
                payment_terms: document.getElementById('paymentTerms').value,
                discount_terms: document.getElementById('discountTerms').value || null,
                notes: document.getElementById('internalNotes').value || null,
                client_notes: document.getElementById('clientNotes').value || null,
                items: lines
            };
            
            // Afficher un indicateur de chargement
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';
            
            // Envoyer la requête
            fetch('api/invoices.php?action=custom', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(invoiceData)
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                
                if (data.success) {
                    alert('Document créé avec succès !');
                    closeCreateInvoiceModal();
                    location.reload();
                } else {
                    alert('Erreur: ' + data.error);
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                console.error('Erreur:', error);
                alert('Erreur lors de la création du document');
            });
        }

        // Calculer automatiquement la date d'échéance
        document.addEventListener('DOMContentLoaded', function() {
            const paymentTermsSelect = document.getElementById('paymentTerms');
            const issueDateInput = document.getElementById('issueDate');
            const dueDateInput = document.getElementById('dueDate');
            
            if (paymentTermsSelect && issueDateInput && dueDateInput) {
                paymentTermsSelect.addEventListener('change', updateDueDate);
                issueDateInput.addEventListener('change', updateDueDate);
            }
            
            function updateDueDate() {
                const issueDate = issueDateInput.value;
                const paymentTerms = paymentTermsSelect.value;
                
                if (issueDate && paymentTerms) {
                    const match = paymentTerms.match(/\d+/);
                    if (match) {
                        const days = parseInt(match[0]);
                        const date = new Date(issueDate);
                        date.setDate(date.getDate() + days);
                        dueDateInput.value = date.toISOString().split('T')[0];
                    }
                }
            }
        });

        // ***** GESTION DES MODÈLES DE FACTURES *****
        
        let currentTemplate = 'modern';
        
        // Ouvrir le modal des templates
        document.getElementById('btnTemplates')?.addEventListener('click', function() {
            loadTemplates();
            document.getElementById('templatesModal').classList.add('active');
        });
        
        // Fermer le modal des templates en cliquant en dehors
        document.getElementById('templatesModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeTemplatesModal();
            }
        });
        
        // Charger les templates disponibles
        function loadTemplates() {
            fetch('api/invoice-settings.php?action=get')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentTemplate = data.settings.template_name || 'modern';
                        displayTemplates(data.templates, currentTemplate);
                    }
                })
                .catch(error => console.error('Erreur chargement templates:', error));
        }
        
        // Afficher les templates
        function displayTemplates(templates, selected) {
            const grid = document.getElementById('templatesGrid');
            grid.innerHTML = '';
            
            Object.entries(templates).forEach(([key, template]) => {
                const card = document.createElement('div');
                card.className = 'template-card' + (key === selected ? ' selected' : '');
                card.style.cssText = `
                    border: 3px solid ${key === selected ? template.primary_color : '#e5e7eb'};
                    border-radius: 12px;
                    padding: 1.5rem;
                    cursor: pointer;
                    transition: all 0.3s;
                    background: ${key === selected ? 'linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1))' : 'white'};
                `;
                
                card.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <div>
                            <h4 style="margin: 0; font-size: 1.125rem; color: ${template.primary_color};">
                                <i class="fas fa-file-invoice"></i> ${template.name}
                            </h4>
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #6b7280;">
                                ${template.description}
                            </p>
                        </div>
                        ${key === selected ? '<i class="fas fa-check-circle" style="color: ' + template.primary_color + '; font-size: 1.5rem;"></i>' : ''}
                    </div>
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <div style="width: 20px; height: 20px; border-radius: 50%; background: ${template.primary_color};"></div>
                        <div style="width: 20px; height: 20px; border-radius: 50%; background: ${template.secondary_color};"></div>
                    </div>
                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                        <button class="btn btn-secondary" style="flex: 1; font-size: 0.75rem; padding: 0.5rem;" onclick="previewTemplate('${key}')">
                            <i class="fas fa-eye"></i> Aperçu
                        </button>
                        <button class="btn btn-primary" style="flex: 1; font-size: 0.75rem; padding: 0.5rem;" onclick="selectTemplate('${key}')">
                            <i class="fas fa-check"></i> Choisir
                        </button>
                    </div>
                `;
                
                grid.appendChild(card);
            });
        }
        
        // Prévisualiser un template
        window.previewTemplate = function(templateKey) {
            window.open('api/invoice-settings.php?action=preview&template=' + templateKey, '_blank', 'width=900,height=800');
        };
        
        // Sélectionner un template
        window.selectTemplate = function(templateKey) {
            const formData = new FormData();
            formData.append('action', 'update-template');
            formData.append('template_name', templateKey);
            
            fetch('api/invoice-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentTemplate = templateKey;
                    loadTemplates(); // Recharger pour mettre à jour la sélection
                    
                    // Notification de succès
                    const notification = document.createElement('div');
                    notification.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: linear-gradient(135deg, #48bb78, #38a169);
                        color: white;
                        padding: 1rem 1.5rem;
                        border-radius: 12px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                        z-index: 10000;
                        animation: slideIn 0.3s;
                    `;
                    notification.innerHTML = `
                        <i class="fas fa-check-circle"></i> 
                        <strong>Modèle mis à jour !</strong><br>
                        <span style="font-size: 0.875rem;">Vos nouvelles factures utiliseront ce modèle.</span>
                    `;
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                } else {
                    alert('Erreur: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la mise à jour du modèle');
            });
        };
    </script>

    <!-- Modal Sélection de Modèles de Facture -->
    <div class="modal" id="templatesModal" aria-hidden="true">
      <div class="modal-content" role="dialog" aria-modal="true" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
          <h2><i class="fas fa-palette"></i> Choisir un Modèle de Facture</h2>
          <button class="close-modal" onclick="closeTemplatesModal()">&times;</button>
        </div>
        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 2rem;">
          Sélectionnez le style de facture qui correspond à votre entreprise. Le modèle choisi sera utilisé pour toutes vos factures.
        </p>
        
        <div id="templatesGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
          <!-- Templates will be loaded here by JavaScript -->
        </div>
      </div>
    </div>

</body>
</html>
