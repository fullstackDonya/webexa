<?php

require_once __DIR__ . '/../crm/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;

/* --- API & DB helpers --- */
function fetchSales(PDO $pdo): array {
    global $customer_id;
    if (empty($customer_id)) return [];
    $sql = "SELECT s.*, e.first_name, e.last_name, p.product_name AS product_name
            FROM erp_sales s
            JOIN erp_employees e ON e.id = s.employee_id
            JOIN erp_stock p ON p.id = s.product_id
            WHERE s.customer_id = ?
            ORDER BY s.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createSale(PDO $pdo, array $data): int {
    global $customer_id;
    if (empty($customer_id)) {
        throw new InvalidArgumentException('customer_id required in session');
    }
    $sql = "INSERT INTO erp_sales (product_id, employee_id, quantity, total_price, customer_id, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['product_id'],
        $data['employee_id'],
        $data['quantity'],
        $data['total_price'],
        $customer_id,
    ]);
    return (int)$pdo->lastInsertId();
}

function deleteSale(PDO $pdo, int $id): bool {
    global $customer_id;
    if (empty($customer_id)) return false;
    $stmt = $pdo->prepare("DELETE FROM erp_sales WHERE id=? AND customer_id=?");
    return $stmt->execute([$id, $customer_id]);
}

/* --- API routing --- */
$action = $_REQUEST['action'] ?? 'view';
if ($action === 'fetch' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(fetchSales($pdo));
    exit;
}
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'product_id' => (int)($_POST['product_id'] ?? 0),
        'employee_id' => (int)($_POST['employee_id'] ?? 0),
        'quantity' => (int)($_POST['quantity'] ?? 0),
        'total_price' => (float)($_POST['total_price'] ?? 0),
    ];
    if ($data['product_id'] <= 0 || $data['employee_id'] <= 0 || $data['quantity'] <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data']);
        exit;
    }
    try {
        $id = createSale($pdo, $data);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['id' => $id]);
    exit;
}
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid id']);
        exit;
    }
    $ok = deleteSale($pdo, $id);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => (bool)$ok]);
    exit;
}

/* --- Prepare data for view --- */
$sales = fetchSales($pdo);

// employees and products limited to session customer
$employeesStmt = $pdo->prepare("SELECT id, first_name, last_name FROM erp_employees" . ($customer_id ? " WHERE customer_id = ? ORDER BY last_name" : " ORDER BY last_name"));
if ($customer_id) { $employeesStmt->execute([$customer_id]); } else { $employeesStmt->execute(); }
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

$productsStmt = $pdo->prepare("SELECT id, product_name AS name FROM erp_stock" . ($customer_id ? " WHERE customer_id = ? ORDER BY product_name" : " ORDER BY product_name"));
if ($customer_id) { $productsStmt->execute([$customer_id]); } else { $productsStmt->execute(); }
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ventes - ERP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Sales Page Styles */
        .sales-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .sales-header h1 {
            margin: 0;
            font-size: 1.875rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sales-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(59, 130, 246, 0.2);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.revenue .stat-icon {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .stat-card.count .stat-icon {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }

        .stat-card.avg .stat-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .stat-card .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
        }

        /* Table Container */
        .table-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.5);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        /* Modern Table */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .modern-table thead {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        }

        .modern-table thead th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e5e7eb;
        }

        .modern-table thead th:first-child {
            border-radius: 12px 0 0 0;
        }

        .modern-table thead th:last-child {
            border-radius: 0 12px 0 0;
        }

        .modern-table tbody tr {
            transition: all 0.2s;
            border-bottom: 1px solid #f3f4f6;
        }

        .modern-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));
            transform: scale(1.01);
        }

        .modern-table tbody td {
            padding: 1rem;
            font-size: 0.875rem;
            color: #1f2937;
        }

        .product-cell {
            font-weight: 600;
            color: #3b82f6;
        }

        .employee-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .employee-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .quantity-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            background: linear-gradient(135deg, #dbeafe, #e0e7ff);
            color: #1e40af;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .price-cell {
            font-weight: 700;
            color: #059669;
            font-size: 1rem;
        }

        .date-cell {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }

        .btn-icon.delete {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: #dc2626;
        }

        .btn-icon.delete:hover {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            transform: scale(1.1);
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 60;
            animation: fadeIn 0.3s;
        }

        .modal.show {
            display: flex;
        }

        .modal .box {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            width: 500px;
            max-width: 95%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .modal .box h3 {
            margin: 0 0 1.5rem 0;
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 1.25rem;
        }

        .form-row label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 0.875rem;
        }

        .form-row input,
        .form-row select {
            padding: 0.875rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.3s;
        }

        .form-row input:focus,
        .form-row select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }

        .btn-quick-add {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            border: 2px dashed #d1d5db;
            background: white;
            color: #6b7280;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .btn-quick-add:hover {
            border-color: #3b82f6;
            background: linear-gradient(135deg, #dbeafe, #e0e7ff);
            color: #3b82f6;
            transform: scale(1.05);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sales-stats {
                grid-template-columns: 1fr;
            }
            
            .modern-table {
                font-size: 0.75rem;
            }
            
            .modern-table thead th,
            .modern-table tbody td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'erp_nav.php'; ?>
    <div class="container">
        <div class="sales-header">
            <h1><i class="fas fa-chart-line"></i> Gestion des Ventes</h1>
            <div style="display: flex; gap: 10px;">
                <a href="invoices.php" class="btn" style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; text-decoration: none;">
                    <i class="fas fa-file-invoice-dollar"></i> Voir les Factures
                </a>
                <button id="btnAdd" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle Vente
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="sales-stats">
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-label">Chiffre d'affaires</div>
                <div class="stat-value" id="totalRevenue">0 €</div>
            </div>
            <div class="stat-card count">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-label">Nombre de ventes</div>
                <div class="stat-value" id="salesCount">0</div>
            </div>
            <div class="stat-card avg">
                <div class="stat-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="stat-label">Panier moyen</div>
                <div class="stat-value" id="avgSale">0 €</div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <div class="table-header">
                <h2 style="margin: 0; font-size: 1.25rem; color: #111827;">
                    <i class="fas fa-list"></i> Historique des ventes
                </h2>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Rechercher une vente...">
                </div>
            </div>
            
            <div class="table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produit</th>
                            <th>Employé</th>
                            <th>Quantité</th>
                            <th>Prix Total</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody">
                        <?php if (count($sales) > 0): ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr data-id="<?= htmlspecialchars($sale['id']) ?>">
                                    <td><strong>#<?= htmlspecialchars($sale['id']) ?></strong></td>
                                    <td class="product-cell">
                                        <i class="fas fa-box"></i> <?= htmlspecialchars($sale['product_name']) ?>
                                    </td>
                                    <td>
                                        <div class="employee-cell">
                                            <div class="employee-avatar">
                                                <?= strtoupper(substr($sale['first_name'], 0, 1) . substr($sale['last_name'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($sale['first_name'] . ' ' . $sale['last_name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="quantity-badge">
                                            <i class="fas fa-layer-group"></i>
                                            <?= htmlspecialchars($sale['quantity']) ?>
                                        </span>
                                    </td>
                                    <td class="price-cell"><?= number_format($sale['total_price'], 2, ',', ' ') ?> €</td>
                                    <td class="date-cell">
                                        <i class="far fa-calendar"></i>
                                        <?= date('d/m/Y H:i', strtotime($sale['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon delete" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="padding: 3rem; text-align: center;">
                                    <div class="empty-state">
                                        <i class="fas fa-shopping-cart"></i>
                                        <h3>Aucune vente enregistrée</h3>
                                        <p>Commencez par ajouter votre première vente</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- modal form -->
    <div class="modal" id="salesModal" aria-hidden="true">
      <div class="box" role="dialog" aria-modal="true">
        <h3 id="modalTitle"><i class="fas fa-plus-circle"></i> Ajouter une vente</h3>
        <form id="salesForm" novalidate>
          <input type="hidden" id="saleId" name="id" value="">
          <div class="form-row">
            <label for="selectProduct"><i class="fas fa-box"></i> Produit</label>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
              <select id="selectProduct" name="product_id" required style="flex: 1;">
                <option value="">-- Sélectionner un produit --</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" id="btnAddProduct" class="btn-quick-add" title="Ajouter un nouveau produit">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
          <div class="form-row">
            <label for="selectEmployee"><i class="fas fa-user"></i> Employé</label>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
              <select id="selectEmployee" name="employee_id" required style="flex: 1;">
                <option value="">-- Sélectionner un employé --</option>
                <?php foreach ($employees as $e): ?>
                  <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['last_name'].' '.$e['first_name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" id="btnAddEmployee" class="btn-quick-add" title="Ajouter un nouvel employé">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
          <div class="form-row">
            <label for="inputQty"><i class="fas fa-layer-group"></i> Quantité</label>
            <input id="inputQty" name="quantity" type="number" min="1" value="1" required>
          </div>
          <div class="form-row">
            <label for="inputTotal"><i class="fas fa-euro-sign"></i> Prix total (€)</label>
            <input id="inputTotal" name="total_price" type="number" step="0.01" min="0" value="0.00" required>
          </div>
          
          <!-- Options facture -->
          <div class="form-row" style="border-top: 1px solid #e5e7eb; padding-top: 1rem; margin-top: 0.5rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
              <input type="checkbox" id="generateInvoice" name="generate_invoice" value="1" style="width: auto;">
              <span><i class="fas fa-file-invoice"></i> Générer une facture automatiquement</span>
            </label>
          </div>
          
          <div class="form-row" id="vatRateRow" style="display: none;">
            <label for="vatRate"><i class="fas fa-percent"></i> Taux de TVA</label>
            <select id="vatRate" name="vat_rate">
              <option value="20.00" selected>TVA Normale (20%)</option>
              <option value="10.00">TVA Intermédiaire (10%)</option>
              <option value="5.50">TVA Réduite (5,5%)</option>
              <option value="2.10">TVA Super-réduite (2,1%)</option>
              <option value="0.00">TVA Exonérée (0%)</option>
            </select>
          </div>
          
          <div class="form-actions">
            <button type="button" id="btnCancel" class="btn btn-ghost">
                <i class="fas fa-times"></i> Annuler
            </button>
            <button type="submit" id="btnSave" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Ajout Rapide Produit -->
    <div class="modal" id="productModal" aria-hidden="true">
      <div class="box" role="dialog" aria-modal="true" style="max-width: 400px;">
        <h3><i class="fas fa-box"></i> Nouveau Produit</h3>
        <form id="productForm" novalidate>
          <div class="form-row">
            <label for="productName"><i class="fas fa-tag"></i> Nom du produit</label>
            <input id="productName" name="product_name" type="text" placeholder="Ex: Ordinateur portable" required>
          </div>
          <div class="form-row">
            <label for="productPrice"><i class="fas fa-euro-sign"></i> Prix unitaire (€)</label>
            <input id="productPrice" name="price" type="number" step="0.01" min="0" value="0.00">
          </div>
          <div class="form-row">
            <label for="productStock"><i class="fas fa-cubes"></i> Quantité en stock</label>
            <input id="productStock" name="quantity" type="number" min="0" value="0">
          </div>
          <div class="form-actions">
            <button type="button" id="btnCancelProduct" class="btn btn-ghost">
                <i class="fas fa-times"></i> Annuler
            </button>
            <button type="submit" id="btnSaveProduct" class="btn btn-primary">
                <i class="fas fa-save"></i> Créer
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Ajout Rapide Employé -->
    <div class="modal" id="employeeModal" aria-hidden="true">
      <div class="box" role="dialog" aria-modal="true" style="max-width: 400px;">
        <h3><i class="fas fa-user"></i> Nouvel Employé</h3>
        <form id="employeeForm" novalidate>
          <div class="form-row">
            <label for="employeeFirstName"><i class="fas fa-user"></i> Prénom</label>
            <input id="employeeFirstName" name="first_name" type="text" placeholder="Jean" required>
          </div>
          <div class="form-row">
            <label for="employeeLastName"><i class="fas fa-user"></i> Nom</label>
            <input id="employeeLastName" name="last_name" type="text" placeholder="Dupont" required>
          </div>
          <div class="form-row">
            <label for="employeeEmail"><i class="fas fa-envelope"></i> Email</label>
            <input id="employeeEmail" name="email" type="email" placeholder="jean.dupont@example.com">
          </div>
          <div class="form-row">
            <label for="employeePhone"><i class="fas fa-phone"></i> Téléphone</label>
            <input id="employeePhone" name="phone" type="tel" placeholder="+33 6 12 34 56 78">
          </div>
          <div class="form-actions">
            <button type="button" id="btnCancelEmployee" class="btn btn-ghost">
                <i class="fas fa-times"></i> Annuler
            </button>
            <button type="submit" id="btnSaveEmployee" class="btn btn-primary">
                <i class="fas fa-save"></i> Créer
            </button>
          </div>
        </form>
      </div>
    </div>

    <script src="assets/js/sales.js"></script>
    <script>
        // Calculate and display stats
        function updateStats() {
            const rows = document.querySelectorAll('#salesTableBody tr[data-id]');
            let total = 0;
            let count = rows.length;
            
            rows.forEach(row => {
                const priceCell = row.querySelector('.price-cell');
                if (priceCell) {
                    const priceText = priceCell.textContent;
                    const price = parseFloat(priceText.replace(/[^\d,]/g, '').replace(',', '.'));
                    if (!isNaN(price)) {
                        total += price;
                    }
                }
            });
            
            const avg = count > 0 ? total / count : 0;
            
            document.getElementById('totalRevenue').textContent = total.toLocaleString('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' €';
            
            document.getElementById('salesCount').textContent = count;
            
            document.getElementById('avgSale').textContent = avg.toLocaleString('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' €';
        }
        
        // Search functionality
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#salesTableBody tr[data-id]');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Initialize stats
        updateStats();
        
        // Update stats after any table change
        const observer = new MutationObserver(updateStats);
        const tableBody = document.getElementById('salesTableBody');
        if (tableBody) {
            observer.observe(tableBody, { childList: true, subtree: true });
        }
    </script>
</body>
</html>