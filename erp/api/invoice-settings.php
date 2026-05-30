<?php
/**
 * Gestion des paramètres de factures (modèles, couleurs, etc.)
 */

session_start();
require_once __DIR__ . '/../../crm/config/database.php';
require_once __DIR__ . '/InvoiceTemplates.php';

// Vérifier l'authentification
if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$customerId = $_SESSION['customer_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

try {
    // Récupérer les paramètres actuels
    if ($method === 'GET' && $action === 'get') {
        $stmt = $pdo->prepare("
            SELECT * FROM erp_invoice_settings
            WHERE customer_id = ?
        ");
        $stmt->execute([$customerId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$settings) {
            // Créer des paramètres par défaut
            $stmt = $pdo->prepare("
                INSERT INTO erp_invoice_settings (customer_id, template_name)
                VALUES (?, 'modern')
            ");
            $stmt->execute([$customerId]);
            
            $settings = [
                'customer_id' => $customerId,
                'template_name' => 'modern',
                'primary_color' => '#667eea',
                'secondary_color' => '#764ba2',
                'show_logo' => true,
                'show_qr_code' => false
            ];
        }
        
        echo json_encode([
            'success' => true,
            'settings' => $settings,
            'templates' => InvoiceTemplates::getAvailableTemplates()
        ]);
        exit;
    }
    
    // Mettre à jour le modèle
    if ($method === 'POST' && $action === 'update-template') {
        $templateName = $_POST['template_name'] ?? 'modern';
        
        // Vérifier que le template existe
        $templates = InvoiceTemplates::getAvailableTemplates();
        if (!isset($templates[$templateName])) {
            throw new Exception('Modèle de facture invalide');
        }
        
        // Mettre à jour ou créer
        $stmt = $pdo->prepare("
            INSERT INTO erp_invoice_settings (customer_id, template_name, primary_color, secondary_color)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                template_name = VALUES(template_name),
                primary_color = VALUES(primary_color),
                secondary_color = VALUES(secondary_color),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->execute([
            $customerId,
            $templateName,
            $templates[$templateName]['primary_color'],
            $templates[$templateName]['secondary_color']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Modèle de facture mis à jour'
        ]);
        exit;
    }
    
    // Mettre à jour les paramètres avancés
    if ($method === 'POST' && $action === 'update-settings') {
        $stmt = $pdo->prepare("
            UPDATE erp_invoice_settings
            SET logo_url = ?,
                header_text = ?,
                footer_text = ?,
                show_logo = ?,
                show_qr_code = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE customer_id = ?
        ");
        
        $stmt->execute([
            $_POST['logo_url'] ?? null,
            $_POST['header_text'] ?? null,
            $_POST['footer_text'] ?? null,
            isset($_POST['show_logo']) ? 1 : 0,
            isset($_POST['show_qr_code']) ? 1 : 0,
            $customerId
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Paramètres mis à jour'
        ]);
        exit;
    }
    
    // Aperçu du modèle
    if ($method === 'GET' && $action === 'preview') {
        $templateName = $_GET['template'] ?? 'modern';
        
        // Données fictives pour l'aperçu
        $invoice = [
            'invoice_number' => 'FA2026-0001',
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'client_name' => 'Client Exemple',
            'client_address' => '123 Rue Exemple',
            'client_postal_code' => '75001',
            'client_city' => 'Paris',
            'payment_status' => 'unpaid',
            'total_ht' => 100.00,
            'total_tva' => 20.00,
            'total_ttc' => 120.00,
            'paid_amount' => 0,
            'late_fee_rate' => 10.00,
            'recovery_indemnity' => 40.00,
            'items' => [[
                'description' => 'Produit exemple',
                'quantity' => 1,
                'unit' => 'unité',
                'unit_price_ht' => 100.00,
                'vat_rate' => 20.00,
                'total_ttc' => 120.00
            ]],
            'vat_details' => [[
                'rate' => 20.00,
                'base_ht' => 100.00,
                'amount' => 20.00
            ]]
        ];
        
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $html = InvoiceTemplates::generateHTML($invoice, $company, $templateName);
        
        echo '<!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Aperçu - ' . htmlspecialchars($templateName) . '</title>
        </head>
        <body style="margin: 0; padding: 20px; background: #f3f4f6;">
            ' . $html . '
            <div style="text-align: center; margin-top: 30px;">
                <button onclick="window.close()" style="padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px;">
                    Fermer l\'aperçu
                </button>
            </div>
        </body>
        </html>';
        exit;
    }
    
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Action non trouvée']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
