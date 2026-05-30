<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="contacts-export-'.date('Ymd-His').'.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

$customer_id = $_SESSION['customer_id'] ?? null;
$params = [];
$where = [];
if ($customer_id) {
    $where[] = 'co.customer_id = :cid';
    $params[':cid'] = (int)$customer_id;
}
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

$headers = ['ID','Prénom','Nom','Email','Téléphone','Entreprise','Statut','Score IA','Dernière activité','Créé le'];
fputcsv($out, $headers);

$sql = "
    SELECT c.id, c.first_name, c.last_name, c.email, c.phone, co.name AS company_name,
           c.status, c.ai_score, c.last_activity, c.created_at
    FROM contacts c
    LEFT JOIN companies co ON c.company_id = co.id
    $whereSql
    ORDER BY c.created_at DESC
";

try {
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
    $stmt->execute();
} catch (Throwable $e) {
    $sql = "
        SELECT c.id, c.first_name, c.last_name, c.email, NULL as phone, co.name AS company_name,
               c.status, NULL as ai_score, NULL as last_activity, c.created_at
        FROM contacts c
        LEFT JOIN companies co ON c.company_id = co.id
        $whereSql
        ORDER BY c.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
    $stmt->execute();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $row['id'],
        $row['first_name'] ?? '',
        $row['last_name'] ?? '',
        $row['email'] ?? '',
        $row['phone'] ?? '',
        $row['company_name'] ?? '',
        $row['status'] ?? '',
        $row['ai_score'] ?? '',
        $row['last_activity'] ?? '',
        $row['created_at'] ?? '',
    ]);
}

fclose($out);
exit;