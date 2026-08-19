<?php
/**
 * Script pour recalculer tous les scores IA des leads existants
 * Utile après avoir ajouté la fonction de scoring
 */

require_once 'config/database.php';
require_once 'includes/ai_scoring.php';
require_once 'includes/verify_subscriptions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'recalculate') {
    try {
        $customer_id = $_SESSION['customer_id'] ?? null;
        
        if ($customer_id) {
            $updated = recalculate_all_lead_scores($pdo, $customer_id);
            $message = "✅ Score IA recalculé pour $updated leads";
        } else {
            $message = "❌ Erreur: customer_id non identifié";
        }
    } catch (Exception $e) {
        $message = "❌ Erreur: " . $e->getMessage();
    }
}

$page_title = "Recalculer les scores IA - CRM Intelligent";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="h4">
                        <i class="fas fa-brain text-primary"></i> Recalculer les scores IA
                    </h1>
                    <a href="leads.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>

                <?php if (isset($message)): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            Outils de calcul IA
                        </h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Cette page permet de recalculer le score IA pour tous vos leads existants. 
                            Le score IA est normalement calculé automatiquement lors de la création ou modification d'un lead.
                        </p>

                        <div class="alert alert-warning">
                            <strong><i class="fas fa-exclamation-triangle"></i> Attention:</strong>
                            Cette action va recalculer le score IA pour tous les leads. Cela peut prendre quelques secondes selon le nombre de leads.
                        </div>

                        <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir recalculer tous les scores IA?');">
                            <input type="hidden" name="action" value="recalculate">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync-alt"></i> Recalculer tous les scores IA
                            </button>
                        </form>

                        <hr class="my-4">

                        <h5>Comment ça marche?</h5>
                        <p>Le score IA est calculé en fonction de:</p>
                        <ul>
                            <li><strong>Complétude du profil (40 pts)</strong>: Prénom, nom, email, téléphone, poste, entreprise</li>
                            <li><strong>Stage du lead (30 pts)</strong>: Converti (30) > Proposition (25) > Qualifié (20) > Contacté (10) > Nouveau (5) > Perdu (0)</li>
                            <li><strong>Source (15 pts)</strong>: Direct (15) > Site web (12) > Réseaux sociaux (8) > etc.</li>
                            <li><strong>Validations (15 pts)</strong>: Email valide (8) + Téléphone (4) + Assigné (3)</li>
                        </ul>

                        <h5 class="mt-4">Exemple</h5>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Situation</th>
                                    <th>Score IA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="text-light">Lead vide (juste un email)</td>
                                    <td><span class="badge bg-danger">~10</span></td>
                                </tr>
                                <tr>
                                    <td class="text-light">Lead avec tous les champs, stage "Nouveau"</td>
                                    <td><span class="badge bg-warning">~50-60</span></td>
                                </tr>
                                <tr>
                                    <td class="text-light">Lead complet, stage "Qualifié"</td>
                                    <td><span class="badge bg-info">~80</span></td>
                                </tr>
                                <tr>
                                    <td class="text-light">Lead complet, stage "Converti"</td>
                                    <td><span class="badge bg-success">~100</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
