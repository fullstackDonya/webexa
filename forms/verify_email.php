<?php
require '../config/database.php'; // Assurez-vous que ce chemin est correct

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$token = $_GET['token'] ?? '';
if (!$token) {
    echo "Lien invalide.";
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email_verify_token = :token");
$stmt->execute([':token' => $token]);
$user = $stmt->fetch();

if ($user) {
    $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, email_verify_token = NULL WHERE id = :id");
    $stmt->execute([':id' => $user['id']]);


    $plan_id = 3; // Plan 3
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+7 days')); // 7 jours d'essai gratuit

    // Vérifier si l'utilisateur a déjà un abonnement actif sur ce plan

    $subStmt = $pdo->prepare("SELECT id FROM user_subscriptions WHERE user_id = :user_id AND plan_id = :plan_id AND status IN ('active', 'trial', 'free_trial', 'essai_gratuit', 'canceled')");
    $subStmt->execute([':user_id' => $user['id'], ':plan_id' => $plan_id]);
    $hasSub = $subStmt->fetch();

    if (!$hasSub) {
        // CORRECTION : Utiliser 'free_trial' au lieu de 'active' pour le mois gratuit de bienvenue
        // 'active' doit être réservé uniquement aux utilisateurs ayant payé
        $insertStmt = $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status, created_at, updated_at) VALUES (:user_id, :plan_id, :start_date, :end_date, 'free_trial', NOW(), NOW())");
               
        $insertStmt->execute([
            ':user_id' => $user['id'],
            ':plan_id' => $plan_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ]);
    }
    echo "Votre email a bien été vérifié ! Un essai gratuit de 7 jours a été activé.<br><a href='../login'>Se connecter</a>";


} else {
    echo "Lien de vérification invalide ou déjà utilisé.";
}