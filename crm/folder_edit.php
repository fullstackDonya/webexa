<?php
// filepath: /Applications/MAMP/htdocs/PP/webitech/WEB/crm/folder_edit.php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/includes/verify_subscriptions.php'; // fournit $pdo, $customer_id, $companies, $user, $customer, $folders



$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    http_response_code(403);
    die('Accès non autorisé.');
}

// Charger les companies autorisées (externes uniquement)
$companies = [];
try {
    $cstmt = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = :cust AND interne_customer = 0 ORDER BY name ASC");
    $cstmt->execute([':cust' => $customer_id]);
    $companies = $cstmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $companies = [];
}

// id du dossier
$folder_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
if ($folder_id <= 0) {
    http_response_code(400);
    die('ID de dossier manquant.');
}

// charger le dossier (et vérifier qu'il appartient bien au même customer via company)
try {
    $stmt = $pdo->prepare("
        SELECT f.*, c.id AS company_id, c.name AS company_name
        FROM folders f
        JOIN companies c ON f.company_id = c.id
        WHERE f.id = :fid AND c.customer_id = :cust
        LIMIT 1
    ");
    $stmt->execute([':fid' => $folder_id, ':cust' => $customer_id]);
    $folder = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    error_log('folder_edit load folder error: '.$e->getMessage());
    $folder = null;
}
if (!$folder) {
    http_response_code(404);
    die('Dossier introuvable ou non autorisé.');
}

// charger statuses pour le select
$statuses = [];
try {
    $s = $pdo->query("SELECT id, name FROM statuses ORDER BY name ASC");
    $statuses = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    $statuses = [];
}

// traiter POST (mise à jour)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // collecte + sanitize
    $name = str_s($_POST['name'] ?? $folder['name'], 255);
    $company_id = isset($_POST['company_id']) && $_POST['company_id'] !== '' ? intval($_POST['company_id']) : null;
    $status_id = isset($_POST['status_id']) && $_POST['status_id'] !== '' ? intval($_POST['status_id']) : null;
    $description = trim((string)($_POST['description'] ?? $folder['description'] ?? ''));

    // valider company_id : doit appartenir au même customer
    $validCompany = false;
    if ($company_id) {
        if (!empty($companies)) {
            foreach ($companies as $c) {
                if (isset($c['id']) && intval($c['id']) === $company_id) { $validCompany = true; break; }
            }
        }
        if (!$validCompany) {
            try {
                $cChk = $pdo->prepare("SELECT id FROM companies WHERE id = :cid AND customer_id = :cust AND interne_customer = 0 LIMIT 1");
                $cChk->execute([':cid' => $company_id, ':cust' => $customer_id]);
                if ($cChk->fetchColumn()) $validCompany = true;
            } catch (Throwable $e) { /* ignore */ }
        }
    }
    if (!$validCompany) {
        // conserver l'ancienne entreprise si soumis invalide
        $company_id = $folder['company_id'] ?? null;
        $_SESSION['flash_error'] = "Entreprise sélectionnée invalide ou non autorisée. Valeur conservée.";
    }

    // validation minimale
    if ($name === '') {
        $_SESSION['flash_error'] = 'Le nom du dossier est requis.';
        header('Location: folder_edit.php?id=' . urlencode($folder_id));
        exit;
    }

    // exécuter l'UPDATE
    try {
        $sql = "UPDATE folders SET
                    name = :name,
                    company_id = :company_id,
                    status_id = :status_id,
                    description = :description,
                    updated_at = NOW()
                WHERE id = :id
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $name,
            ':company_id' => $company_id,
            ':status_id' => $status_id,
            ':description' => $description,
            ':id' => $folder_id
        ]);
        header('Location: folder_view.php?id=' . urlencode($folder_id) . '&saved=1');
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            error_log('FK error on folder update: '.$e->getMessage());
            $_SESSION['flash_error'] = "Impossible d'enregistrer : contrainte référentielle.";
            header('Location: folder_edit.php?id=' . urlencode($folder_id));
            exit;
        }
        error_log('folder_edit save error: '.$e->getMessage());
        $_SESSION['flash_error'] = 'Erreur lors de la sauvegarde.';
        header('Location: folder_edit.php?id=' . urlencode($folder_id));
        exit;
    }
}

// affichage du formulaire
$page_title = "Modifier Dossier";
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?php echo h($page_title); ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4>Modifier le dossier</h4>
                    <?php if (!empty($_SESSION['flash_error'])): ?>
                        <div class="alert alert-danger"><?php echo h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="id" value="<?php echo (int)$folder_id; ?>">
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo h($folder['name']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Entreprise</label>
                            <select name="company_id" class="form-control">
                                <?php foreach ($companies as $c): ?>
                                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)$c['id'] === (int)$folder['company_id'] ? 'selected' : ''); ?>>
                                        <?php echo h($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="status_id" class="form-control">
                                <option value="">— Aucun —</option>
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?php echo (int)$st['id']; ?>" <?php echo ((int)$st['id'] === (int)($folder['status_id'] ?? 0) ? 'selected' : ''); ?>>
                                        <?php echo h($st['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo h($folder['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="folder_view.php?id=<?php echo urlencode($folder_id); ?>" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
?>
```// filepath: /Applications/MAMP/htdocs/PP/webitech/WEB/crm/folder_edit.php
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// charger bootstrap app / vérifs
if (!file_exists(__DIR__ . '/includes/folders.php')) {
    die('Fichier includes manquant.');
}
require_once __DIR__ . '/includes/folders.php'; // fournit $pdo, $customer_id, $companies, $user, $customer, $folders

// helper
function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function str_s($v, $max = 255) {
    $s = trim((string)($v ?? ''));
    if (mb_strlen($s) > $max) $s = mb_substr($s, 0, $max);
    return $s;
}

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) {
    http_response_code(403);
    die('Accès non autorisé.');
}

// id du dossier
$folder_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
if ($folder_id <= 0) {
    http_response_code(400);
    die('ID de dossier manquant.');
}

// charger le dossier (et vérifier qu'il appartient bien au même customer via company)
try {
    $stmt = $pdo->prepare("
        SELECT f.*, c.id AS company_id, c.name AS company_name
        FROM folders f
        JOIN companies c ON f.company_id = c.id
        WHERE f.id = :fid AND c.customer_id = :cust
        LIMIT 1
    ");
    $stmt->execute([':fid' => $folder_id, ':cust' => $customer_id]);
    $folder = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    error_log('folder_edit load folder error: '.$e->getMessage());
    $folder = null;
}
if (!$folder) {
    http_response_code(404);
    die('Dossier introuvable ou non autorisé.');
}

// charger statuses pour le select
$statuses = [];
try {
    $s = $pdo->query("SELECT id, name FROM statuses ORDER BY name ASC");
    $statuses = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Throwable $e) {
    $statuses = [];
}

// traiter POST (mise à jour)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // collecte + sanitize
    $name = str_s($_POST['name'] ?? $folder['name'], 255);
    $company_id = isset($_POST['company_id']) && $_POST['company_id'] !== '' ? intval($_POST['company_id']) : null;
    $status_id = isset($_POST['status_id']) && $_POST['status_id'] !== '' ? intval($_POST['status_id']) : null;
    $description = trim((string)($_POST['description'] ?? $folder['description'] ?? ''));

    // valider company_id : doit appartenir au même customer
    $validCompany = false;
    if ($company_id) {
        if (!empty($companies)) {
            foreach ($companies as $c) {
                if (isset($c['id']) && intval($c['id']) === $company_id) { $validCompany = true; break; }
            }
        }
        if (!$validCompany) {
            try {
                $cChk = $pdo->prepare("SELECT id FROM companies WHERE id = :cid AND customer_id = :cust LIMIT 1");
                $cChk->execute([':cid' => $company_id, ':cust' => $customer_id]);
                if ($cChk->fetchColumn()) $validCompany = true;
            } catch (Throwable $e) { /* ignore */ }
        }
    }
    if (!$validCompany) {
        // conserver l'ancienne entreprise si soumis invalide
        $company_id = $folder['company_id'] ?? null;
        $_SESSION['flash_error'] = "Entreprise sélectionnée invalide ou non autorisée. Valeur conservée.";
    }

    // validation minimale
    if ($name === '') {
        $_SESSION['flash_error'] = 'Le nom du dossier est requis.';
        header('Location: folder_edit.php?id=' . urlencode($folder_id));
        exit;
    }

    // exécuter l'UPDATE
    try {
        $sql = "UPDATE folders SET
                    name = :name,
                    company_id = :company_id,
                    status_id = :status_id,
                    description = :description,
                    updated_at = NOW()
                WHERE id = :id
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $name,
            ':company_id' => $company_id,
            ':status_id' => $status_id,
            ':description' => $description,
            ':id' => $folder_id
        ]);
        header('Location: folder_view.php?id=' . urlencode($folder_id) . '&saved=1');
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            error_log('FK error on folder update: '.$e->getMessage());
            $_SESSION['flash_error'] = "Impossible d'enregistrer : contrainte référentielle.";
            header('Location: folder_edit.php?id=' . urlencode($folder_id));
            exit;
        }
        error_log('folder_edit save error: '.$e->getMessage());
        $_SESSION['flash_error'] = 'Erreur lors de la sauvegarde.';
        header('Location: folder_edit.php?id=' . urlencode($folder_id));
        exit;
    }
}

// affichage du formulaire
$page_title = "Modifier Dossier";
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?php echo h($page_title); ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="wrapper">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4>Modifier le dossier</h4>
                    <?php if (!empty($_SESSION['flash_error'])): ?>
                        <div class="alert alert-danger"><?php echo h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <input type="hidden" name="id" value="<?php echo (int)$folder_id; ?>">
                        <div class="mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo h($folder['name']); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Entreprise</label>
                            <select name="company_id" class="form-control">
                                <?php foreach ($companies as $c): ?>
                                    <option value="<?php echo (int)$c['id']; ?>" <?php echo ((int)$c['id'] === (int)$folder['company_id'] ? 'selected' : ''); ?>>
                                        <?php echo h($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="status_id" class="form-control">
                                <option value="">— Aucun —</option>
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?php echo (int)$st['id']; ?>" <?php echo ((int)$st['id'] === (int)($folder['status_id'] ?? 0) ? 'selected' : ''); ?>>
                                        <?php echo h($st['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo h($folder['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="folder_view.php?id=<?php echo urlencode($folder_id); ?>" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
?>