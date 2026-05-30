<?php


session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../../login.php');
    exit;
}

// Récupère le customer_id lié à ce user
$stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$customer_id = $stmt->fetchColumn();

if (!$customer_id) {
    header('Location: ../index.php?error=Profil client manquant');
    exit;
}

// Récupération et sécurisation des champs
$name_raw         = $_POST['name'] ?? '';
$name             = trim($name_raw);
$industry         = trim($_POST['industry'] ?? '');
$website_raw      = trim($_POST['website'] ?? '');
$website          = $website_raw !== '' ? filter_var($website_raw, FILTER_SANITIZE_URL) : null;
$phone            = trim($_POST['phone'] ?? '');
$email_raw        = trim($_POST['email'] ?? '');
$email            = $email_raw !== '' ? filter_var($email_raw, FILTER_VALIDATE_EMAIL) : null;
$address          = trim($_POST['address'] ?? '');
$city             = trim($_POST['city'] ?? '');
$postal_code      = trim($_POST['postal_code'] ?? '');
$country          = trim($_POST['country'] ?? 'France');

$employee_count_raw = trim((string)($_POST['employee_count'] ?? ''));
if ($employee_count_raw === '') {
    // valeur par défaut si vide — évite l'envoi d'une chaîne vide à la BDD
    $employee_count = 0;
} else {
    // garder uniquement chiffres et convertir
    $employee_count = is_numeric($employee_count_raw) ? (int)$employee_count_raw : 0;
}

$annual_revenue_raw = trim((string)($_POST['annual_revenue'] ?? ''));
if ($annual_revenue_raw === '') {
    $annual_revenue = null;
} else {
    // normaliser formats "1 234,56" ou "1234.56"
    $annual_revenue_clean = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], $annual_revenue_raw);
    $annual_revenue = is_numeric($annual_revenue_clean) ? (float)$annual_revenue_clean : null;
}

$status = trim($_POST['status'] ?? 'prospect');
$source = trim($_POST['source'] ?? '');
$notes  = trim($_POST['notes'] ?? '');

// validation minimale
if ($name === '') {
    header('Location: ../index.php?error=Nom de la société requis');
    exit;
}

try {
    // Vérifie si une société existe déjà avec ce nom
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE name = ?");
    $stmt->execute([$name]);
    $existing_company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_company) {
        // Elle existe : on met à jour le customer_id
        $stmt = $pdo->prepare("UPDATE companies SET customer_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$customer_id, $existing_company['id']]);
        header('Location: ../index.php');
        exit;
    }

    // Sinon, création de la société
    $sql = "INSERT INTO companies (
                name, industry, website, phone, email, address, city, postal_code, country,
                employee_count, annual_revenue, status, source, notes, customer_id, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
            )";
    $stmt = $pdo->prepare($sql);

    // binder explicitement les types pour éviter envois de chaînes vides sur colonnes numériques
    $stmt->bindValue(1, $name, PDO::PARAM_STR);
    $stmt->bindValue(2, $industry, PDO::PARAM_STR);
    $stmt->bindValue(3, $website, $website === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(4, $phone, PDO::PARAM_STR);
    $stmt->bindValue(5, $email, $email === false ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(6, $address, PDO::PARAM_STR);
    $stmt->bindValue(7, $city, PDO::PARAM_STR);
    $stmt->bindValue(8, $postal_code, PDO::PARAM_STR);
    $stmt->bindValue(9, $country, PDO::PARAM_STR);
    $stmt->bindValue(10, $employee_count, PDO::PARAM_INT);
    if ($annual_revenue === null) {
        $stmt->bindValue(11, null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(11, $annual_revenue, PDO::PARAM_STR); // DECIMAL/FLOAT stocké en string acceptable
    }
    $stmt->bindValue(12, $status, PDO::PARAM_STR);
    $stmt->bindValue(13, $source, PDO::PARAM_STR);
    $stmt->bindValue(14, $notes, PDO::PARAM_STR);
    $stmt->bindValue(15, $customer_id, PDO::PARAM_INT);

    $stmt->execute();

    header('Location: ../index.php');
    exit;
} catch (Throwable $e) {
    // log pour debug (ne pas exposer en prod)
    error_log('process_company error: ' . $e->getMessage());
    header('Location: ../index.php?error=Erreur%20lors%20de%20la%20création%20de%20la%20société');
    exit;
}
