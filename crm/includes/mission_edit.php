<?php

if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

// helper
function str_s($v, $max = 255) {
    $s = trim((string)($v ?? ''));
    if (mb_strlen($s) > $max) $s = mb_substr($s, 0, $max);
    return $s;
}

$user_id = $_SESSION['user_id'] ?? null;
$customer_id = $_SESSION['customer_id'] ?? null;

// charger folders/users/statuses filtrés par customer_id
$folders = $users = $statuses = $companies = [];
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        if ($customer_id) {
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

            $ustmt = $pdo->prepare("SELECT id, username FROM users WHERE customer_id = :customer_id ORDER BY username ASC");
            $ustmt->execute([':customer_id' => $customer_id]);
            $users = $ustmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $s = $pdo->query("SELECT id, name FROM statuses WHERE type='mission' ORDER BY name ASC");
        $statuses = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
    }
} catch (Throwable $e) {
    error_log('mission_edit lists load error: '.$e->getMessage());
}

// charger la mission
$mission = null;
$mission_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
if ($mission_id <= 0) { http_response_code(400); exit('ID manquant'); }
try {
    $stmt = $pdo->prepare("SELECT * FROM missions WHERE id = ? LIMIT 1");
    $stmt->execute([$mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log($e->getMessage());
}
if (!$mission) { http_response_code(404); exit('Mission introuvable'); }

// unique POST handler (lit status_id et met à jour)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // collect + sanitize
    $folder_id_post = isset($_POST['folder_id']) && $_POST['folder_id'] !== '' ? intval($_POST['folder_id']) : null;
    $folder_id = $folder_id_post ?? $mission['folder_id'];
    $name = str_s($_POST['name'] ?? $mission['name'], 255);
    $description = trim((string)($_POST['description'] ?? $mission['description'] ?? ''));
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $datetime = !empty($_POST['datetime']) ? str_replace('T', ' ', $_POST['datetime']) : null;
    if ($datetime && !preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $datetime)) $datetime .= ':00';
    $departure = str_s($_POST['departure'] ?? $mission['departure'], 255);
    $arrival = str_s($_POST['arrival'] ?? $mission['arrival'], 255);
    $driver = str_s($_POST['driver'] ?? $mission['driver'], 255);
    $vehicle = str_s($_POST['vehicle'] ?? $mission['vehicle'], 255);
    $prix = isset($_POST['prix']) && $_POST['prix'] !== '' ? floatval(str_replace(',', '.', $_POST['prix'])) : (isset($mission['prix']) ? floatval($mission['prix']) : 0.0);
    $status_id = isset($_POST['status_id']) && $_POST['status_id'] !== '' ? intval($_POST['status_id']) : null;
    $type = str_s($_POST['type'] ?? $mission['type'] ?? 'vtc', 50);
    $project = str_s($_POST['project'] ?? $mission['project'], 255);
    $product = str_s($_POST['product'] ?? $mission['product'], 255);
    $quantity = isset($_POST['quantity']) && $_POST['quantity'] !== '' ? intval($_POST['quantity']) : null;
    $assigned_to = isset($_POST['assigned_to']) && $_POST['assigned_to'] !== '' ? intval($_POST['assigned_to']) : null;

    // validate assigned_to (must belong to same customer)
    if ($assigned_to) {
        $validUser = false;
        foreach ($users as $u) { if (intval($u['id']) === $assigned_to) { $validUser = true; break; } }
        if (!$validUser) {
            try {
                $uChk = $pdo->prepare("SELECT id FROM users WHERE id = :uid AND customer_id = :cust LIMIT 1");
                $uChk->execute([':uid' => $assigned_to, ':cust' => $customer_id]);
                if ($uChk->fetchColumn()) $validUser = true;
            } catch (Throwable $e) { /* ignore */ }
        }
        if (!$validUser) $assigned_to = null;
    }

    // validate folder_id (must exist and belong to same customer)
    $validFolder = false;
    if ($folder_id) {
        foreach ($folders as $f) { if (intval($f['id']) === $folder_id) { $validFolder = true; break; } }
        if (!$validFolder) {
            try {
                $fChk = $pdo->prepare("
                    SELECT f.id FROM folders f
                    JOIN companies c ON f.company_id = c.id
                    WHERE f.id = :fid AND c.customer_id = :cust LIMIT 1
                ");
                $fChk->execute([':fid' => $folder_id, ':cust' => $customer_id]);
                if ($fChk->fetchColumn()) $validFolder = true;
            } catch (Throwable $e) { /* ignore */ }
        }
    }
    if (!$validFolder) {
        // conserver l'ancienne valeur pour éviter NOT NULL / FK errors
        $folder_id = $mission['folder_id'] ?? $folder_id_post;
        $_SESSION['flash_error'] = 'Dossier invalide ou non autorisé — la valeur n\'a pas été modifiée.';
    }

    // validate status_id exists (optional)
    if ($status_id !== null) {
        $validStatus = false;
        foreach ($statuses as $st) { if (intval($st['id']) === $status_id) { $validStatus = true; break; } }
        if (!$validStatus) $status_id = null;
    }

    // perform update
    $sql = "UPDATE missions SET
        folder_id = :folder_id,
        name = :name,
        description = :description,
        start_date = :start_date,
        end_date = :end_date,
        departure = :departure,
        arrival = :arrival,
        datetime = :datetime,
        driver = :driver,
        vehicle = :vehicle,
        prix = :prix,
        status_id = :status_id,
        type = :type,
        project = :project,
        product = :product,
        quantity = :quantity,
        assigned_to = :assigned_to,
        updated_at = NOW()
        WHERE id = :id
        LIMIT 1";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
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
            ':assigned_to' => $assigned_to,
            ':id' => $mission_id
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            error_log('FK error on mission update: ' . $e->getMessage());
            $_SESSION['flash_error'] = "Impossible d'enregistrer : dossier invalide ou contrainte référentielle.";
            header('Location: mission_edit.php?id=' . urlencode($mission_id));
            exit;
        }
        throw $e;
    }

    // reload mission
    $stmt = $pdo->prepare("SELECT * FROM missions WHERE id = ? LIMIT 1");
    $stmt->execute([$mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC) ?: $mission;

    header('Location: mission_view.php?id=' . urlencode($mission_id) . '&saved=1');
    exit;
}

?>