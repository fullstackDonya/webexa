<?php
// Récupérer l'ID de la mission depuis l'URL
$mission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Charger les infos de la mission, du dossier et de la société
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        s.name AS status_name,
        f.id AS folder_id,
        f.name AS folder_name,
        c.name AS company_name
    FROM missions m
    INNER JOIN folders f ON m.folder_id = f.id
    INNER JOIN companies c ON f.company_id = c.id
    LEFT JOIN statuses s ON m.status_id = s.id
    WHERE m.id = ?
");
$stmt->execute([$mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$mission) {
    echo "<div class='alert alert-danger'>Mission introuvable.</div>";
    exit;
}
