<?php

if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../config/database.php';


// helpers
function h($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function str_s($v, $max = 255) {
    $s = trim((string)($v ?? ''));
    if (mb_strlen($s) > $max) $s = mb_substr($s, 0, $max);
    return $s;
}

$user_id = $_SESSION['user_id'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? null;

// charger listes filtrées par customer_id
$folders = $statuses = $users = $companies = [];
if (isset($pdo) && $pdo instanceof PDO && $customer_id) {
    try {
        $cstmt = $pdo->prepare("SELECT id, name FROM companies WHERE customer_id = :customer_id ORDER BY name ASC");
        $cstmt->execute([':customer_id' => $customer_id]);
        $companies = $cstmt->fetchAll(PDO::FETCH_ASSOC);

        $fstmt = $pdo->prepare("
            SELECT f.id, f.name, f.company_id
            FROM folders f
            JOIN companies c ON f.company_id = c.id
            WHERE c.customer_id = :customer_id
            ORDER BY f.name ASC
        ");
        $fstmt->execute([':customer_id' => $customer_id]);
        $folders = $fstmt->fetchAll(PDO::FETCH_ASSOC);

        $s = $pdo->query("SELECT id, name FROM statuses WHERE type='mission'  ORDER BY name ASC");
        $statuses = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];

        // users appartenant au même customer (fallback si pas de colonne customer_id)
        $ustmt = $pdo->prepare("SELECT id, username FROM users WHERE customer_id = :customer_id ORDER BY username ASC");
        try {
            $ustmt->execute([':customer_id' => $customer_id]);
            $users = $ustmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $u = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
            $users = $u ? $u->fetchAll(PDO::FETCH_ASSOC) : [];
        }
    } catch (Throwable $e) {
        error_log('mission_add list load error: '.$e->getMessage());
        $folders = $folders ?: [];
        $statuses = $statuses ?: [];
        $users = $users ?: [];
        $companies = $companies ?: [];
    }
}

// traitement POST (création)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
    // collect + sanitize
    $name = str_s($_POST['name'] ?? '', 255);
    $folder_id = isset($_POST['folder_id']) && $_POST['folder_id'] !== '' ? intval($_POST['folder_id']) : null;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $status_id = isset($_POST['status_id']) && $_POST['status_id'] !== '' ? intval($_POST['status_id']) : null;
    $assigned_to = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? intval($_POST['assigned_to']) : null;
    $description = trim((string)($_POST['description'] ?? ''));
    $departure = str_s($_POST['departure'] ?? '', 255);
    $arrival = str_s($_POST['arrival'] ?? '', 255);
    $datetime = !empty($_POST['datetime']) ? str_replace('T',' ',$_POST['datetime']) : null;
    if ($datetime && !preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $datetime)) $datetime .= ':00';
    $driver = str_s($_POST['driver'] ?? '', 255);
    $vehicle = str_s($_POST['vehicle'] ?? '', 255);
    $prix = isset($_POST['prix']) && $_POST['prix'] !== '' ? floatval(str_replace(',','.', $_POST['prix'])) : 0.0;
    $type = str_s($_POST['type'] ?? 'vtc', 50);
    $project = str_s($_POST['project'] ?? '', 255);
    $product = str_s($_POST['product'] ?? '', 255);
    $quantity = isset($_POST['quantity']) && $_POST['quantity'] !== '' ? intval($_POST['quantity']) : null;


        $pdo->beginTransaction();

        // valider folder -> appartient au même customer
        if ($folder_id) {
            $chk = $pdo->prepare("
                SELECT f.id FROM folders f
                JOIN companies c ON f.company_id = c.id
                WHERE f.id = :fid AND c.customer_id = :cust
                LIMIT 1
            ");
            $chk->execute([':fid' => $folder_id, ':cust' => $customer_id]);
            if (!$chk->fetchColumn()) $folder_id = null;
        }

        // valider assigned_to -> même customer si possible
        if ($assigned_to) {
            try {
                $uChk = $pdo->prepare("SELECT id FROM users WHERE id = :uid AND customer_id = :cust LIMIT 1");
                $uChk->execute([':uid' => $assigned_to, ':cust' => $customer_id]);
                if (!$uChk->fetchColumn()) $assigned_to = null;
            } catch (Throwable $e) {
                // ignore si pas de colonne customer_id
            }
        }

        // insert mission
        $ins = $pdo->prepare("INSERT INTO missions
            (folder_id, name, description, start_date, end_date, departure, arrival, datetime, driver, vehicle, prix, status_id, type, project, product, quantity, assigned_to, created_at, updated_at)
            VALUES
            (:folder_id, :name, :description, :start_date, :end_date, :departure, :arrival, :datetime, :driver, :vehicle, :prix, :status_id, :type, :project, :product, :quantity, :assigned_to, NOW(), NOW())
        ");
        $ins->execute([
            ':folder_id' => $folder_id,
            ':name' => $name,
            ':description' => $description,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':departure' => $departure,
            ':arrival' => $arrival,
            ':datetime' => $datetime,
            ':driver' => $driver,
            ':vehicle' => $vehicle,
            ':prix' => $prix,
            ':status_id' => $status_id,
            ':type' => $type,
            ':project' => $project,
            ':product' => $product,
            ':quantity' => $quantity,
            ':assigned_to' => $assigned_to
        ]);

        $new_id = (int)$pdo->lastInsertId();
        $pdo->commit();

        header('Location: mission_view.php?id=' . urlencode($new_id) . '&created=1');
        exit;
    
}

// ...existing code...
?>