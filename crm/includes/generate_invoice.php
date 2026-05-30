<?php

include("verify_subscriptions.php");

$page_title = "Générer une facture - CRM Intelligent";

$folder_id = isset($_GET['folder_id']) ? intval($_GET['folder_id']) : 0;
if (!$folder_id) {
    die("Dossier invalide.");
}

// Récupérer les missions du dossier (on ne facture que les missions terminées)
$stmt = $pdo->prepare("
    SELECT m.* 
    FROM missions m
    LEFT JOIN statuses s ON m.status_id = s.id
    WHERE m.folder_id = ? AND s.name = 'Terminée'
");
$stmt->execute([$folder_id]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$missions) {
    echo "<div class='alert alert-warning'>Aucune mission terminée à facturer pour ce dossier.</div>";
    exit;
}

// Calcul du montant total (champ prix requis dans missions)
$total = 0;
foreach ($missions as $mission) {
    $total += $mission['prix'] ?? 0;
}

// Générer un numéro de facture unique (ex: F2025-0001)
$year = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(issued_at) = ?");
$stmt->execute([$year]);
$count = $stmt->fetchColumn() + 1;
$invoice_number = "F$year-" . str_pad($count, 4, "0", STR_PAD_LEFT);

// Récupérer les infos du dossier et de l'entreprise
$stmt = $pdo->prepare("SELECT f.*, c.name AS company_name, c.email AS company_email FROM folders f INNER JOIN companies c ON f.company_id = c.id WHERE f.id = ?");
$stmt->execute([$folder_id]);
$folder = $stmt->fetch(PDO::FETCH_ASSOC);

// Société facturante (customer connecté)
$billing_company = null;
if ($customer_id) {
    try {
        $cstmt = $pdo->prepare("SELECT name, email, phone, address, city, postal_code, country, siren, naf FROM customers WHERE id = ? LIMIT 1");
        $cstmt->execute([$customer_id]);
        $billing_company = $cstmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $billing_company = null;
    }
}

// Styles d'email disponibles
$email_styles = [
    'classique' => 'background:#fff;color:#222;font-family:sans-serif;',
    'bleu' => 'background:#eaf4fb;color:#1a3d5d;font-family:sans-serif;',
    'dark' => 'background:#222;color:#fff;font-family:sans-serif;',
];

// Style choisi (par défaut classique)
$selected_style = $_POST['email_style'] ?? 'classique';
$message = $_POST['email_message'] ?? "Bonjour,<br>Veuillez trouver ci-joint la facture <b>$invoice_number</b> pour vos missions.<br>Merci de votre confiance.";
?>