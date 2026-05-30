<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="opportunities-export-'.date('Ymd-His').'.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { http_response_code(401); echo 'unauthenticated'; exit; }

$customer_id = $_SESSION['customer_id'] ?? null;
$params = [':uid' => (int)$user_id];
$where = ['o.assigned_to = :uid'];
if ($customer_id) { $where[] = 'co.customer_id = :cid'; $params[':cid'] = (int)$customer_id; }
$whereSql = 'WHERE ' . implode(' AND ', $where);

$headers = ['ID','Titre','Entreprise','Étape','Montant','Créé le','Échéance prévue','Clôture effective'];
fputcsv($out, $headers);

$sql = "
    SELECT o.id, o.title, co.name AS company_name, o.stage, o.amount, o.created_at, o.expected_close_date, o.actual_close_date
    FROM opportunities o
    LEFT JOIN companies co ON o.company_id = co.id
    $whereSql
    ORDER BY o.created_at DESC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) { $stmt->bindValue($k,$v); }
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $row['id'], $row['title'] ?? '', $row['company_name'] ?? '', $row['stage'] ?? '',
        $row['amount'] ?? '', $row['created_at'] ?? '', $row['expected_close_date'] ?? '', $row['actual_close_date'] ?? ''
    ]);
}

fclose($out);
exit;