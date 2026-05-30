<?php

require_once 'includes/verify_subscriptions.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/env.php';

$page_title = "Boîte de Réception - CRM";
$customer_id = $_SESSION['customer_id'] ?? null;
$error_message = null;
$email_to_open = null;

if (!$customer_id) {
    header('Location: index.php?error=customer');
    exit;
}

// Vérification sécurisée de l'email à ouvrir depuis une notification
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $email_id_param = (int)$_GET['id'];
    
    // Vérifier que l'email appartient bien à ce customer_id (sécurité)
    try {
        $securityCheck = $pdo->prepare("
            SELECT id FROM emails 
            WHERE id = ? AND customer_id = ?
            LIMIT 1
        ");
        $securityCheck->execute([$email_id_param, $customer_id]);
        
        if ($securityCheck->rowCount() > 0) {
            $email_to_open = $email_id_param;
        } else {
            $error_message = "Accès refusé : cet email n'appartient pas à votre compte.";
        }
    } catch (Exception $e) {
        error_log("Email security check error: " . $e->getMessage());
    }
}

// Vérifier si les tables email existent
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_configurations'");
    if ($stmt->rowCount() === 0) {
        $error_message = "Les tables email ne sont pas créées. Exécutez: mysql -u root webitech < database/email_tables.sql";
    }
} catch (Exception $e) {
    $error_message = "Erreur de base de données: " . $e->getMessage();
    error_log("Email inbox DB error: " . $e->getMessage());
}

$emailConfigs = [];
$stats = ['total' => 0, 'unread' => 0, 'with_attachments' => 0];
$emails = [];
$totalPages = 1;
$totalEmails = 0;
$selectedConfigId = null;
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if (!$error_message) {
    try {
        // Vérifier que la table emails a les bonnes colonnes
        $checkColumns = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'emails' 
                 AND COLUMN_NAME = 'customer_id') as has_customer_id,
                (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'emails' 
                 AND COLUMN_NAME = 'config_id') as has_config_id
        ");
        $columnsCheck = $checkColumns->fetch(PDO::FETCH_ASSOC);
        
        if ($columnsCheck['has_customer_id'] == 0 || $columnsCheck['has_config_id'] == 0) {
            $missingCols = [];
            if ($columnsCheck['has_customer_id'] == 0) $missingCols[] = 'customer_id';
            if ($columnsCheck['has_config_id'] == 0) $missingCols[] = 'config_id';
            
            $error_message = "⚠️ Colonnes manquantes dans la table emails: " . implode(', ', $missingCols) . 
                           ". <br><strong>Action requise:</strong> <a href='migrate-email-customer-id.php' class='alert-link btn btn-warning btn-sm ms-2'>" .
                           "<i class='fas fa-database'></i> Exécuter la migration maintenant</a>";
        }
    } catch (Exception $e) {
        $error_message = "Erreur lors de la vérification de la structure: " . $e->getMessage();
    }
}

if (!$error_message) {
    try {
        // Récupérer les configurations email actives
        $configStmt = $pdo->prepare("
            SELECT id, email, provider 
            FROM email_configurations 
            WHERE customer_id = ? AND is_active = 1
            ORDER BY email
        ");
        $configStmt->execute([$customer_id]);
        $emailConfigs = $configStmt->fetchAll(PDO::FETCH_ASSOC);

        // Filtre par configuration
        $selectedConfigId = $_GET['config_id'] ?? ($emailConfigs[0]['id'] ?? null);

        // Pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        // Filtres
        $filter = $_GET['filter'] ?? 'all'; // all, unread, read, attachments
        $search = $_GET['search'] ?? '';

        // Construire la requête
        $whereConditions = ["e.customer_id = ?"];
        $params = [$customer_id];

        if ($selectedConfigId) {
            $whereConditions[] = "e.config_id = ?";
            $params[] = $selectedConfigId;
        }

        if ($filter === 'unread') {
            $whereConditions[] = "e.is_read = 0";
        } elseif ($filter === 'read') {
            $whereConditions[] = "e.is_read = 1";
        } elseif ($filter === 'attachments') {
            $whereConditions[] = "e.has_attachments = 1";
        }

        if (!empty($search)) {
            $whereConditions[] = "(e.subject LIKE ? OR e.from_address LIKE ? OR e.body LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Compter le total
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM emails e 
            WHERE $whereClause
        ");
        $countStmt->execute($params);
        $totalEmails = $countStmt->fetch()['total'];
        $totalPages = ceil($totalEmails / $perPage);

        // Récupérer les emails
        // Note: LIMIT et OFFSET doivent être des entiers, pas des paramètres bindés
        $limitValue = (int)$perPage;
        $offsetValue = (int)$offset;

        $emailsStmt = $pdo->prepare("
            SELECT 
                e.*,
                ec.email as config_email,
                ec.provider
            FROM emails e
            JOIN email_configurations ec ON e.config_id = ec.id
            WHERE $whereClause
            ORDER BY e.email_date DESC
            LIMIT $limitValue OFFSET $offsetValue
        ");
        $emailsStmt->execute($params);
        $emails = $emailsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Statistiques
        $statsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                COALESCE(SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END), 0) as unread,
                COALESCE(SUM(CASE WHEN has_attachments = 1 THEN 1 ELSE 0 END), 0) as with_attachments
            FROM emails
            WHERE customer_id = ?
        ");
        $statsStmt->execute([$customer_id]);
        $statsResult = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // S'assurer que les valeurs ne sont jamais NULL
        $stats = [
            'total' => (int)($statsResult['total'] ?? 0),
            'unread' => (int)($statsResult['unread'] ?? 0),
            'with_attachments' => (int)($statsResult['with_attachments'] ?? 0)
        ];
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des emails: " . $e->getMessage();
        error_log("Email inbox error: " . $e->getMessage());
    }
}
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
    <style>
        .email-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        .email-item {
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .modal-header, .email-body-content, .email-header-viewer {
           color: #495057;
        }
        .email-item:hover {
            background: #f8f9fa;
        }
        .email-item.unread {
            background: #f0f7ff;
            font-weight: 500;
        }
        .email-item.unread:hover {
            background: #e3f2ff;
        }
        .email-checkbox {
            flex-shrink: 0;
        }
        .email-from {
            color: #212529;
            flex: 0 0 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .email-subject {
            color: #495057;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .email-date {
            flex: 0 0 120px;
            text-align: right;
            font-size: 0.85rem;
            color: #6c757d;
        }
        .email-icons {
            flex: 0 0 50px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .inbox-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .inbox-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-tab {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            text-decoration: none;
            color: #495057;
            transition: all 0.2s;
        }
        .filter-tab:hover {
            background: #f8f9fa;
            color: #212529;
        }
        .filter-tab.active {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .email-viewer {
            background: white;
            padding: 30px;
            border-radius: 8px;
            display: none;
        }
        .email-viewer.active {
            display: block;
        }
        .email-header-viewer {
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .stats-bar {
            display: flex;
            gap: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .no-emails {
            text-align: center;
            padding: 60px 20px;
            color: #121517;
        }
        .no-emails i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5><i class="fas fa-exclamation-triangle"></i> Erreur</h5>
                        <?php if (strpos($error_message, '<a href') !== false || strpos($error_message, '<br>') !== false): ?>
                            <p><?php echo $error_message; ?></p>
                        <?php else: ?>
                            <p><?php echo htmlspecialchars($error_message); ?></p>
                        <?php endif; ?>
                        <?php if (strpos($error_message, 'tables email') !== false): ?>
                            <hr>
                            <p class="mb-0">
                                <strong>Solution:</strong> Exécutez cette commande depuis le terminal:<br>
                                <code style="background: #fff; padding: 5px 10px; display: inline-block; margin-top: 5px;">
                                    mysql -u root webitech &lt; database/email_tables.sql
                                </code>
                                <br>
                                <small class="text-muted">Ou importez le fichier via <a href="http://localhost:8888/phpmyadmin" target="_blank">phpMyAdmin</a></small>
                            </p>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!$error_message): ?>
                <!-- Header -->
                <div class="inbox-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1><i class="fas fa-inbox"></i> Boîte de Réception</h1>
                            <div class="stats-bar mt-3">
                                <div class="stat-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><strong><?php echo number_format($stats['total'] ?? 0); ?></strong> emails</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-envelope-open text-primary"></i>
                                    <span><strong><?php echo number_format($stats['unread'] ?? 0); ?></strong> non lus</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-paperclip text-info"></i>
                                    <span><strong><?php echo number_format($stats['with_attachments'] ?? 0); ?></strong> avec pièces jointes</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-primary" onclick="syncAllEmails()">
                                <i class="fas fa-sync"></i> Synchroniser
                            </button>
                            <button class="btn btn-success" onclick="composeEmail()">
                                <i class="fas fa-pen"></i> Nouveau
                            </button>
                            <a href="email-settings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-cog"></i> Paramètres
                            </a>
                        </div>
                    </div>
                    
                    <!-- Filtres et recherche -->
                    <div class="inbox-actions">
                        <select class="form-select" style="width: 250px;" onchange="changeConfig(this.value)">
                            <option value="">Tous les comptes</option>
                            <?php foreach ($emailConfigs as $config): ?>
                                <option value="<?php echo $config['id']; ?>" 
                                    <?php echo ($selectedConfigId == $config['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($config['email']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <form class="d-flex" method="GET" style="flex: 1;">
                            <input type="hidden" name="config_id" value="<?php echo $selectedConfigId; ?>">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <input type="search" name="search" class="form-control" 
                                placeholder="Rechercher dans les emails..." 
                                value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-primary ms-2">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Onglets de filtre -->
                <div class="filter-tabs">
                    <a href="?filter=all<?php echo $selectedConfigId ? '&config_id='.$selectedConfigId : ''; ?>" 
                       class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-inbox"></i> Tous
                    </a>
                    <a href="?filter=unread<?php echo $selectedConfigId ? '&config_id='.$selectedConfigId : ''; ?>" 
                       class="filter-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i> Non lus (<?php echo $stats['unread'] ?? 0; ?>)
                    </a>
                    <a href="?filter=read<?php echo $selectedConfigId ? '&config_id='.$selectedConfigId : ''; ?>" 
                       class="filter-tab <?php echo $filter === 'read' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope-open"></i> Lus
                    </a>
                    <a href="?filter=attachments<?php echo $selectedConfigId ? '&config_id='.$selectedConfigId : ''; ?>" 
                       class="filter-tab <?php echo $filter === 'attachments' ? 'active' : ''; ?>">
                        <i class="fas fa-paperclip"></i> Pièces jointes
                    </a>
                </div>
                
                <!-- Liste des emails -->
                <div class="email-list">
                    <?php if (empty($emails)): ?>
                        <div class="no-emails">
                            <i class="fas fa-inbox"></i>
                            <h4>Aucun email</h4>
                            <p>
                                <?php if (empty($emailConfigs)): ?>
                                    Configurez d'abord un compte email dans les paramètres.
                                <?php else: ?>
                                    Cliquez sur "Synchroniser" pour récupérer vos emails.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($emails as $email): ?>
                            <div class="email-item <?php echo !$email['is_read'] ? 'unread' : ''; ?>" 
                                 data-email-id="<?php echo $email['id']; ?>"
                                 onclick="viewEmail(<?php echo $email['id']; ?>)">
                                <div class="email-checkbox">
                                    <input type="checkbox" class="form-check-input" 
                                           onclick="event.stopPropagation()">
                                </div>
                                <div class="email-from">
                                    <strong><?php echo htmlspecialchars($email['from_name'] ?: $email['from_address']); ?></strong>
                                </div>
                                <div class="email-subject">
                                    <?php echo htmlspecialchars($email['subject']); ?>
                                    <?php if (!$email['is_read']): ?>
                                        <span class="badge bg-primary ms-2">Nouveau</span>
                                    <?php endif; ?>
                                </div>
                                <div class="email-icons">
                                    <?php if ($email['has_attachments']): ?>
                                        <i class="fas fa-paperclip text-muted"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="email-date">
                                    <?php 
                                    $date = new DateTime($email['email_date']);
                                    $now = new DateTime();
                                    $diff = $now->diff($date);
                                    
                                    if ($diff->days === 0) {
                                        echo $date->format('H:i');
                                    } elseif ($diff->days === 1) {
                                        echo 'Hier';
                                    } elseif ($diff->days < 7) {
                                        echo $date->format('l');
                                    } else {
                                        echo $date->format('d/m/Y');
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?><?php echo $selectedConfigId ? '&config_id='.$selectedConfigId : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
                <?php endif; // Fin de if (!$error_message) ?>
                
            </div>
        </div>
    </div>
    
    <!-- Modal Visualisation Email -->
    <div class="modal fade" id="emailViewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailSubject">Chargement...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="emailViewBody">
                    <div class="text-center p-5">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-primary" onclick="replyEmail()">
                        <i class="fas fa-reply"></i> Répondre
                    </button>
                    <button class="btn btn-outline-secondary" onclick="forwardEmail()">
                        <i class="fas fa-share"></i> Transférer
                    </button>
                    <button class="btn btn-outline-danger" onclick="deleteEmail()">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
    let currentEmailId = null;
    
    // Ouvrir automatiquement l'email depuis une notification (sécurisé)
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($email_to_open): ?>
            // Ouvrir l'email de manière sécurisée
            setTimeout(function() {
                viewEmail(<?php echo $email_to_open; ?>);
            }, 500);
        <?php endif; ?>
    });
    
    function changeConfig(configId) {
        const currentUrl = new URL(window.location.href);
        if (configId) {
            currentUrl.searchParams.set('config_id', configId);
        } else {
            currentUrl.searchParams.delete('config_id');
        }
        currentUrl.searchParams.delete('page');
        window.location.href = currentUrl.toString();
    }
    
    function syncAllEmails() {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Synchronisation...';
        
        fetch('api/email-sync.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'sync_all'})
        })
        .then(r => r.json())
        .then(data => {
            console.log('Sync result:', data);
            
            if (data.success) {
                if (data.stats.emails_new > 0) {
                    alert(`✓ Synchronisation réussie!\n${data.stats.emails_new} nouveaux emails récupérés`);
                    location.reload();
                } else {
                    let message = '✓ Synchronisation terminée\n';
                    message += `Aucun nouvel email\n`;
                    message += `Total emails synchronisés: ${data.stats.emails_synced}`;
                    
                    // Afficher les détails des erreurs si présentes
                    if (data.results) {
                        const errors = data.results.filter(r => !r.success);
                        if (errors.length > 0) {
                            message += '\n\n⚠️ Erreurs de configuration:\n';
                            errors.forEach(err => {
                                message += `\n- ${err.email}: ${err.error || err.message}`;
                            });
                        }
                    }
                    
                    alert(message);
                }
            } else {
                let errorMsg = '❌ Erreur: ' + (data.message || 'Erreur inconnue');
                
                // Afficher les détails des erreurs
                if (data.results) {
                    errorMsg += '\n\nDétails:';
                    data.results.forEach(result => {
                        if (!result.success) {
                            errorMsg += `\n- ${result.email}: ${result.error || result.message}`;
                        }
                    });
                }
                
                alert(errorMsg);
                console.error('Sync errors:', data);
            }
        })
        .catch(e => {
            alert('❌ Erreur de synchronisation: ' + e.message);
            console.error('Sync exception:', e);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }
    
    function viewEmail(emailId) {
        currentEmailId = emailId;
        const modal = new bootstrap.Modal(document.getElementById('emailViewModal'));
        modal.show();
        
        fetch(`api/email-operations.php?action=get&id=${emailId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const email = data.email;
                    let body = email.body || '';
                    
                    // Nettoyer les caractères de contrôle et binaires
                    body = cleanEmailBody(body);
                    
                    // Si le body est du HTML, l'utiliser tel quel
                    // Sinon le traiter comme du texte
                    if (!body.includes('<html') && !body.includes('<div') && !body.includes('<p')) {
                        body = escapeHtml(body).replace(/\n/g, '<br>');
                    }
                    
                    document.getElementById('emailSubject').textContent = email.subject;
                    document.getElementById('emailViewBody').innerHTML = `
                        <div class="email-header-viewer">
                            <div class="mb-2">
                                <strong>De:</strong> ${escapeHtml(email.from_name)} &lt;${escapeHtml(email.from_address)}&gt;
                            </div>
                            <div class="mb-2">
                                <strong>À:</strong> ${escapeHtml(email.to_address)}
                            </div>
                            <div class="mb-2">
                                <strong>Date:</strong> ${formatDate(email.email_date)}
                            </div>
                            ${email.has_attachments ? '<div class="mb-2"><i class="fas fa-paperclip"></i> Pièces jointes</div>' : ''}
                        </div>
                        <div class="email-body-content">
                            ${body}
                        </div>
                    `;
                    
                    // Marquer comme lu
                    if (!email.is_read) {
                        markAsRead(emailId);
                    }
                } else {
                    document.getElementById('emailViewBody').innerHTML = 
                        '<div class="alert alert-danger">Erreur lors du chargement de l\'email</div>';
                }
            })
            .catch(e => {
                document.getElementById('emailViewBody').innerHTML = 
                    '<div class="alert alert-danger">Erreur: ' + e.message + '</div>';
            });
    }
    
    /**
     * Nettoyer le contenu d'un email des caractères de contrôle
     */
    function cleanEmailBody(body) {
        if (typeof body !== 'string') return '';
        
        // Supprimer les caractères de contrôle (sauf \n, \r, \t)
        body = body.replace(/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\x9F]/g, '');
        
        // Détecter si c'est du base64 mal décodé (beaucoup de caractères non-ASCII)
        const nonAsciiCount = (body.match(/[^\x20-\x7E\n\r\t]/g) || []).length;
        if (nonAsciiCount > body.length * 0.3) {
            // Plus de 30% de caractères non-ASCII, probablement corrompu
            return '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Le contenu de cet email ne peut pas être affiché correctement (encodage non supporté).</div>';
        }
        
        return body;
    }
    
    function markAsRead(emailId) {
        fetch('api/email-operations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'mark_read', id: emailId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'interface
                const item = document.querySelector(`[data-email-id="${emailId}"]`);
                if (item) {
                    item.classList.remove('unread');
                    const badge = item.querySelector('.badge');
                    if (badge) badge.remove();
                }
            }
        });
    }
    
    function composeEmail() {
        window.location.href = 'email-compose.php';
    }
    
    function replyEmail() {
        window.location.href = `email-compose.php?reply=${currentEmailId}`;
    }
    
    function forwardEmail() {
        window.location.href = `email-compose.php?forward=${currentEmailId}`;
    }
    
    function deleteEmail() {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet email ?')) return;
        
        fetch('api/email-operations.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', id: currentEmailId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Email supprimé');
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        });
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleString('fr-FR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    </script>
</body>
</html>
