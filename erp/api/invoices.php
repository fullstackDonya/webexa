<?php
/**
 * API REST pour la gestion des factures
 * 
 * Endpoints disponibles:
 * - GET    /invoices               Liste des factures
 * - GET    /invoices/:id           Détails d'une facture
 * - POST   /invoices/from-sale     Créer facture depuis une vente
 * - POST   /invoices/custom        Créer facture personnalisée
 * - POST   /invoices/:id/send      Envoyer une facture
 * - POST   /invoices/:id/payment   Enregistrer un paiement
 * - POST   /invoices/:id/cancel    Annuler une facture
 * - GET    /invoices/stats         Statistiques
 * - GET    /vat-rates              Liste des taux de TVA
 * 
 * @author  Webitech CRM
 * @version 1.0
 */

require_once __DIR__ . '/../../crm/config/database.php';
require_once __DIR__ . '/InvoiceGenerator.php';

// Configuration
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customerId = $_SESSION['customer_id'] ?? null;

if (!$customerId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Non authentifié'
    ]);
    exit;
}

// Initialiser le générateur de factures
try {
    $invoiceGen = new InvoiceGenerator($pdo, $customerId);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur initialisation: ' . $e->getMessage()
    ]);
    exit;
}

// Router
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;

try {
    
    // =========================
    // GET - Liste des factures
    // =========================
    if ($method === 'GET' && empty($action)) {
        $filters = [
            'status' => $_GET['status'] ?? null,
            'payment_status' => $_GET['payment_status'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null
        ];
        
        $invoices = $invoiceGen->listInvoices(array_filter($filters));
        
        echo json_encode([
            'success' => true,
            'invoices' => $invoices,
            'count' => count($invoices)
        ]);
        exit;
    }
    
    // =========================
    // GET - Détails d'une facture
    // =========================
    if ($method === 'GET' && $action === 'get' && $id) {
        $invoice = $invoiceGen->getInvoice($id);
        
        if (!$invoice) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Facture non trouvée'
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'invoice' => $invoice
        ]);
        exit;
    }
    
    // =========================
    // GET - Statistiques
    // =========================
    if ($method === 'GET' && $action === 'stats') {
        $period = $_GET['period'] ?? 'month'; // day, week, month, year, all
        
        $stats = $invoiceGen->getStatistics($period);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'period' => $period
        ]);
        exit;
    }
    
    // =========================
    // GET - Taux de TVA
    // =========================
    if ($method === 'GET' && $action === 'vat-rates') {
        $stmt = $pdo->prepare("
            SELECT id, rate, label, description, country, is_active
            FROM erp_vat_rates
            WHERE is_active = 1 AND country = 'FR'
            ORDER BY rate DESC
        ");
        $stmt->execute();
        $vatRates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'vat_rates' => $vatRates
        ]);
        exit;
    }
    
    // =========================
    // POST - Créer facture depuis une vente
    // =========================
    if ($method === 'POST' && $action === 'from-sale') {
        $saleId = (int)($_POST['sale_id'] ?? 0);
        
        if ($saleId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'ID de vente invalide'
            ]);
            exit;
        }
        
        $options = [
            'vat_rate' => isset($_POST['vat_rate']) ? (float)$_POST['vat_rate'] : null
        ];
        
        $invoiceId = $invoiceGen->createInvoiceFromSale($saleId, array_filter($options));
        
        echo json_encode([
            'success' => true,
            'message' => 'Facture créée avec succès',
            'invoice_id' => $invoiceId
        ]);
        exit;
    }
    
    // =========================
    // POST - Créer facture personnalisée
    // =========================
    if ($method === 'POST' && $action === 'custom') {
        // Récupérer les données JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            // Fallback sur $_POST
            $data = $_POST;
        }
        
        if (empty($data['client_name'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Nom du client requis'
            ]);
            exit;
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Lignes de facture requises'
            ]);
            exit;
        }
        
        // Valider chaque ligne
        foreach ($data['items'] as $item) {
            if (empty($item['description']) || !isset($item['quantity']) || 
                !isset($item['unit_price_ht']) || !isset($item['vat_rate'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Données de ligne incomplètes'
                ]);
                exit;
            }
        }
        
        $invoiceData = [
            'invoice_type' => $data['invoice_type'] ?? 'sale',
            'issue_date' => $data['issue_date'] ?? date('Y-m-d'),
            'due_date' => $data['due_date'] ?? null,
            'delivery_date' => $data['delivery_date'] ?? null,
            'client_type' => $data['client_type'] ?? 'company',
            'client_company_id' => $data['client_company_id'] ?? null,
            'client_name' => $data['client_name'],
            'client_email' => $data['client_email'] ?? '',
            'client_address' => $data['client_address'] ?? '',
            'client_city' => $data['client_city'] ?? '',
            'client_postal_code' => $data['client_postal_code'] ?? '',
            'client_country' => $data['client_country'] ?? 'France',
            'client_siret' => $data['client_siret'] ?? '',
            'client_vat_number' => $data['client_vat_number'] ?? '',
            'client_phone' => $data['client_phone'] ?? '',
            'payment_terms' => $data['payment_terms'] ?? 'Paiement à 30 jours',
            'payment_method' => $data['payment_method'] ?? 'transfer',
            'late_fee_rate' => $data['late_fee_rate'] ?? 10.00,
            'recovery_indemnity' => $data['recovery_indemnity'] ?? 40.00,
            'discount_terms' => $data['discount_terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'client_notes' => $data['client_notes'] ?? null,
            'created_by' => $_SESSION['user_id'] ?? null
        ];
        
        $invoiceId = $invoiceGen->createCustomInvoice($invoiceData, $data['items']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Facture créée avec succès',
            'invoice_id' => $invoiceId
        ]);
        exit;
    }
    
    // =========================
    // POST - Envoyer une facture
    // =========================
    if ($method === 'POST' && $action === 'send' && $id) {
        $success = $invoiceGen->sendInvoice($id);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Facture envoyée avec succès'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Impossible d\'envoyer la facture (vérifiez le statut)'
            ]);
        }
        exit;
    }
    
    // =========================
    // POST - Enregistrer un paiement
    // =========================
    if ($method === 'POST' && $action === 'payment' && $id) {
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        
        if ($amount <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Montant invalide'
            ]);
            exit;
        }
        
        $paymentData = [
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'amount' => $amount,
            'payment_method' => $_POST['payment_method'] ?? 'transfer',
            'reference' => $_POST['reference'] ?? null,
            'bank_account_id' => isset($_POST['bank_account_id']) ? (int)$_POST['bank_account_id'] : null,
            'notes' => $_POST['notes'] ?? null,
            'recorded_by' => $_SESSION['user_id'] ?? null
        ];
        
        $paymentId = $invoiceGen->recordPayment($id, $paymentData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Paiement enregistré avec succès',
            'payment_id' => $paymentId
        ]);
        exit;
    }
    
    // =========================
    // POST - Annuler une facture
    // =========================
    if ($method === 'POST' && $action === 'cancel' && $id) {
        $reason = $_POST['reason'] ?? 'Annulation sans motif';
        
        $success = $invoiceGen->cancelInvoice($id, $reason);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Facture annulée avec succès'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Impossible d\'annuler la facture'
            ]);
        }
        exit;
    }
    
    // =========================
    // POST - Mettre à jour les factures en retard (cron)
    // =========================
    if ($method === 'POST' && $action === 'update-overdue') {
        $count = $invoiceGen->updateOverdueInvoices();
        
        echo json_encode([
            'success' => true,
            'message' => "$count facture(s) marquée(s) en retard"
        ]);
        exit;
    }
    
    // Route non trouvée
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Endpoint non trouvé',
        'method' => $method,
        'action' => $action
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
