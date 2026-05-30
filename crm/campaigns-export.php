<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="campaigns-export-'.date('Ymd-His').'.csv"');

$out = fopen('php://output', 'w');
// UTF-8 BOM
fwrite($out, "\xEF\xBB\xBF");

$customer_id = $_SESSION['customer_id'] ?? null;
$search = trim($_GET['q'] ?? '');
$filter_status = $_GET['status'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_channel = $_GET['channel'] ?? '';

$conditions = [];
$params = [];
if ($customer_id) { $conditions[] = 'customer_id = ?'; $params[] = $customer_id; }
if ($search !== '') { $conditions[] = '(name LIKE ? OR subject LIKE ?)'; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
if ($filter_status !== '') { $conditions[] = 'status = ?'; $params[] = $filter_status; }
if ($filter_type !== '') { $conditions[] = 'type = ?'; $params[] = $filter_type; }
if ($filter_channel !== '') { $conditions[] = 'channel = ?'; $params[] = $filter_channel; }

$whereSql = $conditions ? (' WHERE ' . implode(' AND ', $conditions)) : '';

try {
    $sql = 'SELECT id, name, subject, type, channel, status, created_at, scheduled_at, sent_at, open_rate, click_rate, recipients_count FROM campaigns' . $whereSql . ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $headers = ['ID','Nom','Sujet','Type','Canal','Statut','Créé le','Programmé le','Envoyé le','Taux ouverture','Taux clic','Destinataires'];
    fputcsv($out, $headers);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $row['id'], $row['name'] ?? '', $row['subject'] ?? '', $row['type'] ?? '',
            $row['channel'] ?? '', $row['status'] ?? '', $row['created_at'] ?? '',
            $row['scheduled_at'] ?? '', $row['sent_at'] ?? '', $row['open_rate'] ?? '',
            $row['click_rate'] ?? '', $row['recipients_count'] ?? ''
        ]);
    }
} catch (Throwable $e) {
    // Fallback minimal set of columns
    $sql = 'SELECT id, name, subject, type, channel, status, created_at FROM campaigns' . $whereSql . ' ORDER BY created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $headers = ['ID','Nom','Sujet','Type','Canal','Statut','Créé le'];
    fputcsv($out, $headers);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $row['id'], $row['name'] ?? '', $row['subject'] ?? '', $row['type'] ?? '',
            $row['channel'] ?? '', $row['status'] ?? '', $row['created_at'] ?? ''
        ]);
    }
}

fclose($out);
exit;