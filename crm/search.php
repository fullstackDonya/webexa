<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$results = [
    'leads' => [],
    'companies' => [],
    'contacts' => [],
    'tasks' => [],
    'calls' => [],
    'campaigns' => [],
    'emails' => [],
    'opportunities' => [],
    'missions' => [],
    'folders' => []
];

$total_results = 0;

if (!empty($query) && strlen($query) >= 2) {
    $searchTerm = "%{$query}%";
    
    // Recherche dans Leads
    if ($filter === 'all' || $filter === 'leads') {
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, email, phone, status, score, source, created_at, position, notes
            FROM leads
            WHERE customer_id = ? 
            AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR position LIKE ? OR notes LIKE ? OR source LIKE ?)
            ORDER BY score DESC, created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$customer_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results['leads'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($results['leads']);
    }
    
    // Recherche dans Companies
    if ($filter === 'all' || $filter === 'companies' || $filter === 'customers') {
        $stmt = $pdo->prepare("
            SELECT id, name, email, phone, industry, status, created_at, city, website
            FROM companies
            WHERE customer_id = ? 
            AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR industry LIKE ? OR address LIKE ? OR city LIKE ?)
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$customer_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results['companies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($results['companies']);
    }
    
    // Recherche dans Contacts
    if ($filter === 'all' || $filter === 'contacts') {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.email, c.phone, c.company, c.position, c.created_at
            FROM contacts c
            INNER JOIN companies co ON c.company_id = co.id
            WHERE co.customer_id = ?
            AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.company LIKE ? OR c.position LIKE ?)
            ORDER BY c.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$customer_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results['contacts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($results['contacts']);
    }
    
    // Recherche dans Tasks
    if ($filter === 'all' || $filter === 'tasks') {
        $stmt = $pdo->prepare("
            SELECT id, title, description, priority, status, due_date, created_at
            FROM tasks
            WHERE customer_id = ? 
            AND (title LIKE ? OR description LIKE ?)
            ORDER BY 
                CASE priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                due_date ASC
            LIMIT 20
        ");
        $stmt->execute([$customer_id, $searchTerm, $searchTerm]);
        $results['tasks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($results['tasks']);
    }
    
    // Recherche dans Calls
    if ($filter === 'all' || $filter === 'calls') {
        $stmt = $pdo->prepare("
            SELECT id, contact_name, phone, notes, call_type, status, scheduled_time, created_at
            FROM call_reminders
            WHERE customer_id = ? 
            AND (contact_name LIKE ? OR phone LIKE ? OR notes LIKE ?)
            ORDER BY scheduled_time DESC
            LIMIT 20
        ");
        $stmt->execute([$customer_id, $searchTerm, $searchTerm, $searchTerm]);
        $results['calls'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($results['calls']);
    }
    
    // Recherche dans Campaigns
    if ($filter === 'all' || $filter === 'campaigns') {
        $stmt = $pdo->prepare("
            SELECT id, name, type, status, channel, scheduled_at, created_at, subject, sender_name
            FROM campaigns
            WHERE customer_id = ? 
            AND (name LIKE ? OR subject LIKE ? OR type LIKE ? OR sender_name LIKE ?)
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$customer_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results['campaigns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($results['campaigns']);
    }
    
    // Recherche dans Emails
    if ($filter === 'all' || $filter === 'emails') {
        $stmt = $pdo->prepare("
            SELECT id, subject, from_name, from_email, email_date, is_read, has_attachments
            FROM emails
            WHERE customer_id = ? 
            AND (subject LIKE ? OR from_name LIKE ? OR from_email LIKE ? OR body_text LIKE ?)
            ORDER BY email_date DESC
            LIMIT 20
        ");
        $stmt->execute([$customer_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $results['emails'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($results['emails']);
    }
    
    // Recherche dans Opportunities
    if ($filter === 'all' || $filter === 'opportunities') {
        $stmt = $pdo->prepare("
            SELECT id, title, amount, stage, probability, expected_close_date, description, created_at
            FROM opportunities
            WHERE customer_id = ? 
            AND (title LIKE ? OR description LIKE ? OR stage LIKE ?)
            ORDER BY amount DESC, created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$customer_id, $searchTerm, $searchTerm, $searchTerm]);
        $results['opportunities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($results['opportunities']);
    }
    
    // Recherche dans Missions
    if ($filter === 'all' || $filter === 'missions') {
        $stmt = $pdo->prepare("
            SELECT m.id, m.name, m.description, m.status_id, m.start_date, m.end_date, m.created_at
            FROM missions m
            INNER JOIN folders f ON m.folder_id = f.id
            INNER JOIN companies c ON f.company_id = c.id
            WHERE c.customer_id = ? 
            AND (m.name LIKE ? OR m.description LIKE ?)
            ORDER BY m.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$customer_id, $searchTerm, $searchTerm]);
        $results['missions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($results['missions']);
    }
    
    // Recherche dans Folders
    if ($filter === 'all' || $filter === 'folders') {
        $stmt = $pdo->prepare("
            SELECT f.id, f.name, f.description, f.status_id, f.created_at
            FROM folders f
            INNER JOIN companies c ON f.company_id = c.id
            WHERE c.customer_id = ? 
            AND (f.name LIKE ? OR f.description LIKE ?)
            ORDER BY f.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$customer_id, $searchTerm, $searchTerm]);
        $results['folders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_results += count($results['folders']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche - <?= htmlspecialchars($query) ?> | CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #48bb78;
            --warning-color: #ed8936;
            --danger-color: #f56565;
            --info-color: #4299e1;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8edf2 100%);
            min-height: 100vh;
            padding-top: 76px;
        }

        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }

        .search-box {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 1rem 1.5rem 1rem 3.5rem;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50px;
            font-size: 1.1rem;
            background: rgba(255,255,255,0.95);
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .search-box input:focus {
            outline: none;
            border-color: white;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            background: white;
        }

        .search-box i {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1.3rem;
        }

        .search-stats {
            text-align: center;
            color: white;
            margin-top: 1.5rem;
            font-size: 1.1rem;
        }

        .search-stats strong {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .filters-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 0;
        }

        .filter-tab {
            padding: 0.65rem 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 25px;
            background: white;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-tab:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .filter-tab.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .filter-tab .badge {
            background: rgba(255,255,255,0.25);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .filter-tab:not(.active) .badge {
            background: #e2e8f0;
            color: #4a5568;
        }

        .results-section {
            margin-bottom: 2.5rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #e2e8f0;
        }

        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-header .icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .section-header .count {
            background: #e2e8f0;
            color: #4a5568;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.95rem;
            font-weight: 700;
        }

        .result-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
        }

        .result-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .result-card.lead { border-left-color: #667eea; }
        .result-card.customer { border-left-color: #48bb78; }
        .result-card.contact { border-left-color: #4299e1; }
        .result-card.task { border-left-color: #ed8936; }
        .result-card.call { border-left-color: #9f7aea; }
        .result-card.campaign { border-left-color: #f56565; }
        .result-card.email { border-left-color: #38b2ac; }
        .result-card.opportunity { border-left-color: #ecc94b; }
        .result-card.mission { border-left-color: #667eea; }
        .result-card.folder { border-left-color: #718096; }

        .result-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .result-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            color: #718096;
            font-size: 0.9rem;
            margin-top: 0.75rem;
        }

        .result-meta-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .result-description {
            color: #4a5568;
            margin-top: 0.75rem;
            line-height: 1.6;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-badge.new { background: #bee3f8; color: #2c5282; }
        .status-badge.contacted { background: #c6f6d5; color: #22543d; }
        .status-badge.qualified { background: #feebc8; color: #7c2d12; }
        .status-badge.converted { background: #9ae6b4; color: #22543d; }
        .status-badge.lost { background: #fed7d7; color: #742a2a; }
        .status-badge.active { background: #c6f6d5; color: #22543d; }
        .status-badge.inactive { background: #e2e8f0; color: #4a5568; }
        .status-badge.pending { background: #feebc8; color: #7c2d12; }
        .status-badge.completed { background: #9ae6b4; color: #22543d; }
        .status-badge.cancelled { background: #fed7d7; color: #742a2a; }
        .status-badge.in_progress { background: #bee3f8; color: #2c5282; }
        .status-badge.todo { background: #e2e8f0; color: #4a5568; }

        .priority-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .priority-badge.urgent { background: #fed7d7; color: #742a2a; }
        .priority-badge.high { background: #feebc8; color: #7c2d12; }
        .priority-badge.medium { background: #fefcbf; color: #744210; }
        .priority-badge.low { background: #e2e8f0; color: #4a5568; }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 5rem;
            color: #cbd5e0;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            font-size: 1.75rem;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1.1rem;
            color: #718096;
            max-width: 500px;
            margin: 0 auto;
        }

        .highlight {
            background: #fef5e7;
            padding: 0.1rem 0.3rem;
            border-radius: 3px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .search-header {
                padding: 1.5rem 0;
            }

            .search-box input {
                font-size: 1rem;
                padding: 0.85rem 1rem 0.85rem 3rem;
            }

            .filter-tabs {
                justify-content: center;
            }

            .result-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/topbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Search Header -->
    <div class="search-header">
        <div class="container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Rechercher dans le CRM..." value="<?= htmlspecialchars($query) ?>" autofocus>
            </div>
            <?php if (!empty($query)): ?>
            <div class="search-stats">
                <strong><?= $total_results ?></strong> résultat<?= $total_results > 1 ? 's' : '' ?> trouvé<?= $total_results > 1 ? 's' : '' ?> pour 
                <strong>"<?= htmlspecialchars($query) ?>"</strong>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container main-content">
        <!-- Filters -->
        <div class="filters-container">
            <div class="filter-tabs">
                <a href="?q=<?= urlencode($query) ?>&filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-th"></i> Tous
                    <?php if ($filter === 'all' && $total_results > 0): ?>
                    <span class="badge"><?= $total_results ?></span>
                    <?php endif; ?>
                </a>
                <a href="?q=<?= urlencode($query) ?>&filter=leads" class="filter-tab <?= $filter === 'leads' ? 'active' : '' ?>">
                    <i class="fas fa-user-plus"></i> Leads
                    <?php if (count($results['leads']) > 0): ?>
                    <span class="badge"><?= count($results['leads']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="?q=<?= urlencode($query) ?>&filter=companies" class="filter-tab <?= ($filter === 'companies' || $filter === 'customers') ? 'active' : '' ?>">
                    <i class="fas fa-building"></i> Entreprises
                    <?php if (count($results['companies']) > 0): ?>
                    <span class="badge"><?= count($results['companies']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="?q=<?= urlencode($query) ?>&filter=contacts" class="filter-tab <?= $filter === 'contacts' ? 'active' : '' ?>">
                    <i class="fas fa-address-book"></i> Contacts
                    <?php if (count($results['contacts']) > 0): ?>
                    <span class="badge"><?= count($results['contacts']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="?q=<?= urlencode($query) ?>&filter=tasks" class="filter-tab <?= $filter === 'tasks' ? 'active' : '' ?>">
                    <i class="fas fa-tasks"></i> Tâches
                    <?php if (count($results['tasks']) > 0): ?>
                    <span class="badge"><?= count($results['tasks']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="?q=<?= urlencode($query) ?>&filter=calls" class="filter-tab <?= $filter === 'calls' ? 'active' : '' ?>">
                    <i class="fas fa-phone"></i> Appels
                    <?php if (count($results['calls']) > 0): ?>
                    <span class="badge"><?= count($results['calls']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="?q=<?= urlencode($query) ?>&filter=campaigns" class="filter-tab <?= $filter === 'campaigns' ? 'active' : '' ?>">
                    <i class="fas fa-bullhorn"></i> Campagnes
                    <?php if (count($results['campaigns']) > 0): ?>
                    <span class="badge"><?= count($results['campaigns']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="?q=<?= urlencode($query) ?>&filter=emails" class="filter-tab <?= $filter === 'emails' ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i> Emails
                    <?php if (count($results['emails']) > 0): ?>
                    <span class="badge"><?= count($results['emails']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="?q=<?= urlencode($query) ?>&filter=opportunities" class="filter-tab <?= $filter === 'opportunities' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> Opportunités
                    <?php if (count($results['opportunities']) > 0): ?>
                    <span class="badge"><?= count($results['opportunities']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="?q=<?= urlencode($query) ?>&filter=missions" class="filter-tab <?= $filter === 'missions' ? 'active' : '' ?>">
                    <i class="fas fa-briefcase"></i> Missions
                    <?php if (count($results['missions']) > 0): ?>
                    <span class="badge"><?= count($results['missions']) ?></span>
                    <?php endif; ?>
                </a>
                <a href="?q=<?= urlencode($query) ?>&filter=folders" class="filter-tab <?= $filter === 'folders' ? 'active' : '' ?>">
                    <i class="fas fa-folder"></i> Dossiers
                    <?php if (count($results['folders']) > 0): ?>
                    <span class="badge"><?= count($results['folders']) ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <!-- Results -->
        <?php if (empty($query)): ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>Recherche Globale</h3>
            <p>Utilisez la barre de recherche ci-dessus pour trouver des leads, clients, contacts, tâches, campagnes et plus encore dans votre CRM.</p>
        </div>
        <?php elseif ($total_results === 0): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Aucun résultat trouvé</h3>
            <p>Aucun élément ne correspond à votre recherche "<?= htmlspecialchars($query) ?>". Essayez avec d'autres mots-clés.</p>
        </div>
        <?php else: ?>

            <!-- Leads Results -->
            <?php if (!empty($results['leads'])): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h2>Leads</h2>
                    <span class="count"><?= count($results['leads']) ?></span>
                </div>
                <?php foreach ($results['leads'] as $lead): ?>
                <a href="leads-view.php?id=<?= $lead['id'] ?>" class="result-card lead" style="text-decoration: none; color: inherit;">
                    <div class="result-title">
                        <i class="fas fa-user-circle" style="color: #667eea;"></i>
                        <?= htmlspecialchars(trim($lead['first_name'] . ' ' . $lead['last_name'])) ?>
                        <?php if (!empty($lead['position'])): ?>
                        <span style="color: #718096; font-size: 0.9rem; font-weight: 500;">
                            - <?= htmlspecialchars($lead['position']) ?>
                        </span>
                        <?php endif; ?>
                        <span class="status-badge <?= $lead['status'] ?>">
                            <?= htmlspecialchars($lead['status']) ?>
                        </span>
                    </div>
                    <div class="result-meta">
                        <?php if ($lead['email']): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-envelope"></i>
                            <?= htmlspecialchars($lead['email']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($lead['phone']): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-phone"></i>
                            <?= htmlspecialchars($lead['phone']) ?>
                        </div>
                        <?php endif; ?>
                        <div class="result-meta-item">
                            <i class="fas fa-star"></i>
                            Score: <strong><?= $lead['score'] ?></strong>
                        </div>
                        <?php if ($lead['source']): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-tag"></i>
                            <?= htmlspecialchars($lead['source']) ?>
                        </div>
                        <?php endif; ?>
                        <div class="result-meta-item">
                            <i class="far fa-clock"></i>
                            <?= date('d/m/Y', strtotime($lead['created_at'])) ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Companies Results -->
            <?php if (!empty($results['companies'])): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="icon" style="background: linear-gradient(135deg, #48bb78, #38a169);">
                        <i class="fas fa-building"></i>
                    </div>
                    <h2>Entreprises</h2>
                    <span class="count"><?= count($results['companies']) ?></span>
                </div>
                <?php foreach ($results['companies'] as $company): ?>
                <a href="customers-view.php?id=<?= $company['id'] ?>" class="result-card customer" style="text-decoration: none; color: inherit;">
                    <div class="result-title">
                        <i class="fas fa-building" style="color: #48bb78;"></i>
                        <?= htmlspecialchars($company['name']) ?>
                        <?php if (!empty($company['industry'])): ?>
                        <span style="color: #718096; font-size: 0.9rem; font-weight: 500;">
                            - <?= htmlspecialchars($company['industry']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="result-meta">
                        <?php if ($company['email']): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-envelope"></i>
                            <?= htmlspecialchars($company['email']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($company['phone']): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-phone"></i>
                            <?= htmlspecialchars($company['phone']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($company['city'])): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($company['city']) ?>
                        </div>
                        <?php endif; ?>
                        <div class="result-meta-item">
                            <i class="far fa-clock"></i>
                            <?= date('d/m/Y', strtotime($company['created_at'])) ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Contacts Results -->
            <?php if (!empty($results['contacts'])): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="icon" style="background: linear-gradient(135deg, #4299e1, #3182ce);">
                        <i class="fas fa-address-book"></i>
                    </div>
                    <h2>Contacts</h2>
                    <span class="count"><?= count($results['contacts']) ?></span>
                </div>
                <?php foreach ($results['contacts'] as $contact): ?>
                <a href="contacts-view.php?id=<?= $contact['id'] ?>" class="result-card contact" style="text-decoration: none; color: inherit;">
                    <div class="result-title">
                        <i class="fas fa-user" style="color: #4299e1;"></i>
                        <?= htmlspecialchars($contact['name']) ?>
                        <?php if ($contact['position']): ?>
                        <span style="color: #718096; font-size: 0.9rem; font-weight: 500;">
                            - <?= htmlspecialchars($contact['position']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="result-meta">
                        <?php if ($contact['company']): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-building"></i>
                            <?= htmlspecialchars($contact['company']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($contact['email']): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-envelope"></i>
                            <?= htmlspecialchars($contact['email']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($contact['phone']): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-phone"></i>
                            <?= htmlspecialchars($contact['phone']) ?>
                        </div>
                        <?php endif; ?>
                        <div class="result-meta-item">
                            <i class="far fa-clock"></i>
                            <?= date('d/m/Y', strtotime($contact['created_at'])) ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Tasks Results -->
            <?php if (!empty($results['tasks'])): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="icon" style="background: linear-gradient(135deg, #ed8936, #dd6b20);">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h2>Tâches</h2>
                    <span class="count"><?= count($results['tasks']) ?></span>
                </div>
                <?php foreach ($results['tasks'] as $task): ?>
                <a href="tasks.php?id=<?= $task['id'] ?>" class="result-card task" style="text-decoration: none; color: inherit;">
                    <div class="result-title">
                        <i class="fas fa-check-circle" style="color: #ed8936;"></i>
                        <?= htmlspecialchars($task['title']) ?>
                        <span class="priority-badge <?= $task['priority'] ?>">
                            <?= htmlspecialchars($task['priority']) ?>
                        </span>
                        <span class="status-badge <?= $task['status'] ?>">
                            <?= htmlspecialchars($task['status']) ?>
                        </span>
                    </div>
                    <?php if ($task['description']): ?>
                    <div class="result-description">
                        <?= nl2br(htmlspecialchars(substr($task['description'], 0, 200))) ?><?= strlen($task['description']) > 200 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                    <div class="result-meta">
                        <?php if ($task['due_date']): ?>
                        <div class="result-meta-item">
                            <i class="far fa-calendar"></i>
                            Échéance: <?= date('d/m/Y', strtotime($task['due_date'])) ?>
                        </div>
                        <?php endif; ?>
                        <div class="result-meta-item">
                            <i class="far fa-clock"></i>
                            Créé le <?= date('d/m/Y', strtotime($task['created_at'])) ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Calls Results -->
            <?php if (!empty($results['calls'])): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="icon" style="background: linear-gradient(135deg, #9f7aea, #805ad5);">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h2>Appels</h2>
                    <span class="count"><?= count($results['calls']) ?></span>
                </div>
                <?php foreach ($results['calls'] as $call): ?>
                <a href="calls.php?id=<?= $call['id'] ?>" class="result-card call" style="text-decoration: none; color: inherit;">
                    <div class="result-title">
                        <i class="fas fa-phone-alt" style="color: #9f7aea;"></i>
                        <?= htmlspecialchars($call['contact_name']) ?>
                        <span class="status-badge <?= $call['status'] ?>">
                            <?= htmlspecialchars($call['status']) ?>
                        </span>
                    </div>
                    <?php if ($call['notes']): ?>
                    <div class="result-description">
                        <?= nl2br(htmlspecialchars(substr($call['notes'], 0, 200))) ?><?= strlen($call['notes']) > 200 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                    <div class="result-meta">
                        <div class="result-meta-item">
                            <i class="fas fa-phone"></i>
                            <?= htmlspecialchars($call['phone']) ?>
                        </div>
                        <?php if ($call['call_type']): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-tag"></i>
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $call['call_type']))) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($call['scheduled_time']): ?>
                        <div class="result-meta-item">
                            <i class="far fa-calendar"></i>
                            <?= date('d/m/Y H:i', strtotime($call['scheduled_time'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Campaigns Results -->
            <?php if (!empty($results['campaigns'])): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="icon" style="background: linear-gradient(135deg, #f56565, #c53030);">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h2>Campagnes</h2>
                    <span class="count"><?= count($results['campaigns']) ?></span>
                </div>
                <?php foreach ($results['campaigns'] as $campaign): ?>
                <a href="campaigns-view.php?id=<?= $campaign['id'] ?>" class="result-card campaign" style="text-decoration: none; color: inherit;">
                    <div class="result-title">
                        <i class="fas fa-bullhorn" style="color: #f56565;"></i>
                        <?= htmlspecialchars($campaign['name']) ?>
                        <span class="status-badge <?= $campaign['status'] ?>">
                            <?= htmlspecialchars($campaign['status']) ?>
                        </span>
                    </div>
                    <?php if ($campaign['description']): ?>
                    <div class="result-description">
                        <?= nl2br(htmlspecialchars(substr($campaign['description'], 0, 200))) ?><?= strlen($campaign['description']) > 200 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                    <div class="result-meta">
                        <div class="result-meta-item">
                            <i class="fas fa-tag"></i>
                            <?= htmlspecialchars($campaign['type']) ?>
                        </div>
                        <?php if ($campaign['budget']): ?>
                        <div class="result-meta-item">
                            <i class="fas fa-euro-sign"></i>
                            Budget: <?= number_format($campaign['budget'], 2, ',', ' ') ?> €
                        </div>
                        <?php endif; ?>
                        <?php if ($campaign['start_date']): ?>
                        <div class="result-meta-item">
                            <i class="far fa-calendar"></i>
                            Du <?= date('d/m/Y', strtotime($campaign['start_date'])) ?>
                            <?php if ($campaign['end_date']): ?>
                            au <?= date('d/m/Y', strtotime($campaign['end_date'])) ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Emails Results -->
            <?php if (!empty($results['emails'])): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="icon" style="background: linear-gradient(135deg, #38b2ac, #319795);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h2>Emails</h2>
                    <span class="count"><?= count($results['emails']) ?></span>
                </div>
                <?php foreach ($results['emails'] as $email): ?>
                <a href="email-inbox.php?id=<?= $email['id'] ?>" class="result-card email" style="text-decoration: none; color: inherit;">
                    <div class="result-title">
                        <i class="fas fa-envelope<?= $email['is_read'] ? '-open' : '' ?>" style="color: #38b2ac;"></i>
                        <?= htmlspecialchars($email['subject']) ?>
                        <?php if (!$email['is_read']): ?>
                        <span class="status-badge new">Nouveau</span>
                        <?php endif; ?>
                        <?php if ($email['has_attachments']): ?>
                        <i class="fas fa-paperclip" style="color: #718096;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="result-meta">
                        <div class="result-meta-item">
                            <i class="fas fa-user"></i>
                            <?= htmlspecialchars($email['sender_name'] ?: $email['sender_email']) ?>
                        </div>
                        <div class="result-meta-item">
                            <i class="far fa-clock"></i>
                            <?= date('d/m/Y H:i', strtotime($email['received_at'])) ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Opportunities Results -->
            <?php if (!empty($results['opportunities'])): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="icon" style="background: linear-gradient(135deg, #ecc94b, #d69e2e);">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h2>Opportunités</h2>
                    <span class="count"><?= count($results['opportunities']) ?></span>
                </div>
                <?php foreach ($results['opportunities'] as $opportunity): ?>
                <a href="opportunities.php?id=<?= $opportunity['id'] ?>" class="result-card opportunity" style="text-decoration: none; color: inherit;">
                    <div class="result-title">
                        <i class="fas fa-trophy" style="color: #ecc94b;"></i>
                        <?= htmlspecialchars($opportunity['name']) ?>
                        <span style="color: #48bb78; font-weight: 700;">
                            <?= number_format($opportunity['value'], 2, ',', ' ') ?> €
                        </span>
                    </div>
                    <?php if ($opportunity['description']): ?>
                    <div class="result-description">
                        <?= nl2br(htmlspecialchars(substr($opportunity['description'], 0, 200))) ?><?= strlen($opportunity['description']) > 200 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                    <div class="result-meta">
                        <div class="result-meta-item">
                            <i class="fas fa-chart-pie"></i>
                            Étape: <?= htmlspecialchars($opportunity['stage']) ?>
                        </div>
                        <div class="result-meta-item">
                            <i class="fas fa-percentage"></i>
                            Probabilité: <?= $opportunity['probability'] ?>%
                        </div>
                        <?php if ($opportunity['expected_close_date']): ?>
                        <div class="result-meta-item">
                            <i class="far fa-calendar"></i>
                            Clôture prévue: <?= date('d/m/Y', strtotime($opportunity['expected_close_date'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Missions Results -->
            <?php if (!empty($results['missions'])): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h2>Missions</h2>
                    <span class="count"><?= count($results['missions']) ?></span>
                </div>
                <?php foreach ($results['missions'] as $mission): ?>
                <a href="mission_view.php?id=<?= $mission['id'] ?>" class="result-card mission" style="text-decoration: none; color: inherit;">
                    <div class="result-title">
                        <i class="fas fa-briefcase" style="color: #667eea;"></i>
                        <?= htmlspecialchars($mission['title']) ?>
                        <span class="status-badge <?= $mission['status'] ?>">
                            <?= htmlspecialchars($mission['status']) ?>
                        </span>
                        <?php if ($mission['priority']): ?>
                        <span class="priority-badge <?= $mission['priority'] ?>">
                            <?= htmlspecialchars($mission['priority']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($mission['description']): ?>
                    <div class="result-description">
                        <?= nl2br(htmlspecialchars(substr($mission['description'], 0, 200))) ?><?= strlen($mission['description']) > 200 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                    <div class="result-meta">
                        <?php if ($mission['start_date']): ?>
                        <div class="result-meta-item">
                            <i class="far fa-calendar"></i>
                            Du <?= date('d/m/Y', strtotime($mission['start_date'])) ?>
                            <?php if ($mission['end_date']): ?>
                            au <?= date('d/m/Y', strtotime($mission['end_date'])) ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="result-meta-item">
                            <i class="far fa-clock"></i>
                            Créé le <?= date('d/m/Y', strtotime($mission['created_at'])) ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Folders Results -->
            <?php if (!empty($results['folders'])): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="icon" style="background: linear-gradient(135deg, #718096, #4a5568);">
                        <i class="fas fa-folder"></i>
                    </div>
                    <h2>Dossiers</h2>
                    <span class="count"><?= count($results['folders']) ?></span>
                </div>
                <?php foreach ($results['folders'] as $folder): ?>
                <a href="folder_view.php?id=<?= $folder['id'] ?>" class="result-card folder" style="text-decoration: none; color: inherit;">
                    <div class="result-title">
                        <i class="fas fa-folder-open" style="color: #718096;"></i>
                        <?= htmlspecialchars($folder['name']) ?>
                        <?php if ($folder['status']): ?>
                        <span class="status-badge <?= $folder['status'] ?>">
                            <?= htmlspecialchars($folder['status']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($folder['description']): ?>
                    <div class="result-description">
                        <?= nl2br(htmlspecialchars(substr($folder['description'], 0, 200))) ?><?= strlen($folder['description']) > 200 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                    <div class="result-meta">
                        <div class="result-meta-item">
                            <i class="far fa-clock"></i>
                            Créé le <?= date('d/m/Y', strtotime($folder['created_at'])) ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit search on input change
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    window.location.href = `search.php?q=${encodeURIComponent(query)}<?= $filter !== 'all' ? '&filter=' . $filter : '' ?>`;
                }, 800);
            }
        });

        // Handle Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = e.target.value.trim();
                if (query.length >= 2) {
                    window.location.href = `search.php?q=${encodeURIComponent(query)}<?= $filter !== 'all' ? '&filter=' . $filter : '' ?>`;
                }
            }
        });
    </script>
</body>
</html>
