<?php
// voir toutes les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Vérification de l'authentification
if (!isAuthenticated()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérifier si l'onboarding est déjà complété
$stmt = $pdo->prepare("SELECT customer_id, onboarding_completed FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['onboarding_completed']) {
    header('Location: index.php');
    exit;
}

// Récupérer les données existantes si disponibles
$customer = null;
$company = null;

if ($user['customer_id']) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$user['customer_id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE customer_id = ? LIMIT 1");
        $stmt->execute([$user['customer_id']]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration - Webitech CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5568d3;
            --secondary: #764ba2;
            --success: #10b981;
            --bg-light: #f8f9fa;
            --border-color: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .wizard-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 900px;
            overflow: hidden;
        }

        .wizard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            color: white;
            text-align: center;
        }

        .wizard-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .wizard-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .progress-container {
            background: white;
            padding: 30px 40px;
            border-bottom: 1px solid var(--border-color);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 15px;
        }

        .progress-line {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--border-color);
            z-index: 0;
        }

        .progress-line-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            transition: width 0.4s ease;
            border-radius: 3px;
        }

        .step {
            position: relative;
            z-index: 1;
            text-align: center;
            flex: 1;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--border-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .step.active .step-circle {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .step.completed .step-circle {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }

        .step-label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
        }

        .step.active .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        .wizard-content {
            padding: 50px;
            min-height: 450px;
        }

        .step-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .step-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .step-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .step-description {
            color: #6b7280;
            margin-bottom: 35px;
            font-size: 1.05rem;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }

        .form-label .required {
            color: #ef4444;
            margin-left: 3px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .module-selection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .module-card {
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .module-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
        }

        .module-card.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .module-card .checkmark {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .module-card.selected .checkmark {
            background: var(--primary);
            border-color: var(--primary);
        }

        .module-card.selected .checkmark i {
            color: white;
            font-size: 0.75rem;
        }

        .module-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .module-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .module-name {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: #1f2937;
        }

        .module-desc {
            color: #6b7280;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .wizard-actions {
            padding: 25px 50px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f9fafb;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #6b7280;
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .welcome-content {
            text-align: center;
            padding: 40px 0;
        }

        .welcome-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }

        .welcome-icon i {
            font-size: 4rem;
            color: white;
        }

        .welcome-list {
            text-align: left;
            max-width: 500px;
            margin: 30px auto;
        }

        .welcome-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .welcome-item-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(102, 126, 234, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .welcome-item-text h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 4px;
            color: #1f2937;
        }

        .welcome-item-text p {
            font-size: 0.9rem;
            color: #6b7280;
            margin: 0;
        }

        .completion-content {
            text-align: center;
            padding: 40px 0;
        }

        .completion-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease;
        }

        .completion-icon i {
            font-size: 4rem;
            color: white;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin: 30px 0;
            text-align: left;
        }

        .summary-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
        }

        .summary-card h4 {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .summary-card .value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .summary-card .sub-value {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #1e40af;
        }

        .alert-icon {
            flex-shrink: 0;
            margin-top: 2px;
        }

        @media (max-width: 768px) {
            .wizard-content {
                padding: 30px;
            }

            .wizard-actions {
                padding: 20px 30px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .progress-container {
                padding: 20px;
            }

            .step-label {
                font-size: 0.75rem;
            }
        }

        .skip-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .skip-link:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="wizard-container">
        <div class="wizard-header">
            <h1><i class="fas fa-rocket"></i> Bienvenue sur Webitech</h1>
            <p>Configurons votre espace en quelques étapes simples</p>
        </div>

        <div class="progress-container">
            <div class="progress-steps">
                <div class="progress-line">
                    <div class="progress-line-fill" id="progressFill" style="width: 0%"></div>
                </div>
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Bienvenue</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Votre Profil</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Entreprise</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label">Modules</div>
                </div>
                <div class="step" data-step="5">
                    <div class="step-circle">5</div>
                    <div class="step-label">Finalisation</div>
                </div>
            </div>
        </div>

        <div class="wizard-content">
            <!-- Étape 1: Bienvenue -->
            <div class="step-content active" data-step="1">
                <div class="welcome-content">
                    <div class="welcome-icon">
                        <i class="fas fa-hand-sparkles"></i>
                    </div>
                    <h2 class="step-title">Bienvenue <?= htmlspecialchars($userInfo['first_name'] ?? '') ?> !</h2>
                    <p class="step-description">Prenez quelques minutes pour configurer votre espace de travail</p>
                    
                    <div class="welcome-list">
                        <div class="welcome-item">
                            <div class="welcome-item-icon">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="welcome-item-text">
                                <h4>Configuration du profil</h4>
                                <p>Vos informations personnelles et professionnelles</p>
                            </div>
                        </div>
                        <div class="welcome-item">
                            <div class="welcome-item-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="welcome-item-text">
                                <h4>Paramètres d'entreprise</h4>
                                <p>Les détails de votre organisation</p>
                            </div>
                        </div>
                        <div class="welcome-item">
                            <div class="welcome-item-icon">
                                <i class="fas fa-puzzle-piece"></i>
                            </div>
                            <div class="welcome-item-text">
                                <h4>Sélection des modules</h4>
                                <p>CRM, ERP, et autres outils disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Étape 2: Profil Client -->
            <div class="step-content" data-step="2">
                <h2 class="step-title">Votre Profil</h2>
                <p class="step-description">Complétez vos informations personnelles</p>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle alert-icon"></i>
                    <div>Ces informations seront utilisées pour personnaliser votre expérience et faciliter la communication.</div>
                </div>

                <form id="profileForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Prénom <span class="required">*</span></label>
                            <input type="text" class="form-control" name="first_name" value="<?= htmlspecialchars($userInfo['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nom <span class="required">*</span></label>
                            <input type="text" class="form-control" name="last_name" value="<?= htmlspecialchars($userInfo['last_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($customer['email'] ?? $userInfo['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" placeholder="+33 6 12 34 56 78">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Poste/Fonction</label>
                            <input type="text" class="form-control" name="position" value="<?= htmlspecialchars($customer['position'] ?? '') ?>" placeholder="Ex: Directeur Commercial">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Adresse</label>
                        <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($customer['address'] ?? '') ?>" placeholder="Adresse complète">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Ville</label>
                            <input type="text" class="form-control" name="city" value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Code Postal</label>
                            <input type="text" class="form-control" name="postal_code" value="<?= htmlspecialchars($customer['postal_code'] ?? '') ?>">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Étape 3: Entreprise -->
            <div class="step-content" data-step="3">
                <h2 class="step-title">Votre Entreprise</h2>
                <p class="step-description">Informations sur votre organisation</p>

                <div class="alert alert-info">
                    <i class="fas fa-magic alert-icon"></i>
                    <div>Ces informations seront automatiquement synchronisées avec votre profil d'entreprise dans le système.</div>
                </div>

                <form id="companyForm">
                    <div class="form-group">
                        <label class="form-label">Nom de l'entreprise <span class="required">*</span></label>
                        <input type="text" class="form-control" name="company_name" value="<?= htmlspecialchars($company['name'] ?? $customer['name'] ?? '') ?>" required placeholder="Ex: ACME Corporation">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">SIRET/SIREN</label>
                            <input type="text" class="form-control" name="siret" value="<?= htmlspecialchars($company['siret'] ?? '') ?>" placeholder="123 456 789 00012">
                        </div>
                        <div class="form-group">
                            <label class="form-label">TVA Intracommunautaire</label>
                            <input type="text" class="form-control" name="vat_number" value="<?= htmlspecialchars($company['vat_number'] ?? '') ?>" placeholder="FR12345678901">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Site Web</label>
                        <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($company['website'] ?? '') ?>" placeholder="https://www.exemple.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Secteur d'activité</label>
                        <select class="form-control" name="industry">
                            <option value="">Sélectionnez un secteur</option>
                            <option value="technology" <?= ($company['industry'] ?? '') === 'technology' ? 'selected' : '' ?>>Technologies</option>
                            <option value="finance" <?= ($company['industry'] ?? '') === 'finance' ? 'selected' : '' ?>>Finance</option>
                            <option value="healthcare" <?= ($company['industry'] ?? '') === 'healthcare' ? 'selected' : '' ?>>Santé</option>
                            <option value="retail" <?= ($company['industry'] ?? '') === 'retail' ? 'selected' : '' ?>>Commerce</option>
                            <option value="manufacturing" <?= ($company['industry'] ?? '') === 'manufacturing' ? 'selected' : '' ?>>Industrie</option>
                            <option value="services" <?= ($company['industry'] ?? '') === 'services' ? 'selected' : '' ?>>Services</option>
                            <option value="education" <?= ($company['industry'] ?? '') === 'education' ? 'selected' : '' ?>>Éducation</option>
                            <option value="other" <?= ($company['industry'] ?? '') === 'other' ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nombre d'employés</label>
                            <select class="form-control" name="employee_count">
                                <option value="">Sélectionnez</option>
                                <option value="1-10" <?= ($company['employee_count'] ?? '') === '1-10' ? 'selected' : '' ?>>1-10</option>
                                <option value="11-50" <?= ($company['employee_count'] ?? '') === '11-50' ? 'selected' : '' ?>>11-50</option>
                                <option value="51-200" <?= ($company['employee_count'] ?? '') === '51-200' ? 'selected' : '' ?>>51-200</option>
                                <option value="201-500" <?= ($company['employee_count'] ?? '') === '201-500' ? 'selected' : '' ?>>201-500</option>
                                <option value="501+" <?= ($company['employee_count'] ?? '') === '501+' ? 'selected' : '' ?>>500+</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Chiffre d'affaires annuel</label>
                            <select class="form-control" name="annual_revenue">
                                <option value="">Sélectionnez</option>
                                <option value="0-100k">0 - 100K €</option>
                                <option value="100k-500k">100K - 500K €</option>
                                <option value="500k-1m">500K - 1M €</option>
                                <option value="1m-5m">1M - 5M €</option>
                                <option value="5m+">5M+ €</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Étape 4: Modules -->
            <div class="step-content" data-step="4">
                <h2 class="step-title">Sélectionnez vos Modules</h2>
                <p class="step-description">Choisissez les outils dont vous avez besoin</p>

                <div class="module-selection">
                    <div class="module-card selected" data-module="crm">
                        <div class="checkmark"><i class="fas fa-check"></i></div>
                        <div class="module-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="module-name">CRM</div>
                        <div class="module-desc">Gestion de la relation client, leads, opportunités et pipeline de vente</div>
                    </div>

                    <div class="module-card" data-module="erp">
                        <div class="checkmark"></div>
                        <div class="module-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="module-name">ERP</div>
                        <div class="module-desc">Gestion des ressources, comptabilité, factures et missions</div>
                    </div>

                    <div class="module-card" data-module="projects">
                        <div class="checkmark"></div>
                        <div class="module-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="module-name">Projets</div>
                        <div class="module-desc">Gestion de projets, tâches et collaboration d'équipe</div>
                    </div>

                    <div class="module-card" data-module="marketing">
                        <div class="checkmark"></div>
                        <div class="module-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="module-name">Marketing</div>
                        <div class="module-desc">Campagnes email, automation et analytics marketing</div>
                    </div>

                    <div class="module-card" data-module="support">
                        <div class="checkmark"></div>
                        <div class="module-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="module-name">Support</div>
                        <div class="module-desc">Service client, tickets et base de connaissances</div>
                    </div>

                    <div class="module-card" data-module="analytics">
                        <div class="checkmark"></div>
                        <div class="module-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="module-name">Analytics</div>
                        <div class="module-desc">Tableaux de bord et rapports avancés avec IA</div>
                    </div>
                </div>
            </div>

            <!-- Étape 5: Récapitulatif -->
            <div class="step-content" data-step="5">
                <div class="completion-content">
                    <div class="completion-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="step-title">Prêt à commencer !</h2>
                    <p class="step-description">Voici un récapitulatif de votre configuration</p>

                    <div class="summary-grid">
                        <div class="summary-card">
                            <h4>Votre Profil</h4>
                            <div class="value" id="summaryName">-</div>
                            <div class="sub-value" id="summaryEmail">-</div>
                            <div class="sub-value" id="summaryPhone">-</div>
                        </div>

                        <div class="summary-card">
                            <h4>Entreprise</h4>
                            <div class="value" id="summaryCompany">-</div>
                            <div class="sub-value" id="summaryIndustry">-</div>
                            <div class="sub-value" id="summaryEmployees">-</div>
                        </div>

                        <div class="summary-card" style="grid-column: 1 / -1;">
                            <h4>Modules Activés</h4>
                            <div id="summaryModules" class="value">-</div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb alert-icon"></i>
                        <div>Vous pourrez modifier ces paramètres à tout moment dans la section Paramètres.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wizard-actions">
            <div>
                <button type="button" class="btn btn-secondary" id="btnPrevious" style="display: none;">
                    <i class="fas fa-arrow-left"></i> Précédent
                </button>
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="#" class="skip-link" id="skipLink">Passer pour l'instant</a>
                <button type="button" class="btn btn-primary" id="btnNext">
                    Suivant <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 5;
        let wizardData = {
            profile: {},
            company: {},
            modules: ['crm']
        };

        // Navigation
        function updateUI() {
            // Update steps
            document.querySelectorAll('.step').forEach(step => {
                const stepNum = parseInt(step.dataset.step);
                step.classList.remove('active', 'completed');
                
                if (stepNum < currentStep) {
                    step.classList.add('completed');
                } else if (stepNum === currentStep) {
                    step.classList.add('active');
                }
            });

            // Update progress bar
            const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progressFill').style.width = progress + '%';

            // Update content
            document.querySelectorAll('.step-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelector(`[data-step="${currentStep}"].step-content`).classList.add('active');

            // Update buttons
            document.getElementById('btnPrevious').style.display = currentStep > 1 ? 'inline-flex' : 'none';
            
            const btnNext = document.getElementById('btnNext');
            if (currentStep === totalSteps) {
                btnNext.innerHTML = '<i class="fas fa-check"></i> Terminer';
            } else {
                btnNext.innerHTML = 'Suivant <i class="fas fa-arrow-right"></i>';
            }

            // Skip link
            const skipLink = document.getElementById('skipLink');
            skipLink.style.display = currentStep === totalSteps ? 'none' : 'inline';

            // Update summary on last step
            if (currentStep === totalSteps) {
                updateSummary();
            }
        }

        function nextStep() {
            if (currentStep < totalSteps) {
                // Validate current step
                if (!validateStep(currentStep)) {
                    return;
                }
                
                // Save data
                saveStepData(currentStep);
                
                currentStep++;
                updateUI();
            } else {
                // Final submission
                submitWizard();
            }
        }

        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                updateUI();
            }
        }

        function validateStep(step) {
            if (step === 2) {
                const form = document.getElementById('profileForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return false;
                }
            } else if (step === 3) {
                const form = document.getElementById('companyForm');
                const companyName = form.querySelector('[name="company_name"]').value.trim();
                if (!companyName) {
                    alert('Le nom de l\'entreprise est requis');
                    return false;
                }
            }
            return true;
        }

        function saveStepData(step) {
            if (step === 2) {
                const form = document.getElementById('profileForm');
                const formData = new FormData(form);
                wizardData.profile = Object.fromEntries(formData.entries());
            } else if (step === 3) {
                const form = document.getElementById('companyForm');
                const formData = new FormData(form);
                wizardData.company = Object.fromEntries(formData.entries());
            }
        }

        function updateSummary() {
            // Profile
            document.getElementById('summaryName').textContent = 
                `${wizardData.profile.first_name || ''} ${wizardData.profile.last_name || ''}`.trim() || '-';
            document.getElementById('summaryEmail').textContent = wizardData.profile.email || '-';
            document.getElementById('summaryPhone').textContent = wizardData.profile.phone || '-';

            // Company
            document.getElementById('summaryCompany').textContent = wizardData.company.company_name || '-';
            
            const industries = {
                'technology': 'Technologies',
                'finance': 'Finance',
                'healthcare': 'Santé',
                'retail': 'Commerce',
                'manufacturing': 'Industrie',
                'services': 'Services',
                'education': 'Éducation',
                'other': 'Autre'
            };
            document.getElementById('summaryIndustry').textContent = 
                industries[wizardData.company.industry] || '-';
            document.getElementById('summaryEmployees').textContent = 
                wizardData.company.employee_count ? `${wizardData.company.employee_count} employés` : '-';

            // Modules
            const moduleNames = {
                'crm': 'CRM',
                'erp': 'ERP',
                'projects': 'Projets',
                'marketing': 'Marketing',
                'support': 'Support',
                'analytics': 'Analytics'
            };
            const moduleList = wizardData.modules.map(m => moduleNames[m]).join(', ');
            document.getElementById('summaryModules').textContent = moduleList || '-';
        }

        async function submitWizard() {
            const btnNext = document.getElementById('btnNext');
            btnNext.disabled = true;
            btnNext.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Configuration...';

            try {
                const response = await fetch('api/setup-wizard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(wizardData)
                });

                const result = await response.json();

                if (result.success) {
                    window.location.href = 'index.php';
                } else {
                    alert('Erreur: ' + (result.message || 'Erreur lors de la configuration'));
                    btnNext.disabled = false;
                    btnNext.innerHTML = '<i class="fas fa-check"></i> Terminer';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Erreur lors de la configuration');
                btnNext.disabled = false;
                btnNext.innerHTML = '<i class="fas fa-check"></i> Terminer';
            }
        }

        // Event listeners
        document.getElementById('btnNext').addEventListener('click', nextStep);
        document.getElementById('btnPrevious').addEventListener('click', previousStep);

        document.getElementById('skipLink').addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('Êtes-vous sûr de vouloir passer cette étape ? Vous pourrez compléter ces informations plus tard dans les paramètres.')) {
                currentStep++;
                updateUI();
            }
        });

        // Module selection
        document.querySelectorAll('.module-card').forEach(card => {
            card.addEventListener('click', function() {
                const module = this.dataset.module;
                
                if (module === 'crm') {
                    // CRM is always required
                    return;
                }
                
                this.classList.toggle('selected');
                
                if (this.classList.contains('selected')) {
                    if (!wizardData.modules.includes(module)) {
                        wizardData.modules.push(module);
                    }
                } else {
                    wizardData.modules = wizardData.modules.filter(m => m !== module);
                }
            });
        });

        // Initialize
        updateUI();
    </script>
</body>
</html>
