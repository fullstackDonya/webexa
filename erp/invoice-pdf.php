<?php
/**
 * Génération PDF de facture conforme aux normes françaises 2026
 * 
 * Utilise TCPDF pour générer un PDF professionnel avec:
 * - Logo et informations entreprise
 * - Mentions légales obligatoires
 * - TVA française
 * - QR Code de paiement (optionnel)
 * - 5 modèles de facture au choix
 */

session_start();
require_once __DIR__ . '/../crm/config/database.php';
require_once __DIR__ . '/api/InvoiceGenerator.php';
require_once __DIR__ . '/api/InvoiceTemplates.php';

// Vérifier l'authentification
if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    die('Non authentifié');
}

$customerId = $_SESSION['customer_id'];
$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($invoiceId <= 0) {
    http_response_code(400);
    die('ID de facture invalide');
}

// Récupérer la facture complète
try {
    $invoiceGen = new InvoiceGenerator($pdo, $customerId);
    $invoice = $invoiceGen->getInvoice($invoiceId);
    
    if (!$invoice) {
        http_response_code(404);
        die('Facture non trouvée');
    }
    
    // Récupérer les paramètres de facturation (modèle choisi)
    $stmt = $pdo->prepare("
        SELECT template_name FROM erp_invoice_settings
        WHERE customer_id = ?
    ");
    $stmt->execute([$customerId]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    $templateName = $settings['template_name'] ?? 'modern';
    
} catch (Exception $e) {
    http_response_code(500);
    die('Erreur: ' . $e->getMessage());
}

// Vérifier si TCPDF est disponible
$tcpdfPath = __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
$useTCPDF = file_exists($tcpdfPath);

if ($useTCPDF) {
    // Utiliser TCPDF (recommandé)
    require_once $tcpdfPath;
    generatePDFWithTCPDF($invoice, $templateName);
} else {
    // Fallback: Génération HTML simple
    generateHTMLInvoice($invoice, $templateName);
}

/**
 * Générer le PDF avec TCPDF
 */
function generatePDFWithTCPDF($invoice, $templateName = 'modern') {
    global $pdo, $customerId;
    
    // Configuration TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Métadonnées
    $pdf->SetCreator('Webitech CRM');
    $pdf->SetAuthor($invoice['client_name']);
    $pdf->SetTitle('Facture ' . $invoice['invoice_number']);
    $pdf->SetSubject('Facture');
    
    // Supprimer header/footer par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Paramètres
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->SetFont('helvetica', '', 10);
    
    // Ajouter une page
    $pdf->AddPage();
    
    // Récupérer les informations de l'entreprise
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Générer le contenu HTML avec le template choisi
    $html = InvoiceTemplates::generateHTML($invoice, $company, $templateName);
    
    // Écrire le HTML
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Output PDF
    $filename = 'Facture_' . $invoice['invoice_number'] . '.pdf';
    $pdf->Output($filename, 'I'); // I = inline, D = download
}

/**
 * Générer HTML simple (fallback)
 */
function generateHTMLInvoice($invoice, $templateName = 'modern') {
    global $pdo, $customerId;
    
    header('Content-Type: text/html; charset=utf-8');
    
    // Récupérer les informations de l'entreprise
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $html = InvoiceTemplates::generateHTML($invoice, $company, $templateName);
    
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Facture ' . htmlspecialchars($invoice['invoice_number']) . '</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            @media print {
                .no-print { display: none; }
            }
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f3f4f6; }
        </style>
    </head>
    <body>
        <div class="no-print" style="padding: 20px; background: #667eea; color: white; margin-bottom: 20px; text-align: center;">
            <button onclick="window.print()" style="padding: 12px 24px; background: white; color: #667eea; border: none; cursor: pointer; border-radius: 8px; font-weight: 600; margin-right: 10px;">
                <i class="fas fa-print"></i> Imprimer / Enregistrer en PDF
            </button>
            <a href="invoices.php" style="padding: 12px 24px; background: #374151; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Retour aux factures
            </a>
        </div>
        <div style="background: white; max-width: 900px; margin: 0 auto; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
            ' . $html . '
        </div>
    </body>
    </html>';
}

// Récupérer les informations de l'entreprise pour les fonctions
$stmt = $pdo->prepare("
    SELECT name, email, phone, address, city, postal_code, country, 
           siren, siret, naf, vat_number, capital
    FROM customers 
    WHERE id = ?
");
$stmt->execute([$customerId]);
$GLOBALS['customer'] = $stmt->fetch(PDO::FETCH_ASSOC);
