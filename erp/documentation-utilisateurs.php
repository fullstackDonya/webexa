<?php
session_start();
$page_title = "Documentation Utilisateurs - ERP Webitech";
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
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        .hero-section h1,
        .hero-section p,
        .hero-section .lead{
            color: white !important;
        }
        .doc-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            margin-bottom: 30px;
        }
        .doc-card:hover {
            transform: translateY(-10px);
        }
        .doc-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        .doc-card .card-header h2{
            color: white !important;
        }
        .step-badge {
            background: #667eea;
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .feature-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .feature-box p,
        .feature-box ul,
        .feature-box li,
        .feature-box h5,
        .feature-box strong{
            color: #2c3e50 !important;
        }
        .quick-nav {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        .sidebar-search {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
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
        .nav-pills .nav-link {
            color: #667eea;
            border-left: 3px solid transparent;
        }
        .nav-pills .nav-link.active {
            background: #667eea;
            color: white !important;
            border-left-color: #764ba2;
        }
        .card-body{
            background-color: white;
        }
        .card-body p,
        .card-body ul,
        .card-body li,
        .card-body ol,
        .card-body h4,
        .card-body h5,
        .card-body td{
            color: #2c3e50 !important;
        }
        h1, h2, h3, h4, h5, h6{
            color: #2c3e50 !important;
        }
        p{
            color: #2c3e50 !important;
        }
        ul li, ol li{
            color: #2c3e50 !important;
        }
        strong{
            color: #2c3e50 !important;
        }
        .alert{
            color: #2c3e50 !important;
        }
        .alert-info{
            color: #0c5460 !important;
            background-color: #d1ecf1;
        }
        .alert-warning{
            color: #856404 !important;
            background-color: #fff3cd;
        }
        .alert-success{
            color: #0f5132 !important;
            background-color: #d1e7dd;
        }
        .alert-danger{
            color: #842029 !important;
            background-color: #f8d7da;
        }
        .table{
            background-color: white;
        }
        .table thead th{
            color: #2c3e50 !important;
            background-color: #e9ecef;
        }
        .table tbody td,
        .table tbody tr{
            color: #2c3e50 !important;
        }
        .table-striped tbody tr:nth-of-type(odd){
            background-color: rgba(0,0,0,.02);
        }
        code{
            color: #e83e8c;
            background-color: #f8f9fa;
        }
        .badge{
            color: white !important;
        }
        .badge.bg-warning{
            color: #000 !important;
        }
        .doc-card a:not(.btn),
        .card-body a:not(.btn),
        .feature-box a:not(.btn),
        .alert a:not(.btn),
        .container-fluid .col-md-9 a:not(.btn){
            color: #667eea !important;
        }
        .doc-card a:not(.btn):hover,
        .card-body a:not(.btn):hover,
        .feature-box a:not(.btn):hover,
        .alert a:not(.btn):hover,
        .container-fluid .col-md-9 a:not(.btn):hover{
            color: #764ba2 !important;
        }
        .container-fluid{
            background-color: #ffffff !important;
        }
        small{
            color: inherit;
        }
        
        /* Responsive Styles */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0 !important;
                padding: 10px !important;
            }
            .hero-section {
                padding: 30px 0 !important;
            }
            .hero-section h1 {
                font-size: 2rem !important;
            }
            .hero-section .lead {
                font-size: 1rem !important;
            }
            .quick-nav {
                position: static !important;
                max-height: none !important;
                margin-bottom: 20px;
            }
            .doc-card {
                margin-bottom: 20px;
            }
            .container-fluid {
                padding: 10px !important;
            }
        }
        
        @media (max-width: 767.98px) {
            .hero-section h1 {
                font-size: 1.5rem !important;
            }
            .hero-section {
                padding: 20px 0 !important;
            }
            .col-md-4.text-end {
                text-align: center !important;
                margin-top: 15px;
            }
            .step-badge {
                width: 30px;
                height: 30px;
                font-size: 0.9rem;
            }
            .doc-card .card-header {
                padding: 15px;
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
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="display-3">
                            <i class="fas fa-graduation-cap"></i> 
                            Guide Utilisateur ERP
                        </h1>
                        <p class="lead">Bienvenue dans votre espace de gestion d'entreprise Webitech ERP. Ce guide vous accompagne pas à pas pour gérer efficacement vos opérations.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="index.php" class="btn btn-light btn-lg">
                            <i class="fas fa-home"></i> Retour Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="row">
                <!-- Navigation Rapide -->
                <div class="col-md-3 col-12 mb-4 mb-md-0">
                    <div class="quick-nav">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-compass"></i> Navigation</h5>
                            </div>
                            <div class="sidebar-search">
                                <input type="text" id="sidebarSearchInput" placeholder="Rechercher une section..." aria-label="Rechercher">
                            </div>
                            <div class="card-body p-0">
                                <div class="nav flex-column nav-pills p-3">
                                    <a class="nav-link active" href="#connexion">
                                        <i class="fas fa-sign-in-alt"></i> Première Connexion
                                    </a>
                                    <a class="nav-link" href="#interface">
                                        <i class="fas fa-desktop"></i> Interface
                                    </a>
                                    <a class="nav-link" href="#personnel">
                                        <i class="fas fa-users"></i> Personnel
                                    </a>
                                    <a class="nav-link" href="#planning">
                                        <i class="fas fa-calendar-alt"></i> Planning
                                    </a>
                                    <a class="nav-link" href="#missions">
                                        <i class="fas fa-tasks"></i> Missions
                                    </a>
                                    <a class="nav-link" href="#faq">
                                        <i class="fas fa-question-circle"></i> FAQ
                                    </a>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="documentation-admin.php" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="fas fa-book"></i> Doc Avancée
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contenu Principal -->
                <div class="col-md-9 col-12">
                    <!-- Première Connexion -->
                    <section id="connexion" class="mb-5">
                        <div class="doc-card card">
                            <div class="card-header">
                                <h2 class="mb-0"><i class="fas fa-sign-in-alt"></i> Première Connexion</h2>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    Vous avez reçu vos identifiants par email. Si ce n'est pas le cas, contactez votre administrateur.
                                </div>

                                <h4 class="mt-4"><span class="step-badge">1</span> Accéder à l'ERP</h4>
                                <div class="feature-box">
                                    <p><strong>URL de connexion :</strong></p>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" value="https://webitech.fr/erp/" readonly>
                                        <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('https://webitech.fr/erp/')">
                                            <i class="fas fa-copy"></i> Copier
                                        </button>
                                    </div>
                                    <p><i class="fas fa-bookmark"></i> Ajoutez cette URL à vos favoris pour un accès rapide.</p>
                                </div>

                                <h4 class="mt-4"><span class="step-badge">2</span> Se Connecter</h4>
                                <ol>
                                    <li>Saisissez votre <strong>email professionnel</strong></li>
                                    <li>Entrez votre <strong>mot de passe</strong></li>
                                    <li>Cliquez sur <strong>"Connexion"</strong></li>
                                </ol>

                                <h4 class="mt-4"><span class="step-badge">3</span> Sécurité</h4>
                                <div class="alert alert-warning">
                                    <i class="fas fa-shield-alt"></i> <strong>Important :</strong> Changez votre mot de passe lors de la première connexion pour des raisons de sécurité.
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Interface -->
                    <section id="interface" class="mb-5">
                        <div class="doc-card card">
                            <div class="card-header">
                                <h2 class="mb-0"><i class="fas fa-desktop"></i> Comprendre l'Interface</h2>
                            </div>
                            <div class="card-body">
                                <h4>📊 Tableau de Bord Principal</h4>
                                <p>Le <strong>Dashboard</strong> affiche les indicateurs clés de votre entreprise :</p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="feature-box">
                                            <h5><i class="fas fa-chart-line text-success"></i> KPIs Principaux</h5>
                                            <ul>
                                                <li>Nombre d'employés actifs</li>
                                                <li>Missions en cours</li>
                                                <li>Chiffre d'affaires mensuel</li>
                                                <li>Coûts et marges</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-box">
                                            <h5><i class="fas fa-clock text-primary"></i> Activités</h5>
                                            <ul>
                                                <li>Planning du jour</li>
                                                <li>Missions urgentes</li>
                                                <li>Notifications importantes</li>
                                                <li>Tâches à valider</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <h4 class="mt-4">🧭 Menu de Navigation</h4>
                                <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Section</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><i class="fas fa-chart-line text-primary"></i> <strong>Dashboard</strong></td>
                                            <td>Vue d'ensemble des activités de l'entreprise</td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-users text-info"></i> <strong>Personnel</strong></td>
                                            <td>Gestion des employés et intérimaires</td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-building text-secondary"></i> <strong>Entreprises</strong></td>
                                            <td>Clients et partenaires</td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-tasks text-warning"></i> <strong>Missions</strong></td>
                                            <td>Projets et affectations</td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-calendar-alt text-success"></i> <strong>Planning</strong></td>
                                            <td>Gestion des horaires et shifts</td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-chart-bar text-danger"></i> <strong>Ventes</strong></td>
                                            <td>Factures et devis</td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-file-invoice-dollar text-info"></i> <strong>Paies</strong></td>
                                            <td>Gestion des salaires</td>
                                        </tr>
                                        <tr>
                                            <td><i class="fas fa-chart-pie text-primary"></i> <strong>Rapports</strong></td>
                                            <td>Analyses et statistiques</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- Personnel -->
                    <section id="personnel" class="mb-5">
                        <div class="doc-card card">
                            <div class="card-header">
                                <h2 class="mb-0"><i class="fas fa-users"></i> Gestion du Personnel</h2>
                            </div>
                            <div class="card-body">
                                <h4>👥 Types d'Employés</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="feature-box">
                                            <h5><i class="fas fa-user-tie text-primary"></i> Employés Permanents</h5>
                                            <ul>
                                                <li>CDI et CDD</li>
                                                <li>Salaire fixe mensuel</li>
                                                <li>Avantages sociaux</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="feature-box">
                                            <h5><i class="fas fa-user-clock text-success"></i> Intérimaires</h5>
                                            <ul>
                                                <li>Missions temporaires</li>
                                                <li>Taux horaire</li>
                                                <li>Gestion par période</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <h4 class="mt-4">✅ Ajouter un Employé</h4>
                                <ol>
                                    <li>Allez dans <strong>Personnel</strong></li>
                                    <li>Cliquez sur <strong>"+ Ajouter Employé"</strong></li>
                                    <li>Remplissez les informations :
                                        <ul>
                                            <li>Nom, Prénom</li>
                                            <li>Email et téléphone</li>
                                            <li>Type de contrat</li>
                                            <li>Date d'embauche</li>
                                            <li>Salaire/Taux horaire</li>
                                        </ul>
                                    </li>
                                    <li>Enregistrez</li>
                                </ol>

                                <div class="alert alert-success">
                                    <i class="fas fa-lightbulb"></i> <strong>Astuce :</strong> Utilisez les filtres pour trouver rapidement un employé par statut, département ou type de contrat.
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Planning -->
                    <section id="planning" class="mb-5">
                        <div class="doc-card card">
                            <div class="card-header">
                                <h2 class="mb-0"><i class="fas fa-calendar-alt"></i> Gestion du Planning</h2>
                            </div>
                            <div class="card-body">
                                <h4>📅 Créer un Shift</h4>
                                <p>Les shifts permettent de planifier les horaires de travail de vos employés.</p>
                                
                                <div class="feature-box">
                                    <h5>Étapes de création :</h5>
                                    <ol>
                                        <li>Accédez à <strong>Planning</strong></li>
                                        <li>Cliquez sur <strong>"+ Nouveau Shift"</strong></li>
                                        <li>Sélectionnez :
                                            <ul>
                                                <li>L'employé</li>
                                                <li>La mission ou le client</li>
                                                <li>Date et horaires (début/fin)</li>
                                                <li>Type de shift (normal, nuit, weekend)</li>
                                            </ul>
                                        </li>
                                        <li>Validez</li>
                                    </ol>
                                </div>

                                <h4 class="mt-4">🔄 Modifications et Validations</h4>
                                <ul>
                                    <li><strong>Modifier un shift :</strong> Cliquez sur le shift dans le calendrier</li>
                                    <li><strong>Valider les heures :</strong> L'employé peut valider ses heures travaillées</li>
                                    <li><strong>Export :</strong> Exportez le planning en PDF ou Excel</li>
                                </ul>

                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> <strong>Important :</strong> Les shifts doivent être validés avant la génération des paies.
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Missions -->
                    <section id="missions" class="mb-5">
                        <div class="doc-card card">
                            <div class="card-header">
                                <h2 class="mb-0"><i class="fas fa-tasks"></i> Gestion des Missions</h2>
                            </div>
                            <div class="card-body">
                                <h4>🎯 Qu'est-ce qu'une Mission ?</h4>
                                <p>Une mission représente un projet ou un contrat client sur lequel travaillent vos employés.</p>

                                <h4 class="mt-4">✅ Créer une Mission</h4>
                                <ol>
                                    <li>Allez dans <strong>Missions & Projets</strong></li>
                                    <li>Cliquez sur <strong>"+ Nouvelle Mission"</strong></li>
                                    <li>Configurez :
                                        <ul>
                                            <li>Nom de la mission</li>
                                            <li>Client/Entreprise</li>
                                            <li>Date de début et fin</li>
                                            <li>Budget et taux de facturation</li>
                                            <li>Statut (En cours, Terminée, En attente)</li>
                                        </ul>
                                    </li>
                                    <li>Affectez les employés</li>
                                    <li>Enregistrez</li>
                                </ol>

                                <h4 class="mt-4">📊 Suivi de Mission</h4>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Indicateur</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Heures travaillées</strong></td>
                                            <td>Total des heures effectuées sur la mission</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Budget consommé</strong></td>
                                            <td>% du budget utilisé vs prévu</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Marge</strong></td>
                                            <td>Rentabilité de la mission</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Employés affectés</strong></td>
                                            <td>Liste des ressources sur le projet</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- FAQ -->
                    <section id="faq" class="mb-5">
                        <div class="doc-card card">
                            <div class="card-header">
                                <h2 class="mb-0"><i class="fas fa-question-circle"></i> FAQ</h2>
                            </div>
                            <div class="card-body">
                                <div class="accordion" id="faqAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                                Comment importer des employés depuis Excel ?
                                            </button>
                                        </h2>
                                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                Allez dans Personnel → Importer, charger votre fichier Excel (format : Nom, Prénom, Email, Type contrat, Salaire) et validez l'import.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                                Comment générer les bulletins de paie ?
                                            </button>
                                        </h2>
                                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                Allez dans Paies → Générer paies, sélectionnez le mois et l'année, puis cliquez sur "Générer". Les bulletins seront créés automatiquement.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                                Comment exporter le planning ?
                                            </button>
                                        </h2>
                                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                Dans Planning, utilisez les boutons "Exporter PDF" ou "Exporter Excel" en haut à droite du calendrier.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                                Comment suivre la rentabilité d'une mission ?
                                            </button>
                                        </h2>
                                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body">
                                                Ouvrez la fiche mission, l'onglet "Finances" affiche les coûts (salaires), le chiffre d'affaires facturé et la marge en temps réel.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-success mt-4">
                                    <h5><i class="fas fa-headset"></i> Besoin d'aide ?</h5>
                                    <p class="mb-0">Contactez le support technique à <strong>support@webitech.fr</strong> ou consultez la documentation avancée.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Footer -->
                    <div class="text-center mt-5 mb-5">
                        <hr>
                        <p class="text-muted">
                            <i class="fas fa-book"></i> Documentation ERP Webitech - Version 1.0<br>
                            <small>Dernière mise à jour : 16 février 2026</small>
                        </p>
                        <div class="btn-group mt-3">
                            <a href="documentation-admin.php" class="btn btn-outline-info">
                                <i class="fas fa-book-open"></i> Doc Avancée
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
    // Smooth scroll pour les ancres
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
        const sections = document.querySelectorAll('.doc-card');
        const navLinks = document.querySelectorAll('.quick-nav .nav-link');
        
        let current = '';
        sections.forEach(section => {
            const sectionParent = section.closest('section');
            if (sectionParent) {
                const sectionTop = sectionParent.offsetTop;
                if (scrollY >= sectionTop - 100) {
                    current = sectionParent.getAttribute('id');
                }
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
    const navLinks = document.querySelectorAll('.quick-nav .nav-link');
    
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
