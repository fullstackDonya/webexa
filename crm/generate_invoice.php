<?php

require_once 'includes/generate_invoice.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
     <style>
        .table{
            color: #fff;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-file-invoice text-primary"></i> Générer une facture
                </h1>
                <a href="folder_view.php?id=<?php echo $folder_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour au dossier
                </a>
            </div>
            <div class="row">
                <div class="col-lg-9">
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <?php if (!empty($billing_company)): ?>
                                <div class="card shadow-sm mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($billing_company['name'] ?? ''); ?></h5>
                                                <div class="text-muted">
                                                    <?php echo htmlspecialchars(trim(($billing_company['address'] ?? '') . ' ' . ($billing_company['postal_code'] ?? '') . ' ' . ($billing_company['city'] ?? '') . ' ' . ($billing_company['country'] ?? ''))); ?>
                                                </div>
                                                <?php if (!empty($billing_company['email'])): ?>
                                                    <div class="text-muted"><?php echo htmlspecialchars($billing_company['email']); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($billing_company['phone'])): ?>
                                                    <div class="text-muted"><?php echo htmlspecialchars($billing_company['phone']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                                                <?php if (!empty($billing_company['siren'])): ?>
                                                    <div class="text-muted"><strong>SIREN :</strong> <?php echo htmlspecialchars($billing_company['siren']); ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($billing_company['naf'])): ?>
                                                    <div class="text-muted"><strong>NAF :</strong> <?php echo htmlspecialchars($billing_company['naf']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <h4>
                                <span class="badge bg-primary">Facture <?php echo htmlspecialchars($invoice_number); ?></span>
                                <span class="badge bg-secondary">Dossier : <?php echo htmlspecialchars($folder['name']); ?></span>
                            </h4>
                            <div class="mb-3">
                                <a href="generate_invoice_pdf.php?folder_id=<?php echo (int)$folder_id; ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-file-pdf"></i> Générer le PDF
                                </a>
                            </div>
                            <p><strong>Entreprise :</strong> <?php echo htmlspecialchars($folder['company_name']); ?></p>
                            <table class="table table-bordered">
                                <thead>
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
                                        <td>
                                            <?php
                                            if (!empty($mission['datetime'])) {
                                                echo htmlspecialchars(date('d/m/Y', strtotime($mission['datetime'])));
                                            } else {
                                                echo '';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo isset($mission['prix']) ? number_format($mission['prix'], 2, ',', ' ') : '0,00'; ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-end">Total</th>
                                        <th><?php echo number_format($total, 2, ',', ' '); ?> €</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <h4>Aperçu de l'email à envoyer</h4>
                            <form method="post">
                                <input type="hidden" name="folder_id" value="<?php echo $folder_id; ?>">
                                <input type="hidden" name="invoice_number" value="<?php echo htmlspecialchars($invoice_number); ?>">
                                <input type="hidden" name="total" value="<?php echo $total; ?>">
                                <div class="mb-3">
                                    <label for="email_style" class="form-label">Style d'email :</label>
                                    <select name="email_style" id="email_style" class="form-select" onchange="this.form.submit()">
                                        <?php foreach ($email_styles as $key => $css): ?>
                                            <option value="<?php echo $key; ?>" <?php if ($selected_style == $key) echo 'selected'; ?>><?php echo ucfirst($key); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="email_message" class="form-label">Message personnalisé :</label>
                                    <textarea name="email_message" id="email_message" class="form-control" rows="4"><?php echo htmlspecialchars($message); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <div style="<?php echo $email_styles[$selected_style]; ?>padding:20px;border-radius:8px;">
                                        <?php echo $message; ?>
                                        <hr>
                                        <b>Montant total : <?php echo number_format($total, 2, ',', ' '); ?> €</b>
                                    </div>
                                </div>
                                <button type="submit" name="validate_invoice" class="btn btn-success">Valider et envoyer la facture</button>
                                <a href="folder_view.php?id=<?php echo $folder_id; ?>" class="btn btn-secondary">Annuler</a>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Aide</h6>
                        </div>
                        <div class="card-body">
                            <ul class="small ">
                                <li>Seules les missions <b>terminées</b> sont facturées.</li>
                                <li>Personnalisez le message et le style de l'email avant validation.</li>
                                <li>Le PDF ou l'email sera généré après validation.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Traitement après validation
if (isset($_POST['validate_invoice'])) {
    $stmt = $pdo->prepare("INSERT INTO invoices (folder_id, invoice_number, amount, status, issued_at) VALUES (?, ?, ?, 'en_attente', NOW())");
    $stmt->execute([$folder_id, $invoice_number, $total]);
    $invoice_id = $pdo->lastInsertId();

    // (Optionnel) Marquer les missions comme facturées ici

    // Envoi de l'email (à compléter selon ta logique)
    // mail($folder['company_email'], "Nouvelle facture $invoice_number", $message, ...);

    header("Location: invoice_view.php?id=" . $invoice_id);
    exit;
}
?>