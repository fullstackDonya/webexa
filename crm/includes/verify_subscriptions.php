<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

if (!isset($user)) {
    // Si $user n'est pas encore défini, on le récupère
    if (function_exists('getCurrentUser')) {
        $user = getCurrentUser();
    } else {
        // Sécurité : si pas d'utilisateur, on redirige
        header('Location: ../login.php');
        exit;
    }
}

if (!isset($pdo)) {
    // Inclure la connexion PDO si besoin
    require_once __DIR__ . '/../config/database.php';
}

// Vérifier et initialiser customer_id dans la session si manquant
if (!isset($_SESSION['customer_id']) && isset($user['id'])) {
    $stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
    if ($userData && $userData['customer_id']) {
        $_SESSION['customer_id'] = $userData['customer_id'];
        error_log("customer_id initialisé dans la session: " . $userData['customer_id'] . " pour user_id: " . $user['id']);
    } else {
        error_log("ATTENTION: user_id " . $user['id'] . " n'a pas de customer_id dans la table users");
    }
}

// Vérifie un abonnement actif, essai ou free_trial non expiré
// $stmt = $pdo->prepare(
//     "SELECT * FROM user_subscriptions 
//      WHERE user_id = ? 
//        AND status IN ('active', 'trial', 'trialing', 'free_trial', 'essai_gratuit', 'canceled')
//        AND (end_date IS NULL OR end_date >= CURDATE())
//      LIMIT 1"
// );
// $stmt->execute([$user['id']]);
// $subscription = $stmt->fetch();

// if (!$subscription) {
//     // Redirige vers une page d'abonnement ou affiche un message
//     header('Location: ../subscription.php?error=abonnement');
//     exit;
// }