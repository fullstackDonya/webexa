<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de sécurité : l'utilisateur doit être connecté
if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/pipeline_utils.php';

$entityParam = $_GET['entity'] ?? 'opportunity';
$entityType = pipeline_normalize_entity_type($entityParam) ?? 'opportunity';
$entityLabel = pipeline_entity_label($entityType);

$entityTabs = [
    'opportunity' => 'Opportunités',
    'mission' => 'Missions',
    'lead' => 'Leads',
    'campaign' => 'Campagnes',
];

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pipeline <?php echo htmlspecialchars($entityLabel); ?> | CRM Intelligent</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .pipeline-wrapper {
            min-height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
        }
        .pipeline-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }
        .pipeline-board {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            width: 100%;
        }
        .pipeline-column {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 220px);
        }
        .pipeline-column-header {
            border-radius: 10px;
            color: #fff;
            padding: 12px 14px;
            margin-bottom: 14px;
            background: linear-gradient(120deg, rgba(13,110,253,0.85), rgba(13,110,253,0.55));
        }
        .pipeline-column-header h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .pipeline-column-header small {
            opacity: 0.9;
        }
        .pipeline-items {
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding-right: 6px;
        }
        .pipeline-items.drag-over {
            background: rgba(13,110,253,0.08);
            border-radius: 10px;
        }
        .pipeline-card {
            background: #fff;
            border-radius: 10px;
            padding: 14px;
            box-shadow: 0 2px 6px rgba(15,23,42,0.08);
            cursor: grab;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .pipeline-card:active {
            cursor: grabbing;
        }
        .pipeline-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(15,23,42,0.12);
        }
        .pipeline-card h6 {
            margin: 0 0 6px;
            font-size: 15px;
            font-weight: 600;
            color: #0b1729;
        }
        .pipeline-card .card-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .pipeline-card .card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 12px;
            color: #475569;
        }
        .pipeline-card .badge-amount {
            background: #0d6efd;
            color: #fff;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .pipeline-drop-placeholder {
            border: 2px dashed rgba(13,110,253,0.4);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 12px;
            opacity: 0.7;
        }
        .pipeline-tabs .nav-link {
            border-radius: 30px;
            padding: 8px 18px;
            font-weight: 500;
        }
        .pipeline-tabs .nav-link.active {
            background-color: #0d6efd;
            color: #fff;
        }
        .pipeline-empty {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #94a3b8;
            border: 1px dashed #cbd5f5;
            border-radius: 10px;
            padding: 16px;
        }
        @media (max-width: 992px) {
            .pipeline-board {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        <div class="main-content">
            <div class="container-fluid pipeline-wrapper">
                <div class="pipeline-toolbar">
                    <div>
                        <h1 class="h3 text-gray-800 mb-1">
                            <i class="fas fa-project-diagram text-primary"></i>
                            Pipeline <?php echo htmlspecialchars($entityLabel); ?>
                        </h1>
                        <p class="text-muted mb-0">Glissez-déposez les cartes pour faire avancer vos <?php echo strtolower($entityLabel); ?> comme dans Pipedrive.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-outline-secondary" id="pipeline-refresh">
                            <i class="fas fa-rotate"></i> Actualiser
                        </button>
                        <?php if ($entityType === 'opportunity'): ?>
                            <a href="opportunities-add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nouvelle opportunité</a>
                        <?php elseif ($entityType === 'mission'): ?>
                            <a href="mission_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nouvelle mission</a>
                        <?php elseif ($entityType === 'lead'): ?>
                            <a href="leads-add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau lead</a>
                        <?php else: ?>
                            <a href="campaigns-add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nouvelle campagne</a>
                        <?php endif; ?>
                    </div>
                </div>

                <ul class="nav pipeline-tabs mb-3">
                    <?php foreach ($entityTabs as $key => $label): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $entityType === $key ? 'active' : ''; ?>" href="pipeline-board.php?entity=<?php echo urlencode($key); ?>">
                                <?php echo htmlspecialchars($label); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div id="pipeline-board" class="pipeline-board"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.PIPELINE_ENTITY = '<?php echo $entityType; ?>';
        window.PIPELINE_LABEL = '<?php echo addslashes($entityLabel); ?>';
        console.log('PHP - PIPELINE_ENTITY défini à:', window.PIPELINE_ENTITY);
    </script>
    <script src="assets/js/pipeline_board.js?v=<?php echo time(); ?>" defer></script>
</body>
</html>