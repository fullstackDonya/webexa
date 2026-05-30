<?php
require_once __DIR__ . '/includes/verify_subscriptions.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customers-export-'.date('Ymd-His').'.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");

$customer_id = $_SESSION['customer_id'] ?? null;
if (!$customer_id) { http_response_code(401); echo "unauthenticated"; exit; }

$headers = ['ID','Nom','Email','Téléphone','Secteur','Revenus annuels','Statut','Actif','Score satisfaction','Créé le','Mis à jour le'];
fputcsv($out, $headers);

$sql = "SELECT id, name, email, phone, industry, annual_revenue, status, is_active, satisfaction_score, created_at, updated_at FROM companies WHERE customer_id = ? AND interne_customer = 0 ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $row['id'],
        $row['name'] ?? '',
        $row['email'] ?? '',
        $row['phone'] ?? '',
        $row['industry'] ?? '',
        $row['annual_revenue'] ?? '',
        $row['status'] ?? '',
        isset($row['is_active']) ? (int)$row['is_active'] : 0,
        $row['satisfaction_score'] ?? '',
        $row['created_at'] ?? '',
        $row['updated_at'] ?? '',
    ]);
}

fclose($out);
exit;