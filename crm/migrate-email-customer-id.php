<?php
/**
 * Script de migration pour ajouter customer_id à la table emails
 * À exécuter une seule fois
 */

require_once __DIR__ . '/includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';

// Vérifier les permissions admin
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     die("Accès refusé. Seuls les administrateurs peuvent exécuter cette migration.");
// }

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration - Ajouter customer_id</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-database"></i> Migration Base de Données - Emails</h4>
                    </div>
                    <div class="card-body">
                        <h5>Ajouter les colonnes manquantes à la table emails</h5>
                        <p class="text-muted">Cette migration ajoute les colonnes <code>config_id</code> et <code>customer_id</code> si elles n'existent pas.</p>
                        
                        <div id="migration-result">
                            <?php
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_migration'])) {
                                echo '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Migration en cours...</div>';
                                
                                try {
                                    // Lire le fichier SQL
                                    $sqlFile = __DIR__ . '/database/migrate_add_customer_id_to_emails.sql';
                                    
                                    if (!file_exists($sqlFile)) {
                                        throw new Exception("Fichier SQL de migration introuvable: $sqlFile");
                                    }
                                    
                                    $sql = file_get_contents($sqlFile);
                                    
                                    // Exécuter le SQL
                                    $pdo->exec($sql);
                                    
                                    // Vérifier que la colonne existe maintenant
                                    $checkStmt = $pdo->query("
                                        SELECT 
                                            (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                                             WHERE TABLE_SCHEMA = DATABASE() 
                                             AND TABLE_NAME = 'emails' 
                                             AND COLUMN_NAME = 'config_id') as config_id_exists,
                                            (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                                             WHERE TABLE_SCHEMA = DATABASE() 
                                             AND TABLE_NAME = 'emails' 
                                             AND COLUMN_NAME = 'customer_id') as customer_id_exists
                                    ");
                                    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($result['config_id_exists'] > 0 && $result['customer_id_exists'] > 0) {
                                        echo '<div class="alert alert-success mt-3">';
                                        echo '<h5><i class="fas fa-check-circle"></i> Migration réussie!</h5>';
                                        echo '<ul class="mb-0">';
                                        echo '<li>Colonne config_id: ' . ($result['config_id_exists'] ? '✓ Existe' : '✗ Manquante') . '</li>';
                                        echo '<li>Colonne customer_id: ' . ($result['customer_id_exists'] ? '✓ Existe' : '✗ Manquante') . '</li>';
                                        echo '<li>Index créés</li>';
                                        echo '<li>Foreign keys configurées</li>';
                                        echo '</ul>';
                                        echo '<hr>';
                                        echo '<p class="mb-0"><strong>Vous pouvez maintenant:</strong></p>';
                                        echo '<a href="email-inbox.php" class="btn btn-primary mt-2"><i class="fas fa-inbox"></i> Accéder à la boîte de réception</a>';
                                        echo '</div>';
                                    } else {
                                        throw new Exception("Les colonnes n'ont pas été créées correctement");
                                    }
                                    
                                } catch (PDOException $e) {
                                    echo '<div class="alert alert-danger mt-3">';
                                    echo '<h5><i class="fas fa-exclamation-triangle"></i> Erreur SQL</h5>';
                                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                                    echo '<p class="mb-0"><strong>Code d\'erreur:</strong> ' . $e->getCode() . '</p>';
                                    echo '</div>';
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger mt-3">';
                                    echo '<h5><i class="fas fa-exclamation-triangle"></i> Erreur</h5>';
                                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                                    echo '</div>';
                                }
                            } else {
                                // Vérifier si la migration est nécessaire
                                try {
                                    $checkStmt = $pdo->query("
                                        SELECT 
                                            (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                                             WHERE TABLE_SCHEMA = DATABASE() 
                                             AND TABLE_NAME = 'emails' 
                                             AND COLUMN_NAME = 'config_id') as config_id_exists,
                                            (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                                             WHERE TABLE_SCHEMA = DATABASE() 
                                             AND TABLE_NAME = 'emails' 
                                             AND COLUMN_NAME = 'customer_id') as customer_id_exists
                                    ");
                                    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($result['config_id_exists'] > 0 && $result['customer_id_exists'] > 0) {
                                        echo '<div class="alert alert-success">';
                                        echo '<h5><i class="fas fa-check-circle"></i> Migration déjà effectuée</h5>';
                                        echo '<p>Les colonnes nécessaires existent déjà dans la table emails:</p>';
                                        echo '<ul>';
                                        echo '<li>config_id: ✓</li>';
                                        echo '<li>customer_id: ✓</li>';
                                        echo '</ul>';
                                        echo '<hr>';
                                        echo '<a href="email-inbox.php" class="btn btn-primary"><i class="fas fa-inbox"></i> Accéder à la boîte de réception</a>';
                                        echo '</div>';
                                    } else {
                                        echo '<div class="alert alert-warning">';
                                        echo '<h5><i class="fas fa-exclamation-triangle"></i> Migration requise</h5>';
                                        echo '<p>Colonnes manquantes dans la table emails:</p>';
                                        echo '<ul>';
                                        if ($result['config_id_exists'] == 0) echo '<li>config_id: ✗ Manquante</li>';
                                        if ($result['customer_id_exists'] == 0) echo '<li>customer_id: ✗ Manquante</li>';
                                        echo '</ul>';
                                        echo '<p class="mb-0">Cliquez sur le bouton ci-dessous pour exécuter la migration.</p>';
                                        echo '</div>';
                                        
                                        echo '<form method="POST" onsubmit="return confirm(\'Êtes-vous sûr de vouloir exécuter cette migration ?\')">';
                                        echo '<button type="submit" name="execute_migration" class="btn btn-primary btn-lg mt-3">';
                                        echo '<i class="fas fa-play"></i> Exécuter la migration';
                                        echo '</button>';
                                        echo '</form>';
                                    }
                                    
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger">';
                                    echo '<h5><i class="fas fa-exclamation-triangle"></i> Erreur</h5>';
                                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6>Détails techniques</h6>
                        <ul class="text-muted small">
                            <li>Ajoute la colonne <code>config_id INT</code> si manquante (relation vers email_configurations)</li>
                            <li>Ajoute la colonne <code>customer_id INT</code> si manquante (relation vers customers)</li>
                            <li>Crée les index <code>idx_emails_config_id</code> et <code>idx_emails_customer_id</code></li>
                            <li>Ajoute les foreign keys vers <code>email_configurations(id)</code> et <code>customers(id)</code></li>
                            <li>Remplit automatiquement customer_id depuis email_configurations</li>
                        </ul>
                        
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Retour au tableau de bord
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
