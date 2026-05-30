<?php
session_start();
$page_title = "Documentation Administrateurs - ERP Webitech";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa !important;
        }
        .main-content {
            background-color: #ffffff !important;
        }
        .doc-sidebar {
            position: sticky;
            top: 20px;
            height: calc(100vh - 40px);
            overflow-y: auto;
        }
        .sidebar-search {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
            background-color: white;
        }
        .sidebar-search input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 5px;
            font-size: 14px;
        }
        .sidebar-search input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .doc-section {
            scroll-margin-top: 20px;
            margin-bottom: 60px;
        }
        .doc-section h2 {
            color: #2c3e50 !important;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .doc-section h3 {
            color: #2c3e50 !important;
            margin-top: 30px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .step-card {
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
        }
        .step-number {
            background: #667eea;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        .schema-box {
            border: 2px dashed #95a5a6;
            padding: 30px;
            text-align: center;
            background: #ecf0f1;
            margin: 30px 0;
            border-radius: 10px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .success-box {
            background: #d1ecf1;
            border-left: 4px solid #0dcaf0;
            padding: 15px;
            margin: 20px 0;
        }
        .table-features {
            background: white;
        }
        .table-features th {
            background: #667eea;
            color: white;
        }
        ul li{
            color: #2c3e50 !important;
        }
        h4{
            color: #2c3e50 !important;
        }
        h5{
            color: #2c3e50 !important;
        }
        p{
            color: #2c3e50 !important;
        }
        .schema-box{
            color: #2c3e50;
        }
        .schema-box p,
        .schema-box small,
        .schema-box strong{
            color: #2c3e50;
        }
        .success-box,
        .success-box p,
        .success-box ul,
        .success-box li{
            color: #0c5460;
        }
        .warning-box,
        .warning-box p,
        .warning-box ul,
        .warning-box li{
            color: #856404;
        }
        .step-card ul li,
        .step-card ol li,
        .step-card p{
            color: #2c3e50;
        }
        .accordion-body,
        .accordion-body p,
        .accordion-body li{
            color: #2c3e50;
        }
        .card-body ul li,
        .card-body p{
            color: #2c3e50;
        }
        .alert{
            color: #2c3e50;
        }
        .alert-info{
            color: #0c5460;
        }
        .alert-success{
            color: #0f5132;
        }
        strong{
            color: #2c3e50;
        }
        .table-striped tbody tr{
            color: #2c3e50;
        }
        .table-striped tbody tr td{
            color: #2c3e50;
        }
        .table-striped thead th{
            color: #2c3e50;
            background-color: #e9ecef;
        }
        code{
            color: #e83e8c;
            background-color: #f8f9fa;
        }
        .step-card h5,
        .step-card h4{
            color: #2c3e50;
        }
        td{
            color: #2c3e50 !important;
        }
        th{
            color: #2c3e50;
        }
        ol{
            color: #2c3e50 !important;
        }
        ol li{
            color: #2c3e50 !important;
        }
        small{
            color: inherit;
        }
        .doc-section a:not(.btn),
        .card-body a:not(.btn),
        .step-card a:not(.btn),
        .feature-box a:not(.btn),
        .alert a:not(.btn),
        .container-fluid .col-md-9 a:not(.btn){
            color: #667eea !important;
        }
        .doc-section a:not(.btn):hover,
        .card-body a:not(.btn):hover,
        .step-card a:not(.btn):hover,
        .feature-box a:not(.btn):hover,
        .alert a:not(.btn):hover,
        .container-fluid .col-md-9 a:not(.btn):hover{
            color: #764ba2 !important;
        }
        .container-fluid{
            background-color: #ffffff !important;
        }
        
        /* Responsive Styles */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                padding: 10px !important;
            }
            .doc-sidebar {
                position: static !important;
                height: auto !important;
                max-height: none !important;
                margin-bottom: 20px;
            }
            .doc-section {
                margin-bottom: 40px;
            }
            .container-fluid {
                padding: 10px !important;
            }
            .schema-box {
                padding: 15px;
            }
        }
        
        @media (max-width: 767.98px) {
            .doc-section h2 {
                font-size: 1.5rem;
            }
            .doc-section h3 {
                font-size: 1.25rem;
            }
            .step-number {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            .step-card {
                padding: 15px;
            }
            .warning-box,
            .success-box {
                padding: 10px;
            }
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                white-space: nowrap;
            }
            .table thead,
            .table tbody,
            .table tr,
            .table th,
            .table td {
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <?php include 'erp_nav.php'; ?>
    
    <div class="main-content" style="margin-left: 280px; padding: 20px;">
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar Documentation -->
                <div class="col-md-3 col-12 mb-4 mb-md-0">
                    <div class="doc-sidebar">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-book"></i> Sommaire
                                </h5>
                            </div>
                            <div class="sidebar-search">
                                <input type="text" id="sidebarSearchInput" placeholder="Rechercher une section..." aria-label="Rechercher">
                            </div>
                            <div class="list-group list-group-flush">
                                <a href="#introduction" class="list-group-item list-group-item-action">
                                    <i class="fas fa-home"></i> Introduction
                                </a>
                                <a href="#personnel" class="list-group-item list-group-item-action">
                                    <i class="fas fa-users"></i> Gestion Personnel
                                </a>
                                <a href="#planning" class="list-group-item list-group-item-action">
                                    <i class="fas fa-calendar-alt"></i> Planning Avancé
                                </a>
                                <a href="#paie" class="list-group-item list-group-item-action">
                                    <i class="fas fa-file-invoice-dollar"></i> Paie & Charges
                                </a>
                                <a href="#comptabilite" class="list-group-item list-group-item-action">
                                    <i class="fas fa-university"></i> Comptabilité
                                </a>
                                <a href="#rapports" class="list-group-item list-group-item-action">
                                    <i class="fas fa-chart-pie"></i> Rapports
                                </a>
                                <a href="#api" class="list-group-item list-group-item-action">
                                    <i class="fas fa-code"></i> API & Intégrations
                                </a>
                                <a href="#best-practices" class="list-group-item list-group-item-action">
                                    <i class="fas fa-lightbulb"></i> Bonnes Pratiques
                                </a>
                            </div>
                            
                            <div class="card-body">
                                <a href="documentation-utilisateurs.php" class="btn btn-info w-100">
                                    <i class="fas fa-users"></i> Doc Utilisateurs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenu Documentation -->
                <div class="col-md-9 col-12">
                    <!-- Introduction -->
                    <section id="introduction" class="doc-section">
                        <h1 class="display-4 mb-4">
                            <i class="fas fa-graduation-cap text-primary"></i>
                            Documentation Administrateurs
                        </h1>
                        
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Guide Complet ERP</h5>
                            <p class="mb-0">Ce guide détaillé couvre toutes les fonctionnalités avancées de l'ERP pour optimiser la gestion de votre entreprise.</p>
                        </div>

                        <h3>Modules Principaux</h3>
                        <div class="table-responsive">
                        <table class="table table-features">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>Description</th>
                                    <th>Lien</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="fas fa-users text-primary"></i> Personnel</td>
                                    <td>Gestion complète des employés et intérimaires</td>
                                    <td><a href="employees.php" class="btn btn-sm btn-primary">Accéder</a></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-building text-info"></i> Entreprises</td>
                                    <td>Clients et partenaires commerciaux</td>
                                    <td><a href="companies.php" class="btn btn-sm btn-info">Accéder</a></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-tasks text-warning"></i> Missions</td>
                                    <td>Gestion de projets et affectations</td>
                                    <td><a href="missions.php" class="btn btn-sm btn-warning">Accéder</a></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-calendar-alt text-success"></i> Planning</td>
                                    <td>Planification des shifts et horaires</td>
                                    <td><a href="shifts.php" class="btn btn-sm btn-success">Accéder</a></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-chart-bar text-danger"></i> Ventes</td>
                                    <td>Facturation et devis</td>
                                    <td><a href="sales.php" class="btn btn-sm btn-danger">Accéder</a></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-file-invoice-dollar text-secondary"></i> Paies</td>
                                    <td>Bulletins de salaire et charges sociales</td>
                                    <td><a href="payroll.php" class="btn btn-sm btn-secondary">Accéder</a></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-university text-primary"></i> Comptabilité</td>
                                    <td>Synchronisation bancaire et écritures</td>
                                    <td><a href="accounting.php" class="btn btn-sm btn-primary">Accéder</a></td>
                                </tr>
                                <tr>
                                    <td><i class="fas fa-chart-pie text-dark"></i> Rapports</td>
                                    <td>Analytics et tableaux de bord avancés</td>
                                    <td><a href="reports.php" class="btn btn-sm btn-dark">Accéder</a></td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <!-- Personnel -->
                    <section id="personnel" class="doc-section">
                        <h2><i class="fas fa-users"></i> Gestion du Personnel</h2>

                        <h3>📌 Types d'Employés</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="step-card">
                                    <h4><i class="fas fa-user-tie text-primary"></i> Employés CDI/CDD</h4>
                                    <ul>
                                        <li>Contrats permanents</li>
                                        <li>Salaire mensuel fixe</li>
                                        <li>Charges sociales automatiques</li>
                                        <li>Congés et absences</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="step-card">
                                    <h4><i class="fas fa-user-clock text-success"></i> Intérimaires</h4>
                                    <ul>
                                        <li>Missions temporaires</li>
                                        <li>Taux horaire variable</li>
                                        <li>Facturation par période</li>
                                        <li>Gestion flexible</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <h3>✅ Import en Masse</h3>
                        <div class="step-card">
                            <ol>
                                <li>Préparez un fichier <strong>CSV</strong> avec les colonnes : Nom, Prénom, Email, Type, Salaire/Taux</li>
                                <li>Allez dans <a href="employees.php">Personnel</a> → <strong>Importer</strong></li>
                                <li>Chargez le fichier et mappez les colonnes</li>
                                <li>Validez l'import</li>
                            </ol>
                        </div>

                        <h3>📊 Calcul des Charges</h3>
                        <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Charge</th>
                                    <th>Taux</th>
                                    <th>Base</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Sécurité Sociale</td>
                                    <td>~22%</td>
                                    <td>Salaire brut</td>
                                </tr>
                                <tr>
                                    <td>Retraite complémentaire</td>
                                    <td>~8%</td>
                                    <td>Salaire brut</td>
                                </tr>
                                <tr>
                                    <td>Assurance chômage</td>
                                    <td>4.05%</td>
                                    <td>Salaire brut</td>
                                </tr>
                                <tr>
                                    <td>Formation professionnelle</td>
                                    <td>1%</td>
                                    <td>Salaire brut</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <!-- Planning -->
                    <section id="planning" class="doc-section">
                        <h2><i class="fas fa-calendar-alt"></i> Planning Avancé</h2>

                        <h3>🎯 Gestion des Shifts</h3>
                        <p>Le système de planning permet de gérer plusieurs types de shifts :</p>

                        <div class="step-card">
                            <h5>Types de Shifts :</h5>
                            <ul>
                                <li><strong>Normal</strong> : Horaires standard (8h-17h)</li>
                                <li><strong>Nuit</strong> : 21h-6h avec majoration 25%</li>
                                <li><strong>Weekend</strong> : Samedi/Dimanche avec majoration 50%</li>
                                <li><strong>Férié</strong> : Jours fériés avec double rémunération</li>
                            </ul>
                        </div>

                        <h3>📱 API Planning</h3>
                        <div class="step-card">
                            <h5>Endpoints disponibles :</h5>
                            <ul>
                                <li><code>GET /api/shifts</code> - Liste des shifts</li>
                                <li><code>POST /api/shifts</code> - Créer un shift</li>
                                <li><code>PUT /api/shifts/{id}</code> - Modifier un shift</li>
                                <li><code>DELETE /api/shifts/{id}</code> - Supprimer un shift</li>
                            </ul>
                        </div>

                        <h3>✅ Validation des Heures</h3>
                        <ol>
                            <li>L'employé valide ses heures via l'interface</li>
                            <li>Le manager approuve les shifts</li>
                            <li>Les heures validées sont prises en compte pour la paie</li>
                        </ol>

                        <div class="warning-box">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Important :</strong> Tous les shifts doivent être validés avant le 25 du mois pour la génération des paies.
                        </div>
                    </section>

                    <!-- Paie -->
                    <section id="paie" class="doc-section">
                        <h2><i class="fas fa-file-invoice-dollar"></i> Paie & Charges Sociales</h2>

                        <h3>💼 Génération Automatique des Paies</h3>
                        <div class="step-card">
                            <ol>
                                <li>Accédez à <a href="payroll.php">Paies</a></li>
                                <li>Cliquez sur <strong>"Générer Paies du Mois"</strong></li>
                                <li>Sélectionnez le mois et l'année</li>
                                <li>Le système calcule automatiquement :
                                    <ul>
                                        <li>Heures travaillées (shifts validés)</li>
                                        <li>Salaire brut</li>
                                        <li>Charges patronales et salariales</li>
                                        <li>Salaire net</li>
                                    </ul>
                                </li>
                                <li>Validez et générez les bulletins PDF</li>
                            </ol>
                        </div>

                        <h3>📧 Envoi Automatique</h3>
                        <p>Les bulletins de paie peuvent être envoyés automatiquement par email aux employés.</p>
                        
                        <div class="success-box">
                            <i class="fas fa-check-circle"></i> <strong>Automatisation :</strong> Configurez l'envoi automatique le dernier jour du mois via les paramètres de paie.
                        </div>

                        <h3>📊 Export DSN</h3>
                        <p>Exportez les déclarations sociales au format DSN pour transmission aux organismes sociaux :</p>
                        <ol>
                            <li>Allez dans Paies → Export DSN</li>
                            <li>Sélectionnez la période</li>
                            <li>Téléchargez le fichier XML</li>
                            <li>Importez-le sur net-entreprises.fr</li>
                        </ol>
                    </section>

                    <!-- Comptabilité -->
                    <section id="comptabilite" class="doc-section">
                        <h2><i class="fas fa-university"></i> Comptabilité & Banque</h2>

                        <h3>🏦 Synchronisation Bancaire</h3>
                        <p>L'ERP se connecte automatiquement à vos comptes bancaires via l'API bancaire.</p>

                        <div class="step-card">
                            <h5>Configuration :</h5>
                            <ol>
                                <li>Allez dans <a href="accounting.php">Comptabilité</a></li>
                                <li>Cliquez sur <strong>"Connecter Banque"</strong></li>
                                <li>Sélectionnez votre établissement (Bridge API)</li>
                                <li>Autorisez l'accès</li>
                                <li>Les transactions sont synchronisées quotidiennement</li>
                            </ol>
                        </div>

                        <h3>📝 Écritures Comptables</h3>
                        <p>Les écritures sont générées automatiquement pour :</p>
                        <ul>
                            <li>Factures clients (ventes)</li>
                            <li>Paies (charges sociales)</li>
                            <li>Achats et frais</li>
                            <li>Transactions bancaires</li>
                        </ul>

                        <h3>📊 Bilan & Compte de Résultat</h3>
                        <p>Générez vos états financiers en temps réel :</p>
                        <ul>
                            <li><strong>Bilan comptable</strong> : Actif / Passif</li>
                            <li><strong>Compte de résultat</strong> : Produits / Charges</li>
                            <li><strong>Tableau de trésorerie</strong> : Encaissements / Décaissements</li>
                        </ul>
                    </section>

                    <!-- Rapports -->
                    <section id="rapports" class="doc-section">
                        <h2><i class="fas fa-chart-pie"></i> Rapports & Analytics</h2>

                        <h3>📊 Tableaux de Bord Disponibles</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="step-card">
                                    <h5><i class="fas fa-chart-line text-success"></i> Performance RH</h5>
                                    <ul>
                                        <li>Taux d'absentéisme</li>
                                        <li>Turnover employés</li>
                                        <li>Coût salarial moyen</li>
                                        <li>Productivité par employé</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="step-card">
                                    <h5><i class="fas fa-euro-sign text-primary"></i> Performance Financière</h5>
                                    <ul>
                                        <li>Chiffre d'affaires</li>
                                        <li>Marge par mission</li>
                                        <li>Rentabilité globale</li>
                                        <li>Trésorerie prévisionnelle</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <h3>📈 Export des Données</h3>
                        <p>Tous les rapports peuvent être exportés en :</p>
                        <ul>
                            <li><strong>PDF</strong> : Pour impression</li>
                            <li><strong>Excel</strong> : Pour analyses complémentaires</li>
                            <li><strong>CSV</strong> : Pour imports externes</li>
                        </ul>
                    </section>

                    <!-- API -->
                    <section id="api" class="doc-section">
                        <h2><i class="fas fa-code"></i> API & Intégrations</h2>

                        <h3>🔌 API REST</h3>
                        <p>L'ERP expose une API REST complète pour intégrations tierces.</p>

                        <div class="step-card">
                            <h5>Authentification :</h5>
                            <pre><code>POST /api/auth/login
{
  "email": "admin@webitech.fr",
  "password": "***"
}

Retourne : { "token": "eyJhbGc..." }</code></pre>
                        </div>

                        <h3>📚 Endpoints Principaux</h3>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Endpoint</th>
                                    <th>Méthode</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>/api/employees</td>
                                    <td>GET, POST</td>
                                    <td>Liste et création d'employés</td>
                                </tr>
                                <tr>
                                    <td>/api/shifts</td>
                                    <td>GET, POST, PUT</td>
                                    <td>Gestion des shifts</td>
                                </tr>
                                <tr>
                                    <td>/api/missions</td>
                                    <td>GET, POST</td>
                                    <td>Gestion des missions</td>
                                </tr>
                                <tr>
                                    <td>/api/payroll</td>
                                    <td>GET, POST</td>
                                    <td>Génération de paies</td>
                                </tr>
                                <tr>
                                    <td>/api/invoices</td>
                                    <td>GET, POST</td>
                                    <td>Facturation</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="success-box">
                            <p>Consultez la <a href="API_DOCUMENTATION.md">documentation API complète</a> pour plus de détails.</p>
                        </div>
                    </section>

                    <!-- Best Practices -->
                    <section id="best-practices" class="doc-section">
                        <h2><i class="fas fa-lightbulb"></i> Meilleures Pratiques</h2>

                        <h3>✅ Gestion RH</h3>
                        <ul>
                            <li>🔹 Validez les shifts avant le 25 du mois</li>
                            <li>🔹 Vérifiez les absences et congés régulièrement</li>
                            <li>🔹 Mettez à jour les taux horaires annuellement</li>
                            <li>🔹 Archivez les bulletins de paie 50 ans minimum</li>
                        </ul>

                        <h3>💰 Finances</h3>
                        <ul>
                            <li>🔹 Synchronisez vos comptes bancaires quotidiennement</li>
                            <li>🔹 Exportez les déclarations sociales avant le 15 du mois</li>
                            <li>🔹 Surveillez la trésorerie hebdomadairement</li>
                            <li>🔹 Générez le bilan trimestriellement</li>
                        </ul>

                        <h3>📊 Rapports</h3>
                        <ul>
                            <li>🔹 Consultez les KPIs RH mensuellement</li>
                            <li>🔹 Analysez la rentabilité par mission</li>
                            <li>🔹 Suivez les coûts salariaux vs budget</li>
                            <li>🔹 Exportez les données pour audits externes</li>
                        </ul>

                        <h3>🔐 Sécurité</h3>
                        <ul>
                            <li>🔹 Activez l'authentification à deux facteurs</li>
                            <li>🔹 Créez des sauvegardes quotidiennes</li>
                            <li>🔹 Limitez les accès selon les rôles</li>
                            <li>🔹 Auditez les connexions régulièrement</li>
                        </ul>
                    </section>

                    <!-- Footer -->
                    <div class="text-center mt-5 mb-5">
                        <hr>
                        <p class="text-muted">
                            <i class="fas fa-book"></i> Documentation ERP Webitech - Version 1.0<br>
                            <small>Dernière mise à jour : 16 février 2026</small>
                        </p>
                        <div class="btn-group mt-3">
                            <a href="documentation-utilisateurs.php" class="btn btn-outline-info">
                                <i class="fas fa-users"></i> Doc Utilisateurs
                            </a>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-home"></i> Retour Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Highlight section active
    window.addEventListener('scroll', () => {
        const sections = document.querySelectorAll('.doc-section');
        const navLinks = document.querySelectorAll('.doc-sidebar .list-group-item');
        
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (scrollY >= sectionTop - 100) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });
    });

    // Sidebar search
    const sidebarSearchInput = document.getElementById('sidebarSearchInput');
    const navLinks = document.querySelectorAll('.doc-sidebar .list-group-item');
    
    if (sidebarSearchInput) {
        sidebarSearchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            navLinks.forEach(link => {
                const text = link.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    link.style.display = 'flex';
                } else {
                    link.style.display = 'none';
                }
            });
        });
    }
    </script>
</body>
</html>
