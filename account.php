<?php
/**
 * Webexa — Account Settings
 * Profile, security, company, notifications, API keys
 */

session_start();
require_once __DIR__ . '/crm/config/database.php';
require_once __DIR__ . '/crm/includes/auth.php';

if (!isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$user = getCurrentUser();
if (!$user) {
    header('Location: index.php');
    exit;
}

// Fetch company info
$company = [];
if (!empty($user['customer_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$user['customer_id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Handle POST actions
$flash = ['type' => '', 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Update profile ──────────────────────────────────────────────────────
    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name  = trim($_POST['last_name'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $position   = trim($_POST['position'] ?? '');

        if ($first_name && $last_name) {
            $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, position=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$first_name, $last_name, $phone, $position, $user['id']]);
            $user = getCurrentUser();
            $flash = ['type' => 'success', 'msg' => 'Profil mis à jour avec succès.'];
        } else {
            $flash = ['type' => 'error', 'msg' => 'Prénom et nom sont obligatoires.'];
        }
    }

    // ── Change password ──────────────────────────────────────────────────────
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new_pw   = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $flash = ['type' => 'error', 'msg' => 'Mot de passe actuel incorrect.'];
        } elseif (strlen($new_pw) < 8) {
            $flash = ['type' => 'error', 'msg' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'];
        } elseif ($new_pw !== $confirm) {
            $flash = ['type' => 'error', 'msg' => 'Les mots de passe ne correspondent pas.'];
        } else {
            $hash = password_hash($new_pw, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$hash, $user['id']]);
            $flash = ['type' => 'success', 'msg' => 'Mot de passe modifié avec succès.'];
        }
    }

    // ── Update company ───────────────────────────────────────────────────────
    if ($action === 'update_company' && !empty($user['customer_id'])) {
        $name     = trim($_POST['company_name'] ?? '');
        $website  = trim($_POST['website'] ?? '');
        $industry = trim($_POST['industry'] ?? '');
        $phone_c  = trim($_POST['company_phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');

        if ($name) {
            $stmt = $pdo->prepare("UPDATE customers SET name=?, website=?, industry=?, phone=?, address=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$name, $website, $industry, $phone_c, $address, $user['customer_id']]);
            $stmt2 = $pdo->prepare("SELECT * FROM customers WHERE id=?");
            $stmt2->execute([$user['customer_id']]);
            $company = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];
            $flash = ['type' => 'success', 'msg' => 'Informations entreprise mises à jour.'];
        } else {
            $flash = ['type' => 'error', 'msg' => 'Le nom de l\'entreprise est obligatoire.'];
        }
    }

    // ── Update notifications ─────────────────────────────────────────────────
    if ($action === 'update_notifications') {
        $prefs = json_encode([
            'email_leads'       => isset($_POST['notif_email_leads']),
            'email_deals'       => isset($_POST['notif_email_deals']),
            'email_tasks'       => isset($_POST['notif_email_tasks']),
            'browser_leads'     => isset($_POST['notif_browser_leads']),
            'browser_deals'     => isset($_POST['notif_browser_deals']),
            'digest_weekly'     => isset($_POST['notif_digest_weekly']),
        ]);
        $stmt = $pdo->prepare("UPDATE users SET notification_prefs=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$prefs, $user['id']]);
        $user = getCurrentUser();
        $flash = ['type' => 'success', 'msg' => 'Préférences de notification enregistrées.'];
    }
}

// Parse notification prefs
$notif = json_decode($user['notification_prefs'] ?? '{}', true) ?: [];

// Active tab from query string
$active_tab = $_GET['tab'] ?? 'profile';
$valid_tabs = ['profile', 'security', 'company', 'notifications', 'api'];
if (!in_array($active_tab, $valid_tabs)) $active_tab = 'profile';

// Generate avatar initials
$initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
$avatar_colors = ['#6C63FF', '#FF6584', '#00C48C', '#FFB800', '#0A84FF'];
$avatar_color  = $avatar_colors[ord($initials[0] ?? 'A') % count($avatar_colors)];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte — Webexa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52E0;
            --primary-light: #EEF0FF;
            --success: #00C48C;
            --danger: #FF4C4C;
            --warning: #FFB800;
            --dark: #1A1A2E;
            --text: #323338;
            --text-secondary: #676879;
            --border: #E6E9EF;
            --bg: #F7F8FC;
            --white: #FFFFFF;
            --sidebar-width: 260px;
            --topbar-height: 64px;
            --radius: 10px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.06);
            --shadow-md: 0 4px 16px rgba(0,0,0,.08);
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── TOPBAR ── */
        .topbar {
            height: var(--topbar-height);
            background: var(--white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
            gap: 16px;
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--dark);
        }

        .topbar-brand-icon {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--primary), #FF6584);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
        }

        .topbar-brand-name {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .topbar-sep { width: 1px; height: 22px; background: var(--border); margin: 0 4px; }

        .topbar-page-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-btn {
            display: flex; align-items: center; gap: 7px;
            padding: 8px 14px;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--text);
            text-decoration: none;
            transition: all .2s;
        }
        .topbar-btn:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }

        .avatar-sm {
            width: 34px; height: 34px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        /* ── PAGE LAYOUT ── */
        .page-layout {
            max-width: 1080px;
            margin: 0 auto;
            padding: 32px 24px 64px;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .page-header p { font-size: 14px; color: var(--text-secondary); }

        /* ── PROFILE CARD ── */
        .profile-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 28px;
            display: flex;
            align-items: center;
            gap: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .avatar-lg {
            width: 80px; height: 80px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            font-weight: 800;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(0,0,0,.15);
        }

        .profile-info h2 { font-size: 20px; font-weight: 800; color: var(--dark); }
        .profile-info .email { font-size: 14px; color: var(--text-secondary); margin-top: 2px; }
        .profile-info .role-badge {
            display: inline-flex; align-items: center; gap: 5px;
            margin-top: 8px;
            padding: 4px 10px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 100px;
            font-size: 12px;
            font-weight: 700;
        }

        .profile-card-actions { margin-left: auto; }

        /* ── TABS ── */
        .tabs-nav {
            display: flex;
            gap: 4px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 28px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .tabs-nav::-webkit-scrollbar { display: none; }

        .tab-link {
            display: flex; align-items: center; gap: 7px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius) var(--radius) 0 0;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: all .2s;
        }
        .tab-link:hover { color: var(--primary); background: var(--primary-light); }
        .tab-link.active { color: var(--primary); border-bottom-color: var(--primary); background: transparent; }
        .tab-link i { font-size: 14px; }

        /* ── PANELS ── */
        .tab-panel { display: none; }
        .tab-panel.active {
            display: block;
            animation: fadeUp .25s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── SECTION CARD ── */
        .section-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .section-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-card-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
        }

        .section-card-header p {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        .section-card-body { padding: 24px; }

        /* ── FORM ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-grid.single { grid-template-columns: 1fr; }
        .field-full { grid-column: 1 / -1; }

        .field { margin-bottom: 0; }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 7px;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            font-size: 14px;
            font-family: inherit;
            color: var(--text);
            background: var(--white);
            transition: all .2s;
            -webkit-appearance: none;
        }

        .field textarea { resize: vertical; min-height: 80px; }
        .field input:disabled { background: var(--bg); color: var(--text-secondary); }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108,99,255,.1);
        }

        .field .hint {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        .input-with-icon { position: relative; }
        .input-with-icon input { padding-right: 42px; }
        .input-with-icon .toggle-pw {
            position: absolute; right: 13px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-secondary); font-size: 14px; padding: 0;
        }
        .input-with-icon .toggle-pw:hover { color: var(--primary); }

        /* ── FORM ACTIONS ── */
        .form-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            margin-top: 20px;
        }

        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 20px;
            border-radius: var(--radius);
            font-size: 13px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            border: none;
            transition: all .2s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(108,99,255,.3);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(108,99,255,.4); }
        .btn-primary:disabled { background: #C8CAD8; box-shadow: none; cursor: not-allowed; transform: none; }

        .btn-ghost {
            background: var(--bg);
            color: var(--text);
            border: 1.5px solid var(--border);
        }
        .btn-ghost:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }

        .btn-danger {
            background: #FFF0F0;
            color: var(--danger);
            border: 1.5px solid #FFCCCC;
        }
        .btn-danger:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

        /* ── ALERT ── */
        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 16px;
            border-radius: var(--radius);
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .alert i { margin-top: 1px; flex-shrink: 0; }
        .alert.success { background: #EDFAF5; color: #00875A; border: 1px solid #C3F0DE; }
        .alert.error   { background: #FFF0F0; color: #CC2222; border: 1px solid #FFCCCC; }
        .alert.info    { background: var(--primary-light); color: var(--primary); border: 1px solid rgba(108,99,255,.2); }

        /* ── TOGGLE SWITCH ── */
        .notif-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }
        .notif-row:last-child { border-bottom: none; }

        .notif-info h4 { font-size: 14px; font-weight: 600; color: var(--dark); }
        .notif-info p  { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }

        .toggle {
            position: relative;
            display: inline-block;
            width: 42px;
            height: 24px;
            flex-shrink: 0;
        }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute; inset: 0;
            background: var(--border);
            border-radius: 100px;
            cursor: pointer;
            transition: .3s;
        }
        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 18px; height: 18px;
            left: 3px; top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: .3s;
            box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }
        .toggle input:checked + .toggle-slider { background: var(--primary); }
        .toggle input:checked + .toggle-slider::before { transform: translateX(18px); }

        /* ── API KEYS ── */
        .api-key-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }
        .api-key-row:last-child { border-bottom: none; }

        .api-key-info { flex: 1; }
        .api-key-info h4 { font-size: 14px; font-weight: 600; }
        .api-key-info .key-value {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 4px;
            background: var(--bg);
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            display: flex; align-items: center; gap: 8px;
        }
        .api-key-info .key-masked { letter-spacing: 2px; }

        .copy-btn {
            background: none; border: none; cursor: pointer;
            color: var(--text-secondary); font-size: 13px;
            padding: 2px 4px;
        }
        .copy-btn:hover { color: var(--primary); }

        .key-badge {
            padding: 3px 9px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
        }
        .key-badge.live { background: #EDFAF5; color: #00875A; }
        .key-badge.test { background: #FFF9E6; color: #856400; }

        /* ── DANGER ZONE ── */
        .danger-zone {
            border-color: #FFCCCC;
        }
        .danger-zone .section-card-header {
            background: #FFF8F8;
            border-bottom-color: #FFCCCC;
        }
        .danger-zone .section-card-header h3 { color: var(--danger); }

        /* ── STAT CHIPS ── */
        .stat-chips {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .stat-chip {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 20px;
            flex: 1;
            min-width: 140px;
            box-shadow: var(--shadow-sm);
        }
        .stat-chip-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.5px;
        }
        .stat-chip-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 3px;
        }

        /* ── SESSIONS ── */
        .session-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }
        .session-item:last-child { border-bottom: none; }
        .session-icon {
            width: 38px; height: 38px;
            border-radius: var(--radius);
            background: var(--bg);
            border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            color: var(--text-secondary);
            font-size: 16px;
            flex-shrink: 0;
        }
        .session-info { flex: 1; }
        .session-info h4 { font-size: 13px; font-weight: 600; }
        .session-info p  { font-size: 12px; color: var(--text-secondary); margin-top: 2px; }
        .session-current { font-size: 11px; font-weight: 700; color: var(--success); background: #EDFAF5; padding: 3px 8px; border-radius: 100px; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .page-layout { padding: 20px 16px 48px; }
            .form-grid { grid-template-columns: 1fr; }
            .profile-card { flex-direction: column; text-align: center; }
            .profile-card-actions { margin: 0; }
            .stat-chips { flex-direction: column; }
        }

        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
    </style>
</head>
<body>

<!-- ══ TOPBAR ══ -->
<div class="topbar">
    <a href="crm/index.php" class="topbar-brand">
        <div class="topbar-brand-icon">🚀</div>
        <span class="topbar-brand-name">Webexa</span>
    </a>
    <div class="topbar-sep"></div>
    <span class="topbar-page-title">Mon Compte</span>

    <div class="topbar-right">
        <a href="crm/index.php" class="topbar-btn">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a href="logout.php" class="topbar-btn" style="color:#FF4C4C; border-color:#FFCCCC;">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
        <div class="avatar-sm" style="background:<?= htmlspecialchars($avatar_color) ?>"><?= htmlspecialchars($initials) ?></div>
    </div>
</div>

<!-- ══ PAGE ══ -->
<div class="page-layout">

    <!-- Flash message -->
    <?php if ($flash['msg']): ?>
        <?php $icon = $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'; ?>
        <div class="alert <?= $flash['type'] ?>">
            <i class="fas <?= $icon ?>"></i>
            <span><?= htmlspecialchars($flash['msg']) ?></span>
        </div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="page-header">
        <h1>Paramètres du compte</h1>
        <p>Gérez votre profil, sécurité et préférences</p>
    </div>

    <!-- Profile summary card -->
    <div class="profile-card">
        <div class="avatar-lg" style="background:<?= htmlspecialchars($avatar_color) ?>"><?= htmlspecialchars($initials) ?></div>
        <div class="profile-info">
            <h2><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h2>
            <div class="email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            <div class="role-badge">
                <i class="fas fa-shield-halved"></i>
                <?= htmlspecialchars(ucfirst($user['role'] ?? 'user')) ?>
            </div>
        </div>
        <div class="profile-card-actions">
            <div class="stat-chips" style="margin-bottom:0; flex-direction:row;">
                <div class="stat-chip" style="min-width:110px;">
                    <div class="stat-chip-value"><?= date('d/m/Y', strtotime($user['created_at'] ?? 'now')) ?></div>
                    <div class="stat-chip-label">Membre depuis</div>
                </div>
                <div class="stat-chip" style="min-width:110px;">
                    <div class="stat-chip-value" style="color:var(--success)">Actif</div>
                    <div class="stat-chip-label">Statut du compte</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <nav class="tabs-nav">
        <a href="?tab=profile" class="tab-link <?= $active_tab==='profile' ? 'active' : '' ?>">
            <i class="fas fa-user"></i> Profil
        </a>
        <a href="?tab=security" class="tab-link <?= $active_tab==='security' ? 'active' : '' ?>">
            <i class="fas fa-lock"></i> Sécurité
        </a>
        <a href="?tab=company" class="tab-link <?= $active_tab==='company' ? 'active' : '' ?>">
            <i class="fas fa-building"></i> Entreprise
        </a>
        <a href="?tab=notifications" class="tab-link <?= $active_tab==='notifications' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="?tab=api" class="tab-link <?= $active_tab==='api' ? 'active' : '' ?>">
            <i class="fas fa-key"></i> API
        </a>
    </nav>

    <!-- ══ TAB: PROFILE ══ -->
    <div class="tab-panel <?= $active_tab==='profile' ? 'active' : '' ?>">
        <div class="section-card">
            <div class="section-card-header">
                <div>
                    <h3>Informations personnelles</h3>
                    <p>Modifiez vos informations de profil</p>
                </div>
            </div>
            <div class="section-card-body">
                <form method="POST" action="?tab=profile">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-grid">
                        <div class="field">
                            <label for="first_name">Prénom <span style="color:var(--danger)">*</span></label>
                            <input type="text" id="first_name" name="first_name"
                                   value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="last_name">Nom <span style="color:var(--danger)">*</span></label>
                            <input type="text" id="last_name" name="last_name"
                                   value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label>Adresse e-mail</label>
                            <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                            <div class="hint">L'adresse e-mail ne peut pas être modifiée ici.</div>
                        </div>
                        <div class="field">
                            <label for="phone">Téléphone</label>
                            <input type="tel" id="phone" name="phone"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                   placeholder="+33 6 00 00 00 00">
                        </div>
                        <div class="field field-full">
                            <label for="position">Poste / Titre</label>
                            <input type="text" id="position" name="position"
                                   value="<?= htmlspecialchars($user['position'] ?? '') ?>"
                                   placeholder="ex : Directeur commercial">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══ TAB: SECURITY ══ -->
    <div class="tab-panel <?= $active_tab==='security' ? 'active' : '' ?>">
        <div class="section-card">
            <div class="section-card-header">
                <div>
                    <h3>Changer le mot de passe</h3>
                    <p>Utilisez un mot de passe fort et unique</p>
                </div>
            </div>
            <div class="section-card-body">
                <form method="POST" action="?tab=security">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-grid single">
                        <div class="field">
                            <label for="current_password">Mot de passe actuel</label>
                            <div class="input-with-icon">
                                <input type="password" id="current_password" name="current_password" required
                                       placeholder="••••••••" autocomplete="current-password">
                                <button type="button" class="toggle-pw" onclick="togglePw('current_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="field">
                            <label for="new_password">Nouveau mot de passe</label>
                            <div class="input-with-icon">
                                <input type="password" id="new_password" name="new_password" required
                                       placeholder="••••••••" autocomplete="new-password" oninput="checkPw(this.value)">
                                <button type="button" class="toggle-pw" onclick="togglePw('new_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="pw-strength" style="display:none; margin-top:8px;">
                                <div style="height:4px; border-radius:100px; background:var(--border); overflow:hidden; margin-bottom:4px;">
                                    <div id="pw-fill" style="height:100%; border-radius:100px; width:0; transition:all .3s;"></div>
                                </div>
                                <div id="pw-label" style="font-size:11px;"></div>
                            </div>
                        </div>
                        <div class="field">
                            <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                            <div class="input-with-icon">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                       placeholder="••••••••" autocomplete="new-password">
                                <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-lock"></i> Mettre à jour le mot de passe
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <div>
                    <h3>Sessions actives</h3>
                    <p>Appareils connectés à votre compte</p>
                </div>
                <button class="btn btn-ghost" style="font-size:12px; padding:7px 14px;">
                    <i class="fas fa-sign-out-alt"></i> Tout déconnecter
                </button>
            </div>
            <div class="section-card-body">
                <div class="session-item">
                    <div class="session-icon"><i class="fas fa-desktop"></i></div>
                    <div class="session-info">
                        <h4>Chrome — macOS</h4>
                        <p><?= $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' ?> · Dernière activité : maintenant</p>
                    </div>
                    <span class="session-current">Session actuelle</span>
                </div>
            </div>
        </div>

        <div class="section-card danger-zone">
            <div class="section-card-header">
                <div>
                    <h3><i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i>Zone dangereuse</h3>
                    <p>Ces actions sont irréversibles</p>
                </div>
            </div>
            <div class="section-card-body">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                    <div>
                        <div style="font-size:14px; font-weight:600; color:var(--dark);">Supprimer le compte</div>
                        <div style="font-size:13px; color:var(--text-secondary); margin-top:3px;">
                            Toutes vos données seront définitivement supprimées
                        </div>
                    </div>
                    <button class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash"></i> Supprimer mon compte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ TAB: COMPANY ══ -->
    <div class="tab-panel <?= $active_tab==='company' ? 'active' : '' ?>">
        <div class="section-card">
            <div class="section-card-header">
                <div>
                    <h3>Informations de l'entreprise</h3>
                    <p>Ces informations apparaissent sur vos documents</p>
                </div>
            </div>
            <div class="section-card-body">
                <?php if (empty($user['customer_id'])): ?>
                    <div class="alert info">
                        <i class="fas fa-circle-info"></i>
                        <span>Aucune entreprise associée à votre compte. Contactez un administrateur.</span>
                    </div>
                <?php else: ?>
                <form method="POST" action="?tab=company">
                    <input type="hidden" name="action" value="update_company">
                    <div class="form-grid">
                        <div class="field field-full">
                            <label for="company_name">Nom de l'entreprise <span style="color:var(--danger)">*</span></label>
                            <input type="text" id="company_name" name="company_name"
                                   value="<?= htmlspecialchars($company['name'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="company_phone">Téléphone</label>
                            <input type="tel" id="company_phone" name="company_phone"
                                   value="<?= htmlspecialchars($company['phone'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="website">Site web</label>
                            <input type="url" id="website" name="website"
                                   value="<?= htmlspecialchars($company['website'] ?? '') ?>"
                                   placeholder="https://www.exemple.com">
                        </div>
                        <div class="field">
                            <label for="industry">Secteur d'activité</label>
                            <select id="industry" name="industry">
                                <?php
                                $industries = ['Technology' => 'Technologie', 'Finance' => 'Finance & Banque',
                                    'Healthcare' => 'Santé', 'Retail' => 'Commerce', 'Manufacturing' => 'Industrie',
                                    'Services' => 'Services', 'Real Estate' => 'Immobilier', 'Education' => 'Éducation', 'Other' => 'Autre'];
                                foreach ($industries as $val => $label):
                                    $sel = ($company['industry'] ?? '') === $val ? 'selected' : '';
                                ?>
                                    <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field field-full">
                            <label for="address">Adresse</label>
                            <textarea id="address" name="address" placeholder="Rue, code postal, ville, pays"><?= htmlspecialchars($company['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══ TAB: NOTIFICATIONS ══ -->
    <div class="tab-panel <?= $active_tab==='notifications' ? 'active' : '' ?>">
        <form method="POST" action="?tab=notifications">
            <input type="hidden" name="action" value="update_notifications">

            <div class="section-card">
                <div class="section-card-header">
                    <div>
                        <h3>Notifications par e-mail</h3>
                        <p>Choisissez quand vous souhaitez recevoir des e-mails</p>
                    </div>
                </div>
                <div class="section-card-body">
                    <?php
                    $email_notifs = [
                        ['notif_email_leads',  'Nouveaux leads', 'Recevoir un email à chaque nouveau lead assigné'],
                        ['notif_email_deals',  'Opportunités', 'Mises à jour sur vos opportunités commerciales'],
                        ['notif_email_tasks',  'Tâches', 'Rappels d\'échéance et nouvelles tâches assignées'],
                        ['notif_digest_weekly','Digest hebdomadaire', 'Résumé de votre activité chaque lundi matin'],
                    ];
                    foreach ($email_notifs as [$key, $title, $desc]):
                        $checked = !empty($notif[$key]) ? 'checked' : '';
                    ?>
                    <div class="notif-row">
                        <div class="notif-info">
                            <h4><?= $title ?></h4>
                            <p><?= $desc ?></p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="<?= $key ?>" <?= $checked ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section-card">
                <div class="section-card-header">
                    <div>
                        <h3>Notifications navigateur</h3>
                        <p>Alertes en temps réel dans votre navigateur</p>
                    </div>
                </div>
                <div class="section-card-body">
                    <?php
                    $browser_notifs = [
                        ['notif_browser_leads', 'Nouveaux leads', 'Alerte immédiate à la création d\'un lead'],
                        ['notif_browser_deals', 'Mise à jour pipeline', 'Quand une opportunité change de statut'],
                    ];
                    foreach ($browser_notifs as [$key, $title, $desc]):
                        $checked = !empty($notif[$key]) ? 'checked' : '';
                    ?>
                    <div class="notif-row">
                        <div class="notif-info">
                            <h4><?= $title ?></h4>
                            <p><?= $desc ?></p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" name="<?= $key ?>" <?= $checked ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top:4px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer les préférences
                </button>
            </div>
        </form>
    </div>

    <!-- ══ TAB: API ══ -->
    <div class="tab-panel <?= $active_tab==='api' ? 'active' : '' ?>">
        <div class="section-card">
            <div class="section-card-header">
                <div>
                    <h3>Clés API</h3>
                    <p>Utilisez ces clés pour connecter des outils tiers à Webexa</p>
                </div>
                <button class="btn btn-primary" style="font-size:12px; padding:8px 14px;" onclick="generateKey()">
                    <i class="fas fa-plus"></i> Générer une clé
                </button>
            </div>
            <div class="section-card-body">
                <div class="alert info">
                    <i class="fas fa-circle-info"></i>
                    <span>Ne partagez jamais vos clés API. Elles donnent accès à votre compte Webexa.</span>
                </div>

                <div class="api-key-row">
                    <div class="api-key-info">
                        <h4>Clé de production <span class="key-badge live">Live</span></h4>
                        <div class="key-value">
                            <span class="key-masked" id="key-live">wxk_live_••••••••••••••••••••••••••••••••</span>
                            <button class="copy-btn" onclick="copyKey('live')" title="Copier">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <button class="btn btn-ghost" style="font-size:12px; padding:7px 12px;" onclick="revokeKey('live')">
                        <i class="fas fa-trash"></i> Révoquer
                    </button>
                </div>

                <div class="api-key-row">
                    <div class="api-key-info">
                        <h4>Clé de test <span class="key-badge test">Test</span></h4>
                        <div class="key-value">
                            <span class="key-masked" id="key-test">wxk_test_••••••••••••••••••••••••••••••••</span>
                            <button class="copy-btn" onclick="copyKey('test')" title="Copier">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <button class="btn btn-ghost" style="font-size:12px; padding:7px 12px;" onclick="revokeKey('test')">
                        <i class="fas fa-trash"></i> Révoquer
                    </button>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <div>
                    <h3>Documentation API</h3>
                    <p>Ressources pour intégrer Webexa dans vos applications</p>
                </div>
            </div>
            <div class="section-card-body">
                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    <a href="#" class="btn btn-ghost" style="width:auto;">
                        <i class="fas fa-book"></i> Documentation
                    </a>
                    <a href="#" class="btn btn-ghost" style="width:auto;">
                        <i class="fas fa-code"></i> Exemples de code
                    </a>
                    <a href="#" class="btn btn-ghost" style="width:auto;">
                        <i class="fas fa-plug"></i> Webhooks
                    </a>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.page-layout -->

<script>
    function togglePw(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    function checkPw(val) {
        const wrap  = document.getElementById('pw-strength');
        const fill  = document.getElementById('pw-fill');
        const label = document.getElementById('pw-label');
        if (!val) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'block';
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        const levels = [
            { w:'25%', c:'#FF4C4C', t:'Très faible' },
            { w:'50%', c:'#FFB800', t:'Faible' },
            { w:'75%', c:'#6C63FF', t:'Moyen' },
            { w:'100%',c:'#00C48C', t:'Fort' },
        ];
        const l = levels[score - 1] || levels[0];
        fill.style.width = l.w; fill.style.background = l.c;
        label.textContent = l.t; label.style.color = l.c;
    }

    function confirmDelete() {
        if (confirm('⚠️ Êtes-vous certain de vouloir supprimer votre compte ? Cette action est irréversible.')) {
            alert('Fonctionnalité de suppression à implémenter côté serveur.');
        }
    }

    function copyKey(type) {
        const text = document.getElementById('key-' + type).textContent.trim();
        navigator.clipboard.writeText(text).then(() => {
            alert('Clé copiée dans le presse-papiers !');
        }).catch(() => {
            alert('Impossible de copier. Copiez manuellement : ' + text);
        });
    }

    function revokeKey(type) {
        if (confirm('Révoquer cette clé API ? Les intégrations utilisant cette clé cesseront de fonctionner.')) {
            alert('La révocation de clé sera implémentée via l\'API.');
        }
    }

    function generateKey() {
        alert('La génération de clé sera implémentée via l\'API.');
    }
</script>
</body>
</html>
