<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Vérification de l'authentification
if (!isAuthenticated()) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier si l'onboarding est déjà complété
$stmt = $pdo->prepare("SELECT customer_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$customer_id = $stmt->fetchColumn();

// Si déjà complété, rediriger vers le dashboard
if ($customer_id && !isset($_GET['force'])) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM companies WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    if ($stmt->fetchColumn() > 0) {
        header('Location: index.php');
        exit;
    }
}

// Récupérer la liste des customers existants pour sélection
$stmt = $pdo->query("SELECT id, name, email FROM customers ORDER BY name ASC");
$existing_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .onboarding-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .onboarding-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            animation: fadeInDown 0.6s ease;
        }

        .onboarding-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .onboarding-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 50px;
            position: relative;
            padding: 0 20px;
        }

        .progress-steps::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 20px;
            right: 20px;
            height: 3px;
            background: rgba(255,255,255,0.3);
            z-index: 0;
        }

        .progress-line {
            position: absolute;
            top: 25px;
            left: 20px;
            height: 3px;
            background: white;
            transition: width 0.4s ease;
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }

        .step.active .step-circle {
            background: white;
            color: var(--primary-color);
            transform: scale(1.1);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }

        .step.completed .step-circle {
            background: var(--success-color);
            color: white;
        }

        .step-label {
            color: white;
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.8;
        }

        .step.active .step-label {
            opacity: 1;
            font-weight: 600;
        }

        /* Main Card */
        .onboarding-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeInUp 0.6s ease;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        .step-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .step-description {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 1rem;
        }

        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Option Cards */
        .option-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .option-card {
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .option-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .option-card.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .option-card input[type="radio"],
        .option-card input[type="checkbox"] {
            display: none;
        }

        .option-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .option-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .option-description {
            font-size: 0.9rem;
            color: #6b7280;
        }

        /* Buttons */
        .btn-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            gap: 15px;
        }

        .btn-custom {
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
            min-width: 120px;
        }

        .btn-previous {
            background: #f3f4f6;
            color: #6b7280;
        }

        .btn-previous:hover {
            background: #e5e7eb;
            transform: translateX(-5px);
        }

        .btn-next {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            margin-left: auto;
        }

        .btn-next:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-next:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Module Selection */
        .module-card {
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .module-card:hover {
            border-color: var(--primary-color);
            transform: translateX(5px);
        }

        .module-card.selected {
            border-color: var(--success-color);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(16, 185, 129, 0.1) 100%);
        }

        .module-checkbox {
            width: 24px;
            height: 24px;
            cursor: pointer;
        }

        .module-info {
            flex: 1;
        }

        .module-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }

        .module-description {
            font-size: 0.9rem;
            color: #6b7280;
        }

        /* Summary */
        .summary-section {
            background: #f9fafb;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .summary-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .summary-value {
            color: #1f2937;
            font-weight: 600;
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Loading */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 4px;
        }

        /* Success Message */
        .success-message {
            display: none;
            text-align: center;
            padding: 40px;
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 20px;
            animation: bounceIn 0.6s ease;
        }

        @keyframes bounceIn {
            0%, 20%, 40%, 60%, 80%, 100% {
                animation-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
            }
            0% {
                opacity: 0;
                transform: scale3d(.3, .3, .3);
            }
            20% {
                transform: scale3d(1.1, 1.1, 1.1);
            }
            40% {
                transform: scale3d(.9, .9, .9);
            }
            60% {
                opacity: 1;
                transform: scale3d(1.03, 1.03, 1.03);
            }
            80% {
                transform: scale3d(.97, .97, .97);
            }
            100% {
                opacity: 1;
                transform: scale3d(1, 1, 1);
            }
        }
    </style>
</head>
<body>

<div class="onboarding-container">
    <div class="onboarding-header">
        <h1><i class="fas fa-rocket"></i> Bienvenue sur Webitech</h1>
        <p>Configurons ensemble votre espace de travail en quelques étapes simples</p>
    </div>

    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="progress-line" id="progressLine"></div>
        <div class="step active" data-step="1">
            <div class="step-circle">1</div>
            <div class="step-label">Société</div>
        </div>
        <div class="step" data-step="2">
            <div class="step-circle">2</div>
            <div class="step-label">Modules</div>
        </div>
        <div class="step" data-step="3">
            <div class="step-circle">3</div>
            <div class="step-label">Entreprise</div>
        </div>
        <div class="step" data-step="4">
            <div class="step-circle">4</div>
            <div class="step-label">Résumé</div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="onboarding-card">
        <form id="onboardingForm">
            
            <!-- Step 1: Customer Selection/Creation -->
            <div class="step-content active" data-step="1">
                <h2 class="step-title"><i class="fas fa-building"></i> Votre Société</h2>
                <p class="step-description">Sélectionnez votre société ou créez-en une nouvelle</p>

                <div class="option-cards">
                    <div class="option-card" onclick="selectCustomerOption('existing')">
                        <input type="radio" name="customer_option" value="existing" id="customer_existing">
                        <div class="option-icon"><i class="fas fa-search"></i></div>
                        <div class="option-title">Société Existante</div>
                        <div class="option-description">Je fais partie d'une société déjà enregistrée</div>
                    </div>

                    <div class="option-card" onclick="selectCustomerOption('new')">
                        <input type="radio" name="customer_option" value="new" id="customer_new">
                        <div class="option-icon"><i class="fas fa-plus-circle"></i></div>
                        <div class="option-title">Nouvelle Société</div>
                        <div class="option-description">Je crée une nouvelle société</div>
                    </div>
                </div>

                <!-- Existing Customer Selection -->
                <div id="existingCustomerSection" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label">Sélectionner votre société</label>
                        <select class="form-select" id="existing_customer_id" name="existing_customer_id">
                            <option value="">-- Choisir --</option>
                            <?php foreach ($existing_customers as $cust): ?>
                                <option value="<?= $cust['id'] ?>"><?= htmlspecialchars($cust['name']) ?> (<?= htmlspecialchars($cust['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Email de la société *</label>
                            <input type="email" class="form-control" id="validation_email" name="validation_email" placeholder="contact@entreprise.fr">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Code postal *</label>
                            <input type="text" class="form-control" id="validation_postal" name="validation_postal" placeholder="75001">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Code de validation *</label>
                            <input type="text" class="form-control" id="validation_code" name="validation_code" placeholder="123456">
                        </div>
                    </div>
                    <p class="text-muted" style="font-size: 0.85rem;">
                        <i class="fas fa-info-circle"></i> Contactez l'administrateur de votre société pour obtenir ces informations
                    </p>
                </div>

                <!-- New Customer Creation -->
                <div id="newCustomerSection" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom de la société *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Ma Société">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" id="customer_email" name="customer_email" placeholder="contact@societe.fr">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone" placeholder="+33 1 23 45 67 89">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pays</label>
                            <input type="text" class="form-control" id="customer_country" name="customer_country" value="France">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" id="customer_address" name="customer_address" placeholder="123 Rue de la Paix">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Code postal</label>
                            <input type="text" class="form-control" id="customer_postal" name="customer_postal" placeholder="75001">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Module Selection -->
            <div class="step-content" data-step="2">
                <h2 class="step-title"><i class="fas fa-th-large"></i> Modules à Activer</h2>
                <p class="step-description">Sélectionnez les modules dont vous avez besoin</p>

                <div class="row">
                    <div class="col-md-6">
                        <h5 style="margin-bottom: 20px; color: #667eea;"><i class="fas fa-briefcase"></i> CRM</h5>
                        
                        <label class="module-card">
                            <input type="checkbox" class="module-checkbox" name="modules[]" value="crm_contacts" checked>
                            <div class="module-info">
                                <div class="module-title">Contacts & Leads</div>
                                <div class="module-description">Gestion des contacts, leads et opportunités</div>
                            </div>
                        </label>

                        <label class="module-card">
                            <input type="checkbox" class="module-checkbox" name="modules[]" value="crm_campaigns">
                            <div class="module-info">
                                <div class="module-title">Campagnes Marketing</div>
                                <div class="module-description">Créez et suivez vos campagnes email</div>
                            </div>
                        </label>

                        <label class="module-card">
                            <input type="checkbox" class="module-checkbox" name="modules[]" value="crm_analytics">
                            <div class="module-info">
                                <div class="module-title">Analytics & Rapports</div>
                                <div class="module-description">Tableaux de bord et analyses avancées</div>
                            </div>
                        </label>

                        <label class="module-card">
                            <input type="checkbox" class="module-checkbox" name="modules[]" value="crm_automation">
                            <div class="module-info">
                                <div class="module-title">Automatisation</div>
                                <div class="module-description">Workflows et automatisations</div>
                            </div>
                        </label>
                    </div>

                    <div class="col-md-6">
                        <h5 style="margin-bottom: 20px; color: #10b981;"><i class="fas fa-cogs"></i> ERP</h5>

                        <label class="module-card">
                            <input type="checkbox" class="module-checkbox" name="modules[]" value="erp_accounting">
                            <div class="module-info">
                                <div class="module-title">Comptabilité</div>
                                <div class="module-description">Gestion comptable et fiscale</div>
                            </div>
                        </label>

                        <label class="module-card">
                            <input type="checkbox" class="module-checkbox" name="modules[]" value="erp_invoices">
                            <div class="module-info">
                                <div class="module-title">Facturation</div>
                                <div class="module-description">Devis et factures professionnels</div>
                            </div>
                        </label>

                        <label class="module-card">
                            <input type="checkbox" class="module-checkbox" name="modules[]" value="erp_hr">
                            <div class="module-info">
                                <div class="module-title">Ressources Humaines</div>
                                <div class="module-description">Gestion des employés et paie</div>
                            </div>
                        </label>

                        <label class="module-card">
                            <input type="checkbox" class="module-checkbox" name="modules[]" value="erp_projects">
                            <div class="module-info">
                                <div class="module-title">Gestion de Projets</div>
                                <div class="module-description">Missions et suivi de projets</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Step 3: Company Info -->
            <div class="step-content" data-step="3">
                <h2 class="step-title"><i class="fas fa-building-user"></i> Votre Entreprise</h2>
                <p class="step-description">Informations sur votre entreprise (sera créée automatiquement dans Companies)</p>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom de l'entreprise *</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Mon Entreprise">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">SIRET / N° entreprise</label>
                        <input type="text" class="form-control" id="company_siret" name="company_siret" placeholder="123 456 789 00012">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="company_email" name="company_email" placeholder="contact@entreprise.fr">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" id="company_phone" name="company_phone" placeholder="+33 1 23 45 67 89">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Site web</label>
                        <input type="url" class="form-control" id="company_website" name="company_website" placeholder="https://entreprise.fr">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Adresse</label>
                    <input type="text" class="form-control" id="company_address" name="company_address" placeholder="123 Rue de la Paix">
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Ville</label>
                        <input type="text" class="form-control" id="company_city" name="company_city" placeholder="Paris">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Code postal</label>
                        <input type="text" class="form-control" id="company_postal_code" name="company_postal_code" placeholder="75001">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pays</label>
                        <input type="text" class="form-control" id="company_country" name="company_country" value="France">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Secteur d'activité</label>
                    <select class="form-select" id="company_industry" name="company_industry">
                        <option value="">-- Sélectionner --</option>
                        <option value="technology">Technologie / IT</option>
                        <option value="finance">Finance / Banque</option>
                        <option value="retail">Commerce / Retail</option>
                        <option value="health">Santé / Médical</option>
                        <option value="education">Éducation / Formation</option>
                        <option value="manufacturing">Industrie / Manufacturing</option>
                        <option value="services">Services</option>
                        <option value="real_estate">Immobilier</option>
                        <option value="other">Autre</option>
                    </select>
                </div>
            </div>

            <!-- Step 4: Summary -->
            <div class="step-content" data-step="4">
                <h2 class="step-title"><i class="fas fa-check-circle"></i> Récapitulatif</h2>
                <p class="step-description">Vérifiez les informations avant de finaliser</p>

                <div class="summary-section">
                    <div class="summary-title"><i class="fas fa-building"></i> Société</div>
                    <div id="summarySociety"></div>
                </div>

                <div class="summary-section">
                    <div class="summary-title"><i class="fas fa-th-large"></i> Modules Activés</div>
                    <div id="summaryModules"></div>
                </div>

                <div class="summary-section">
                    <div class="summary-title"><i class="fas fa-building-user"></i> Entreprise</div>
                    <div id="summaryCompany"></div>
                </div>

                <div style="background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); color: white; padding: 20px; border-radius: 15px; margin-top: 20px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-info-circle" style="font-size: 2rem;"></i>
                        <div>
                            <strong style="font-size: 1.1rem;">Information importante</strong>
                            <p style="margin: 5px 0 0 0; opacity: 0.9;">Les informations de votre société seront automatiquement copiées dans votre entreprise. Vous pourrez les modifier à tout moment dans les paramètres.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div class="loading-spinner" id="loadingSpinner">
                <div class="spinner-border text-primary"></div>
                <p style="margin-top: 15px; color: #6b7280;">Configuration en cours...</p>
            </div>

            <!-- Success -->
            <div class="success-message" id="successMessage">
                <div class="success-icon"><i class="fas fa-check-circle"></i></div>
                <h3 style="color: #1f2937; margin-bottom: 10px;">Configuration réussie !</h3>
                <p style="color: #6b7280;">Redirection vers votre dashboard...</p>
            </div>

            <!-- Navigation Buttons -->
            <div class="btn-navigation" id="navigationButtons">
                <button type="button" class="btn btn-custom btn-previous" id="btnPrevious" onclick="previousStep()" style="display: none;">
                    <i class="fas fa-arrow-left"></i> Précédent
                </button>
                <button type="button" class="btn btn-custom btn-next" id="btnNext" onclick="nextStep()">
                    Suivant <i class="fas fa-arrow-right"></i>
                </button>
            </div>

        </form>
    </div>
</div>

<script>
    let currentStep = 1;
    const totalSteps = 4;

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        updateProgressBar();
        setupModuleCards();
    });

    // Customer Option Selection
    function selectCustomerOption(option) {
        document.querySelectorAll('.option-cards .option-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        if (option === 'existing') {
            document.getElementById('customer_existing').checked = true;
            document.querySelector('[onclick="selectCustomerOption(\'existing\')"]').classList.add('selected');
            document.getElementById('existingCustomerSection').style.display = 'block';
            document.getElementById('newCustomerSection').style.display = 'none';
        } else {
            document.getElementById('customer_new').checked = true;
            document.querySelector('[onclick="selectCustomerOption(\'new\')"]').classList.add('selected');
            document.getElementById('existingCustomerSection').style.display = 'none';
            document.getElementById('newCustomerSection').style.display = 'block';
        }
    }

    // Module Cards Setup
    function setupModuleCards() {
        const moduleCards = document.querySelectorAll('.module-card');
        moduleCards.forEach(card => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            
            if (checkbox.checked) {
                card.classList.add('selected');
            }
            
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });
        });
    }

    // Navigation
    function nextStep() {
        if (!validateStep(currentStep)) {
            return;
        }

        if (currentStep === totalSteps) {
            submitForm();
            return;
        }

        currentStep++;
        showStep(currentStep);
    }

    function previousStep() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    }

    function showStep(step) {
        // Hide all steps
        document.querySelectorAll('.step-content').forEach(content => {
            content.classList.remove('active');
        });

        // Show current step
        document.querySelector(`.step-content[data-step="${step}"]`).classList.add('active');

        // Update progress
        document.querySelectorAll('.step').forEach(stepEl => {
            const stepNum = parseInt(stepEl.dataset.step);
            stepEl.classList.remove('active', 'completed');
            
            if (stepNum === step) {
                stepEl.classList.add('active');
            } else if (stepNum < step) {
                stepEl.classList.add('completed');
            }
        });

        updateProgressBar();
        updateButtons();

        // Update summary if on last step
        if (step === 4) {
            updateSummary();
        }
    }

    function updateProgressBar() {
        const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
        const progressLine = document.getElementById('progressLine');
        const stepsContainer = document.querySelector('.progress-steps');
        const firstStep = stepsContainer.querySelector('.step:first-child');
        const lastStep = stepsContainer.querySelector('.step:last-child');
        
        const firstStepX = firstStep.offsetLeft + (firstStep.offsetWidth / 2);
        const lastStepX = lastStep.offsetLeft + (lastStep.offsetWidth / 2);
        const totalWidth = lastStepX - firstStepX;
        
        progressLine.style.width = (totalWidth * progress / 100) + 'px';
    }

    function updateButtons() {
        const btnPrevious = document.getElementById('btnPrevious');
        const btnNext = document.getElementById('btnNext');

        btnPrevious.style.display = currentStep > 1 ? 'block' : 'none';
        
        if (currentStep === totalSteps) {
            btnNext.innerHTML = '<i class="fas fa-check"></i> Finaliser';
        } else {
            btnNext.innerHTML = 'Suivant <i class="fas fa-arrow-right"></i>';
        }
    }

    function validateStep(step) {
        if (step === 1) {
            const customerOption = document.querySelector('input[name="customer_option"]:checked');
            if (!customerOption) {
                alert('Veuillez sélectionner une option');
                return false;
            }

            if (customerOption.value === 'existing') {
                const customerId = document.getElementById('existing_customer_id').value;
                const email = document.getElementById('validation_email').value;
                const postal = document.getElementById('validation_postal').value;
                const code = document.getElementById('validation_code').value;

                if (!customerId || !email || !postal || !code) {
                    alert('Veuillez remplir tous les champs de validation');
                    return false;
                }
            } else {
                const name = document.getElementById('customer_name').value;
                const email = document.getElementById('customer_email').value;

                if (!name || !email) {
                    alert('Veuillez remplir au moins le nom et l\'email de la société');
                    return false;
                }
            }
        }

        if (step === 3) {
            const companyName = document.getElementById('company_name').value;
            if (!companyName) {
                alert('Veuillez saisir le nom de votre entreprise');
                return false;
            }
        }

        return true;
    }

    function updateSummary() {
        // Society Summary
        const customerOption = document.querySelector('input[name="customer_option"]:checked').value;
        let societyHTML = '';
        
        if (customerOption === 'existing') {
            const select = document.getElementById('existing_customer_id');
            const selectedOption = select.options[select.selectedIndex];
            societyHTML = `
                <div class="summary-item">
                    <span class="summary-label">Type</span>
                    <span class="summary-value">Société Existante</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Société</span>
                    <span class="summary-value">${selectedOption.text}</span>
                </div>
            `;
        } else {
            const name = document.getElementById('customer_name').value;
            const email = document.getElementById('customer_email').value;
            societyHTML = `
                <div class="summary-item">
                    <span class="summary-label">Type</span>
                    <span class="summary-value">Nouvelle Société</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Nom</span>
                    <span class="summary-value">${name}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Email</span>
                    <span class="summary-value">${email}</span>
                </div>
            `;
        }
        document.getElementById('summarySociety').innerHTML = societyHTML;

        // Modules Summary
        const checkedModules = document.querySelectorAll('input[name="modules[]"]:checked');
        const moduleNames = {
            'crm_contacts': 'Contacts & Leads',
            'crm_campaigns': 'Campagnes Marketing',
            'crm_analytics': 'Analytics & Rapports',
            'crm_automation': 'Automatisation',
            'erp_accounting': 'Comptabilité',
            'erp_invoices': 'Facturation',
            'erp_hr': 'Ressources Humaines',
            'erp_projects': 'Gestion de Projets'
        };
        
        let modulesHTML = '';
        checkedModules.forEach(module => {
            modulesHTML += `<div class="summary-item">
                <span class="summary-value"><i class="fas fa-check text-success"></i> ${moduleNames[module.value]}</span>
            </div>`;
        });
        document.getElementById('summaryModules').innerHTML = modulesHTML || '<p class="text-muted">Aucun module sélectionné</p>';

        // Company Summary
        const companyName = document.getElementById('company_name').value;
        const companyEmail = document.getElementById('company_email').value;
        const companyPhone = document.getElementById('company_phone').value;
        
        let companyHTML = `
            <div class="summary-item">
                <span class="summary-label">Nom</span>
                <span class="summary-value">${companyName || '-'}</span>
            </div>
        `;
        if (companyEmail) {
            companyHTML += `
                <div class="summary-item">
                    <span class="summary-label">Email</span>
                    <span class="summary-value">${companyEmail}</span>
                </div>
            `;
        }
        if (companyPhone) {
            companyHTML += `
                <div class="summary-item">
                    <span class="summary-label">Téléphone</span>
                    <span class="summary-value">${companyPhone}</span>
                </div>
            `;
        }
        document.getElementById('summaryCompany').innerHTML = companyHTML;
    }

    function submitForm() {
        // Show loading
        document.getElementById('navigationButtons').style.display = 'none';
        document.querySelector('.step-content.active').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'block';

        // Gather all form data
        const formData = new FormData(document.getElementById('onboardingForm'));

        // Send to backend
        fetch('api/onboarding-process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                document.getElementById('loadingSpinner').style.display = 'none';
                document.getElementById('successMessage').style.display = 'block';

                // Redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 2000);
            } else {
                alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
                document.getElementById('loadingSpinner').style.display = 'none';
                document.getElementById('navigationButtons').style.display = 'flex';
                document.querySelector('.step-content.active').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur de connexion au serveur');
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('navigationButtons').style.display = 'flex';
            document.querySelector('.step-content.active').style.display = 'block';
        });
    }
</script>

</body>
</html>
