<?php
/**
 * PDF Invoice Generation
 * 
 * @phpstan-require-once config/database.php
 */

session_start();

// Inclure la configuration et initialiser PDO
if (!function_exists('get_db_connection')) {
    require_once __DIR__ . '/config/database.php';
}

// Assurer que $pdo est disponible globalement
global $pdo;

$folder_id = isset($_GET['folder_id']) ? intval($_GET['folder_id']) : 0;
if (!$folder_id) {
    die('Dossier invalide.');
}

// Vérifier que l'utilisateur a accès à ce dossier via customer_id
if (!isset($_SESSION['customer_id'])) {
    die('Accès refusé : aucun customer_id en session.');
}

$customer_id = $_SESSION['customer_id'];
$billing_company = null;
if ($customer_id) {
    try {
        $cstmt = $pdo->prepare("SELECT name, email, phone, address, city, country, siren, naf FROM customers WHERE id = ? LIMIT 1");
        $cstmt->execute([$customer_id]);
        $billing_company = $cstmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $billing_company = null;
    }
}

// Missions terminées uniquement
$stmt = $pdo->prepare("
    SELECT m.*
    FROM missions m
    LEFT JOIN statuses s ON m.status_id = s.id
    WHERE m.folder_id = ? AND s.name = 'Terminée'");
$stmt->execute([$folder_id]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$missions) {
    die('Aucune mission terminée à facturer pour ce dossier.');
}

$total = 0;
foreach ($missions as $mission) {
    $total += $mission['prix'] ?? 0;
}

$year = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE YEAR(issued_at) = ?");
$stmt->execute([$year]);
$count = $stmt->fetchColumn() + 1;
$invoice_number = "F$year-" . str_pad($count, 4, "0", STR_PAD_LEFT);

$stmt = $pdo->prepare("SELECT f.*, c.name AS company_name, c.email AS company_email FROM folders f INNER JOIN companies c ON f.company_id = c.id WHERE f.id = ?");
$stmt->execute([$folder_id]);
$folder = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$folder) {
    die('Dossier introuvable.');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture <?php echo htmlspecialchars($invoice_number); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .invoice-title {
            font-size: 22px;
            font-weight: 700;
        }
        .invoice-meta {
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="no-print mb-3 d-flex gap-2">
        <a href="generate_invoice.php?folder_id=<?php echo (int)$folder_id; ?>" class="btn btn-secondary">Retour</a>
        <button class="btn btn-primary" onclick="window.print()">Télécharger / Imprimer en PDF</button>
    </div>

    <?php if (!empty($billing_company)): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <div class="invoice-title"><?php echo htmlspecialchars($billing_company['name'] ?? ''); ?></div>
                        <div class="invoice-meta">
                            <?php echo htmlspecialchars(trim(($billing_company['address'] ?? '') . ' ' . ($billing_company['postal_code'] ?? '') . ' ' . ($billing_company['city'] ?? '') . ' ' . ($billing_company['country'] ?? ''))); ?>
                        </div>
                        <?php if (!empty($billing_company['email'])): ?>
                            <div class="invoice-meta"><?php echo htmlspecialchars($billing_company['email']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($billing_company['phone'])): ?>
                            <div class="invoice-meta"><?php echo htmlspecialchars($billing_company['phone']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <?php if (!empty($billing_company['siren'])): ?>
                            <div class="invoice-meta"><strong>SIREN :</strong> <?php echo htmlspecialchars($billing_company['siren']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($billing_company['naf'])): ?>
                            <div class="invoice-meta"><strong>NAF :</strong> <?php echo htmlspecialchars($billing_company['naf']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="invoice-header">
                <div>
                    <div class="invoice-title">Facture <?php echo htmlspecialchars($invoice_number); ?></div>
                    <div class="invoice-meta">Dossier : <?php echo htmlspecialchars($folder['name']); ?></div>
                </div>
                <div class="text-end">
                    <div><strong>Entreprise :</strong> <?php echo htmlspecialchars($folder['company_name']); ?></div>
                    <?php if (!empty($folder['company_email'])): ?>
                        <div class="invoice-meta"><?php echo htmlspecialchars($folder['company_email']); ?></div>
                    <?php endif; ?>
                    <div class="invoice-meta">Date : <?php echo date('d/m/Y'); ?></div>
                </div>
            </div>

            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mission</th>
                        <th>Détails</th>
                        <th>Date</th>
                        <th>Prix</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($missions as $mission): ?>
                    <?php
                    $details = [];
                    if (!empty($mission['departure']) || !empty($mission['arrival'])) {
                        $details[] = trim(($mission['departure'] ?? '') . ' → ' . ($mission['arrival'] ?? ''));
                    }
                    if (!empty($mission['driver'])) { $details[] = 'Chauffeur: ' . $mission['driver']; }
                    if (!empty($mission['vehicle'])) { $details[] = 'Véhicule: ' . $mission['vehicle']; }
                    if (!empty($mission['product'])) { $details[] = 'Produit: ' . $mission['product']; }
                    if (!empty($mission['quantity'])) { $details[] = 'Qté: ' . $mission['quantity']; }
                    if (!empty($mission['project'])) { $details[] = 'Projet: ' . $mission['project']; }
                    if (!empty($mission['responsible'])) { $details[] = 'Responsable: ' . $mission['responsible']; }
                    if (!empty($mission['description'])) { $details[] = $mission['description']; }
                    $detailsText = implode(' • ', $details);
                    ?>
                    <tr>
                        <td>M-<?php echo htmlspecialchars($mission['id']); ?></td>
                        <td><?php echo htmlspecialchars($detailsText); ?></td>
                        <td><?php echo !empty($mission['datetime']) ? htmlspecialchars(date('d/m/Y', strtotime($mission['datetime']))) : ''; ?></td>
                        <td><?php echo isset($mission['prix']) ? number_format($mission['prix'], 2, ',', ' ') : '0,00'; ?> €</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total</th>
                        <th><?php echo number_format($total, 2, ',', ' '); ?> €</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</body>
</html>
