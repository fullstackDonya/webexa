<?php
include 'includes/verify_subscriptions.php';
$page_title = "Documentation Commerciaux - CRM Webitech";
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
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .doc-section {
            scroll-margin-top: 20px;
            margin-bottom: 60px;
        }
        .doc-section h2 {
            color: #2c3e50 !important;
            border-bottom: 3px solid #3498db;
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
            border-left: 4px solid #3498db;
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
        }
        .step-number {
            background: #3498db;
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
        .quick-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            text-decoration: none;
            display: block;
            transition: transform 0.3s;
        }
        .quick-link:hover {
            transform: translateY(-5px);
            color: white;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            color:black;
        }
        .success-box {
            background: #d1ecf1;
            border-left: 4px solid #0dcaf0;
            padding: 15px;
            margin: 20px 0;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .nav-pills .nav-link.active {
            background: #3498db;
        }
        .table-features {
            background: white;
        }
        .table-features th {
            background: #3498db;
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
        .card-body p, .card-body p a{
            color: #acb6bf !important;
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
        .quick-link h5,
        .quick-link small,
        .quick-link p{
            color: white !important;
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
        .badge.bg-warning{
            color: #000 !important;
        }
        .badge.bg-success,
        .badge.bg-danger,
        .badge.bg-primary,
        .badge.bg-info{
            color: white !important;
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
        .table tbody tr td strong{
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
        .text-muted{
            color: #6c757d !important;
        }
        .doc-section a:not(.btn),
        .card-body a:not(.btn),
        .step-card a:not(.btn),
        .feature-box a:not(.btn),
        .alert a:not(.btn),
        .container-fluid .col-md-9 a:not(.btn){
            color: #3498db !important;
        }
        .doc-section a:not(.btn):hover,
        .card-body a:not(.btn):hover,
        .step-card a:not(.btn):hover,
        .feature-box a:not(.btn):hover,
        .alert a:not(.btn):hover,
        .container-fluid .col-md-9 a:not(.btn):hover{
            color: #2980b9 !important;
        }
        .container-fluid{
            background-color: #ffffff !important;
        }
        .doc-section > p,
        .doc-section > ul,
        .doc-section > ol,
        .doc-section > h3,
        .doc-section > h4,
        .doc-section > h5{
            color: #2c3e50 !important;
        }
        .c-w{
            color: #dbe2e9 !important;
        }
        
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        <?php include 'includes/topbar.php'; ?>
        
        <div class="main-content">
            <div class="container-fluid">
                <div class="row">
                    <!-- Sidebar Documentation -->
                    <div class="col-md-3">
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
                                    <a href="#premiers-pas" class="list-group-item list-group-item-action">
                                        <i class="fas fa-walking"></i> Premiers Pas
                                    </a>
                                    <a href="#leads" class="list-group-item list-group-item-action">
                                        <i class="fas fa-user-plus"></i> Gestion des Leads
                                    </a>
                                    <a href="#pipeline" class="list-group-item list-group-item-action">
                                        <i class="fas fa-stream"></i> Pipeline de Vente
                                    </a>
                                    <a href="#email-oauth" class="list-group-item list-group-item-action">
                                        <i class="fas fa-envelope"></i> Email OAuth
                                    </a>
                                    <a href="#whatsapp" class="list-group-item list-group-item-action">
                                        <i class="fab fa-whatsapp"></i> WhatsApp Business
                                    </a>
                                    <a href="#campaigns" class="list-group-item list-group-item-action">
                                        <i class="fas fa-bullhorn"></i> Campagnes Marketing
                                    </a>
                                    <a href="#automations" class="list-group-item list-group-item-action">
                                        <i class="fas fa-robot"></i> Automations
                                    </a>
                                    <a href="#analytics" class="list-group-item list-group-item-action">
                                        <i class="fas fa-chart-bar"></i> Analytics & KPIs
                                    </a>
                                    <a href="#best-practices" class="list-group-item list-group-item-action">
                                        <i class="fas fa-lightbulb"></i> Meilleures Pratiques
                                    </a>
                                    <a href="#faq" class="list-group-item list-group-item-action">
                                        <i class="fas fa-question-circle"></i> FAQ
                                    </a>
                                </div>
                                
                                <div class="card-body">
                                    <a href="chat-assistant.php" class="btn btn-success w-100 mb-2">
                                        <i class="fas fa-comments"></i> Chat Assistant
                                    </a>
                                    <a href="documentation-clients.php" class="btn btn-info w-100">
                                        <i class="fas fa-users"></i> Doc Clients
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenu Documentation -->
                    <div class="col-md-9">
                        <!-- Introduction -->
                        <section id="introduction" class="doc-section">
                            <h1 class="display-4 mb-4">
                                <i class="fas fa-graduation-cap text-primary"></i>
                                Documentation Commerciaux
                            </h1>
                            
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Bienvenue dans votre CRM Webitech</h5>
                                <p class="mb-0">Ce guide complet vous accompagne dans l'utilisation de toutes les fonctionnalités du CRM pour maximiser vos performances commerciales.</p>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <a href="#leads" class="quick-link">
                                        <i class="fas fa-user-plus fa-2x mb-2"></i>
                                        <h5>Gérer vos Leads</h5>
                                        <small>Capture, qualification, scoring</small>
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="#pipeline" class="quick-link">
                                        <i class="fas fa-stream fa-2x mb-2"></i>
                                        <h5>Pipeline de Vente</h5>
                                        <small>Suivi opportunités, prévisions</small>
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="#campaigns" class="quick-link">
                                        <i class="fas fa-bullhorn fa-2x mb-2"></i>
                                        <h5>Campagnes</h5>
                                        <small>Email, WhatsApp, automation</small>
                                    </a>
                                </div>
                            </div>

                            <h3>Fonctionnalités Principales</h3>
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
                                        <td><i class="fas fa-users text-primary"></i> Contacts</td>
                                        <td>Gestion centralisée de tous vos contacts professionnels</td>
                                        <td><a href="contacts.php" class="btn btn-sm btn-primary">Accéder</a></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-user-plus text-success"></i> Leads</td>
                                        <td>Qualification et scoring automatique des prospects</td>
                                        <td><a href="leads.php" class="btn btn-sm btn-success">Accéder</a></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-handshake text-warning"></i> Opportunités</td>
                                        <td>Suivi des deals et prévisions de vente</td>
                                        <td><a href="opportunities.php" class="btn btn-sm btn-warning">Accéder</a></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-building text-info"></i> Entreprises</td>
                                        <td>Base de données clients et prospects B2B</td>
                                        <td><a href="customers.php" class="btn btn-sm btn-info">Accéder</a></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-envelope text-danger"></i> Email OAuth</td>
                                        <td>Intégration Gmail/Outlook avec sync bidirectionnelle</td>
                                        <td><a href="email-settings.php" class="btn btn-sm btn-danger">Accéder</a></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp</td>
                                        <td>Campagnes WhatsApp Business avec templates Meta</td>
                                        <td><a href="whatsapp-settings.php" class="btn btn-sm" style="background: #25D366; color: white;">Accéder</a></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-robot text-secondary"></i> Automations</td>
                                        <td>Workflows automatisés pour gagner du temps</td>
                                        <td><a href="automations-list.php" class="btn btn-sm btn-secondary">Accéder</a></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-chart-line text-dark"></i> Analytics</td>
                                        <td>Tableaux de bord et KPIs en temps réel</td>
                                        <td><a href="analytics-sales.php" class="btn btn-sm btn-dark">Accéder</a></td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>

                        <!-- Premiers Pas -->
                        <section id="premiers-pas" class="doc-section">
                            <h2><i class="fas fa-walking"></i> Premiers Pas</h2>

                            <div class="step-card">
                                <h4><span class="step-number">1</span> Connexion au CRM</h4>
                                <p>Accédez à <code>https://webitech.fr/crm/</code> avec vos identifiants.</p>
                                <ul>
                                    <li><strong>URL:</strong> https://webitech.fr/crm/</li>
                                    <li><strong>Identifiant:</strong> Votre email professionnel</li>
                                    <li><strong>Mot de passe:</strong> Fourni par votre administrateur</li>
                                </ul>
                            </div>

                            <div class="step-card">
                                <h4><span class="step-number">2</span> Configurer votre Email (OAuth)</h4>
                                <p>Connectez votre Gmail ou Outlook pour synchroniser automatiquement vos emails.</p>
                                <ol>
                                    <li>Allez dans <a href="email-settings.php"><i class="fas fa-cog"></i> Email (OAuth)</a></li>
                                    <li>Cliquez sur <strong>"🔐 Connecter Gmail"</strong> ou <strong>"🔐 Connecter Outlook"</strong></li>
                                    <li>Autorisez l'accès dans la fenêtre Google/Microsoft</li>
                                    <li>Vos emails seront synchronisés toutes les 5 minutes</li>
                                </ol>
                                <div class="success-box">
                                    <i class="fas fa-check-circle"></i> <strong>Avantage:</strong> Tous vos emails clients sont automatiquement liés aux contacts/leads dans le CRM !
                                </div>
                            </div>

                            <div class="step-card">
                                <h4><span class="step-number">3</span> Importer vos Contacts</h4>
                                <p>Importez votre base de contacts existante en quelques clics.</p>
                                <ol>
                                    <li>Préparez un fichier <strong>CSV</strong> avec colonnes: prénom, nom, email, téléphone, entreprise</li>
                                    <li>Allez dans <a href="contacts.php"><i class="fas fa-users"></i> Contacts</a></li>
                                    <li>Cliquez sur <strong>"Importer"</strong></li>
                                    <li>Chargez votre fichier CSV et mappez les colonnes</li>
                                    <li>Validez l'import</li>
                                </ol>
                            </div>

                            <div class="step-card">
                                <h4><span class="step-number">4</span> Personnaliser votre Dashboard</h4>
                                <p>Le <a href="index.php">tableau de bord</a> affiche vos KPIs principaux :</p>
                                <ul>
                                    <li>Nombre de leads actifs</li>
                                    <li>Opportunités en cours (valeur totale)</li>
                                    <li>Taux de conversion</li>
                                    <li>Chiffre d'affaires du mois</li>
                                    <li>Activités récentes</li>
                                </ul>
                            </div>
                        </section>

                        <!-- Gestion des Leads -->
                        <section id="leads" class="doc-section">
                            <h2><i class="fas fa-user-plus"></i> Gestion des Leads</h2>

                            <h3>📌 Qu'est-ce qu'un Lead ?</h3>
                            <p>Un <strong>lead</strong> est un prospect potentiel qui a montré un intérêt pour vos produits/services mais n'est pas encore qualifié comme client.</p>

                            <div class="schema-box">
                                <h5>Cycle de Vie du Lead</h5>
                                <div class="d-flex justify-content-around align-items-center">
                                    <div class="text-center">
                                        <i class="fas fa-user-plus fa-3x text-info"></i>
                                        <p class="mt-2"><strong>Nouveau</strong></p>
                                    </div>
                                    <i class="fas fa-arrow-right fa-2x text-muted"></i>
                                    <div class="text-center">
                                        <i class="fas fa-phone fa-3x text-primary"></i>
                                        <p class="mt-2"><strong>Contacté</strong></p>
                                    </div>
                                    <i class="fas fa-arrow-right fa-2x text-muted"></i>
                                    <div class="text-center">
                                        <i class="fas fa-check-circle fa-3x text-success"></i>
                                        <p class="mt-2"><strong>Qualifié</strong></p>
                                    </div>
                                    <i class="fas fa-arrow-right fa-2x text-muted"></i>
                                    <div class="text-center">
                                        <i class="fas fa-handshake fa-3x text-warning"></i>
                                        <p class="mt-2"><strong>Opportunité</strong></p>
                                    </div>
                                </div>
                            </div>

                            <h3>✅ Ajouter un Lead</h3>
                            <div class="step-card">
                                <ol>
                                    <li>Allez dans <a href="leads.php"><i class="fas fa-user-plus"></i> Leads</a></li>
                                    <li>Cliquez sur <strong>"+ Ajouter Lead"</strong></li>
                                    <li>Remplissez les informations :
                                        <ul>
                                            <li><strong>Nom/Prénom</strong> (requis)</li>
                                            <li><strong>Email</strong> (recommandé pour tracking)</li>
                                            <li><strong>Téléphone</strong></li>
                                            <li><strong>Entreprise</strong></li>
                                            <li><strong>Source</strong> : Site web, Salon, Référence, Cold calling, etc.</li>
                                            <li><strong>Statut</strong> : Nouveau, Contacté, Qualifié, Perdu</li>
                                        </ul>
                                    </li>
                                    <li>Cliquez sur <strong>"Enregistrer"</strong></li>
                                </ol>
                            </div>

                            <h3>🎯 Lead Scoring Automatique</h3>
                            <p>Le CRM attribue automatiquement un <strong>score de 0 à 100</strong> à chaque lead selon :</p>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Critère</th>
                                        <th>Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Email professionnel renseigné</td>
                                        <td>+10</td>
                                    </tr>
                                    <tr>
                                        <td>Téléphone renseigné</td>
                                        <td>+10</td>
                                    </tr>
                                    <tr>
                                        <td>Entreprise identifiée</td>
                                        <td>+15</td>
                                    </tr>
                                    <tr>
                                        <td>A ouvert un email de campagne</td>
                                        <td>+20</td>
                                    </tr>
                                    <tr>
                                        <td>A cliqué dans un email</td>
                                        <td>+30</td>
                                    </tr>
                                    <tr>
                                        <td>A répondu à une campagne WhatsApp</td>
                                        <td>+25</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="warning-box">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Astuce:</strong> Priorisez les leads avec un score > 60 pour maximiser votre taux de conversion !
                            </div>

                            <h3>📊 Gérer les Leads</h3>
                            <ul>
                                <li><strong>Filtrer</strong> par statut, source, score</li>
                                <li><strong>Rechercher</strong> par nom, email, entreprise</li>
                                <li><strong>Exporter</strong> en CSV pour analyse externe</li>
                                <li><strong>Convertir</strong> en opportunité quand qualifié</li>
                            </ul>
                        </section>

                        <!-- Pipeline de Vente -->
                        <section id="pipeline" class="doc-section">
                            <h2><i class="fas fa-stream"></i> Pipeline de Vente</h2>

                            <h3>📌 Qu'est-ce que le Pipeline ?</h3>
                            <p>Le pipeline visualise toutes vos <strong>opportunités de vente</strong> classées par étape du processus commercial.</p>

                            <div class="schema-box">
                                <h5>Étapes du Pipeline</h5>
                                <div class="row text-center">
                                    <div class="col">
                                        <i class="fas fa-lightbulb fa-2x text-info"></i>
                                        <p class="mt-2"><small><strong>Prospection</strong><br>50% prob.</small></p>
                                    </div>
                                    <div class="col">
                                        <i class="fas fa-phone fa-2x text-primary"></i>
                                        <p class="mt-2"><small><strong>Contact</strong><br>60% prob.</small></p>
                                    </div>
                                    <div class="col">
                                        <i class="fas fa-clipboard-list fa-2x text-warning"></i>
                                        <p class="mt-2"><small><strong>Proposition</strong><br>75% prob.</small></p>
                                    </div>
                                    <div class="col">
                                        <i class="fas fa-handshake fa-2x text-info"></i>
                                        <p class="mt-2"><small><strong>Négociation</strong><br>85% prob.</small></p>
                                    </div>
                                    <div class="col">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                        <p class="mt-2"><small><strong>Gagné</strong><br>100% prob.</small></p>
                                    </div>
                                </div>
                            </div>

                            <h3>✅ Créer une Opportunité</h3>
                            <div class="step-card">
                                <ol>
                                    <li>Depuis un lead qualifié, cliquez sur <strong>"Convertir en Opportunité"</strong></li>
                                    <li>OU allez dans <a href="opportunities.php"><i class="fas fa-handshake"></i> Opportunités</a> → <strong>"+ Nouvelle"</strong></li>
                                    <li>Remplissez :
                                        <ul>
                                            <li><strong>Nom du deal</strong> (ex: "Contrat SaaS - Entreprise X")</li>
                                            <li><strong>Montant estimé</strong> (en €)</li>
                                            <li><strong>Probabilité de closing</strong> (calculée auto selon l'étape)</li>
                                            <li><strong>Date de closing estimée</strong></li>
                                            <li><strong>Étape actuelle</strong> du pipeline</li>
                                        </ul>
                                    </li>
                                </ol>
                            </div>

                            <h3>🎯 Vue Kanban</h3>
                            <p>Accédez au <a href="pipeline-board.php"><strong>Tableau Pipeline</strong></a> pour glisser-déposer vos opportunités entre les étapes.</p>
                            <ul>
                                <li>Chaque colonne = une étape du pipeline</li>
                                <li>Glissez une carte d'opportunité pour la faire avancer</li>
                                <li>Le montant total par étape est affiché en haut</li>
                            </ul>

                            <h3>📈 Prévisions de Vente</h3>
                            <p>Le module <a href="opportunities-forecast.php"><strong>Forecast</strong></a> calcule automatiquement :</p>
                            <ul>
                                <li><strong>Chiffre d'affaires prévisionnel</strong> = Σ (Montant × Probabilité)</li>
                                <li><strong>Deals à risque</strong> (date de closing dépassée)</li>
                                <li><strong>Top opportunités</strong> par montant</li>
                            </ul>
                        </section>

                        <!-- Email OAuth -->
                        <section id="email-oauth" class="doc-section">
                            <h2><i class="fas fa-envelope"></i> Email OAuth (Gmail / Outlook)</h2>

                            <div class="success-box">
                                <i class="fas fa-rocket"></i> <strong>Nouveauté 2026 !</strong> Synchronisation bidirectionnelle avec Gmail et Outlook via OAuth 2.0 sécurisé.
                            </div>

                            <h3>📌 Pourquoi utiliser OAuth ?</h3>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Avant (IMAP)</th>
                                        <th>Maintenant (OAuth)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>❌ Mot de passe stocké</td>
                                        <td>✅ Connexion sécurisée sans mot de passe</td>
                                    </tr>
                                    <tr>
                                        <td>❌ Sync manuelle</td>
                                        <td>✅ Sync auto toutes les 5 min</td>
                                    </tr>
                                    <tr>
                                        <td>❌ Lecture seule</td>
                                        <td>✅ Envoi depuis le CRM</td>
                                    </tr>
                                    <tr>
                                        <td>❌ Erreurs fréquentes</td>
                                        <td>✅ Auto-refresh des tokens</td>
                                    </tr>
                                </tbody>
                            </table>

                            <h3>🔧 Configuration OAuth Gmail</h3>
                            <div class="step-card">
                                <h5>Étape 1 : Connecter Gmail</h5>
                                <ol>
                                    <li>Allez dans <a href="email-settings.php"><i class="fas fa-cog"></i> Email (OAuth)</a></li>
                                    <li>Cliquez sur <strong>"🔐 Connecter Gmail"</strong></li>
                                    <li>Vous serez redirigé vers Google</li>
                                    <li>Sélectionnez votre compte Gmail professionnel</li>
                                    <li>Autorisez les permissions suivantes :
                                        <ul>
                                            <li>✅ Lire vos emails</li>
                                            <li>✅ Envoyer des emails en votre nom</li>
                                            <li>✅ Modifier les labels/dossiers</li>
                                        </ul>
                                    </li>
                                    <li>Vous serez redirigé vers le CRM → <span class="badge bg-success">Connecté</span></li>
                                </ol>
                            </div>

                            <div class="step-card">
                                <h5>Étape 2 : Configuration Outlook</h5>
                                <p>Processus identique pour Microsoft 365 / Outlook.com :</p>
                                <ol>
                                    <li>Cliquez sur <strong>"🔐 Connecter Outlook"</strong></li>
                                    <li>Connectez-vous avec votre compte Microsoft</li>
                                    <li>Autorisez l'accès au CRM</li>
                                </ol>
                            </div>

                            <h3>📧 Envoyer des Emails depuis le CRM</h3>
                            <ul>
                                <li>Depuis une fiche <strong>Contact</strong>, <strong>Lead</strong> ou <strong>Opportunité</strong> → cliquez sur <i class="fas fa-envelope"></i> <strong>Envoyer Email</strong></li>
                                <li>L'email partira depuis <strong>VOTRE adresse Gmail/Outlook</strong> (pas une adresse générique)</li>
                                <li>Les réponses sont automatiquement trackées dans le CRM</li>
                            </ul>

                            <h3>🔄 Synchronisation Automatique</h3>
                            <p>Un <strong>worker en arrière-plan</strong> synchronise vos emails toutes les 5 minutes :</p>
                            <ul>
                                <li>Nouveaux emails reçus → liés au contact s'il existe</li>
                                <li>Emails envoyés → enregistrés dans l'historique</li>
                                <li>Pièces jointes → sauvegardées</li>
                            </ul>

                            <div class="warning-box">
                                <i class="fas fa-info-circle"></i> <strong>Note:</strong> Si le token expire, un badge <span class="badge bg-danger">Expiré</span> apparaît. Cliquez sur <strong>"Reconnecter"</strong> pour résoudre.
                            </div>
                        </section>

                        <!-- WhatsApp Business -->
                        <section id="whatsapp" class="doc-section">
                            <h2><i class="fab fa-whatsapp"></i> WhatsApp Business Cloud API</h2>

                            <div class="success-box">
                                <i class="fas fa-rocket"></i> <strong>Nouveauté 2026 !</strong> Envoyez des campagnes WhatsApp professionnelles avec templates approuvés Meta.
                            </div>

                            <h3>📌 Pourquoi WhatsApp dans le CRM ?</h3>
                            <ul>
                                <li>✅ <strong>Taux d'ouverture 98%</strong> (vs 20% pour email)</li>
                                <li>✅ <strong>Réponses instantanées</strong></li>
                                <li>✅ <strong>Templates approuvés</strong> par Meta (pas de spam)</li>
                                <li>✅ <strong>Coût faible</strong> : 0.005$ - 0.10$ par message</li>
                                <li>✅ <strong>Statistiques temps réel</strong> : envoyé, délivré, lu, répondu</li>
                            </ul>

                            <h3>🔧 Configuration WhatsApp Business</h3>
                            <div class="step-card">
                                <h5>Prérequis Meta</h5>
                                <ol>
                                    <li><strong>Créer un Meta Business Account</strong> sur <a href="https://business.facebook.com" target="_blank">business.facebook.com</a></li>
                                    <li><strong>Créer une App Meta</strong> dans le <a href="https://developers.facebook.com" target="_blank">Meta for Developers</a></li>
                                    <li><strong>Ajouter WhatsApp Business</strong> à votre app</li>
                                    <li><strong>Obtenir votre Phone Number ID</strong> et <strong>Access Token</strong></li>
                                </ol>
                                <p class="mt-3">📖 <a href="whatsapp-settings.php">Guide détaillé ici</a></p>
                            </div>

                            <div class="step-card">
                                <h5>Connecter WhatsApp au CRM</h5>
                                <ol>
                                    <li>Allez dans <a href="whatsapp-settings.php"><i class="fab fa-whatsapp"></i> WhatsApp Business</a></li>
                                    <li>Cliquez sur <strong>"+ Connecter WhatsApp"</strong></li>
                                    <li>Renseignez :
                                        <ul>
                                            <li><strong>Phone Number ID</strong> (ex: 123456789012345)</li>
                                            <li><strong>Business Account ID</strong></li>
                                            <li><strong>Numéro affiché</strong> (ex: +33 6 12 34 56 78)</li>
                                            <li><strong>Access Token</strong> (depuis Meta)</li>
                                        </ul>
                                    </li>
                                    <li>Testez la connexion → <span class="badge bg-success">Connecté</span></li>
                                </ol>
                            </div>

                            <h3>📝 Créer des Templates WhatsApp</h3>
                            <p>Les messages WhatsApp professionnels nécessitent des <strong>templates approuvés par Meta</strong>.</p>
                            
                            <div class="step-card">
                                <h5>Création Template</h5>
                                <ol>
                                    <li>Allez dans <a href="whatsapp-templates.php"><i class="fas fa-file-alt"></i> Templates WhatsApp</a></li>
                                    <li>Cliquez sur <strong>"+ Créer Template"</strong></li>
                                    <li>Remplissez :
                                        <ul>
                                            <li><strong>Nom</strong> (format: promo_ete_2026)</li>
                                            <li><strong>Langue</strong> (fr, en, es...)</li>
                                            <li><strong>Catégorie</strong> : MARKETING, UTILITY, AUTHENTICATION</li>
                                            <li><strong>Corps du message</strong> avec variables : <code>{{1}}</code>, <code>{{2}}</code></li>
                                            <li><strong>Header</strong> (optionnel) : texte, image, vidéo</li>
                                            <li><strong>Footer</strong> (optionnel, max 60 caractères)</li>
                                        </ul>
                                    </li>
                                    <li>Soumettez à Meta pour approbation</li>
                                    <li>Statut → <span class="badge bg-warning">PENDING</span> puis <span class="badge bg-success">APPROVED</span> (24-48h)</li>
                                </ol>
                            </div>

                            <h3>📣 Créer une Campagne WhatsApp</h3>
                            <div class="step-card">
                                <ol>
                                    <li>Allez dans <a href="whatsapp-campaigns.php"><i class="fab fa-whatsapp"></i> Campagnes WhatsApp</a></li>
                                    <li>Cliquez sur <strong>"+ Nouvelle Campagne"</strong></li>
                                    <li>Configurez :
                                        <ul>
                                            <li><strong>Nom</strong> de la campagne</li>
                                            <li><strong>Numéro WhatsApp</strong> expéditeur</li>
                                            <li><strong>Template approuvé</strong></li>
                                            <li><strong>Destinataires</strong> : Tous les leads / Tous les clients / Segment / Liste CSV</li>
                                            <li><strong>Programmation</strong> (optionnel)</li>
                                        </ul>
                                    </li>
                                    <li>Envoyez ou programmez</li>
                                    <li>Suivez les statistiques : Envoyé / Délivré / Lu / Répondu</li>
                                </ol>
                            </div>

                            <h3>💬 Conversations WhatsApp</h3>
                            <p>Accédez à <a href="whatsapp-conversations.php">Conversations</a> pour :</p>
                            <ul>
                                <li>Voir tous les messages entrants</li>
                                <li>Répondre dans la fenêtre de 24h</li>
                                <li>Lier les conversations aux leads/clients</li>
                            </ul>

                            <div class="warning-box">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Limite Tier:</strong> Nouveau numéro = 50 messages/jour. Monte automatiquement jusqu'à UNLIMITED selon la qualité.
                            </div>
                        </section>

                        <!-- Campagnes Marketing -->
                        <section id="campaigns" class="doc-section">
                            <h2><i class="fas fa-bullhorn"></i> Campagnes Marketing</h2>

                            <h3>📌 Types de Campagnes</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-primary mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <i class="fas fa-envelope"></i> Campagnes Email
                                        </div>
                                        <div class="card-body">
                                            <ul>
                                                <li class="c-w">Newsletters</li>
                                                <li class="c-w">Emails promotionnels</li>
                                                <li class="c-w">Relances automatiques</li>
                                                <li class="c-w">A/B Testing</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-success mb-3">
                                        <div class="card-header text-white" style="background: #25D366;">
                                            <i class="fab fa-whatsapp"></i> Campagnes WhatsApp
                                        </div>
                                        <div class="card-body">
                                            <ul >
                                                <li class="c-w">Messages promotionnels</li>
                                                <li class="c-w">Notifications transactionnelles</li>
                                                <li class="c-w">Codes de validation</li>
                                                <li class="c-w">Suivi de livraison</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h3>📧 Créer une Campagne Email</h3>
                            <div class="step-card">
                                <ol>
                                    <li>Allez dans <a href="campaigns.php"><i class="fas fa-bullhorn"></i> Campagnes</a></li>
                                    <li>Cliquez sur <strong>"+ Créer Campagne"</strong></li>
                                    <li>Choisissez <strong>Type: Email</strong></li>
                                    <li>Éditeur visuel :
                                        <ul>
                                            <li>Glissez-déposez des blocs (texte, image, bouton)</li>
                                            <li>Personnalisez avec <code>{firstname}</code>, <code>{company}</code></li>
                                            <li>Prévisualisez sur desktop/mobile</li>
                                        </ul>
                                    </li>
                                    <li>Sélectionnez la liste de destinataires</li>
                                    <li>Programmez ou envoyez immédiatement</li>
                                </ol>
                            </div>

                            <h3>📊 Statistiques des Campagnes</h3>
                            <p>Suivez les performances dans <a href="campaigns-stats.php">Statistiques</a> :</p>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Métrique</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Taux d'ouverture</strong></td>
                                        <td>% d'emails ouverts (objectif: >25%)</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Taux de clic (CTR)</strong></td>
                                        <td>% de clics sur les liens (objectif: >3%)</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Taux de conversion</strong></td>
                                        <td>% de destinataires ayant réalisé l'action souhaitée</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Désabonnements</strong></td>
                                        <td>% de désabonnements (à minimiser: <0.5%)</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Bounces</strong></td>
                                        <td>Emails non délivrés (nettoyer la liste si >5%)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>

                        <!-- Automations -->
                        <section id="automations" class="doc-section">
                            <h2><i class="fas fa-robot"></i> Automations</h2>

                            <div class="success-box">
                                <i class="fas fa-magic"></i> <strong>Gagnez du temps !</strong> Les automations exécutent des actions répétitives automatiquement.
                            </div>

                            <h3>📌 Scénarios d'Automation</h3>
                            
                            <div class="step-card">
                                <h5>Exemple 1 : Bienvenue Nouveau Lead</h5>
                                <p><strong>Déclencheur:</strong> Nouveau lead créé</p>
                                <p><strong>Actions:</strong></p>
                                <ol>
                                    <li>Attendre 5 minutes</li>
                                    <li>Envoyer email de bienvenue</li>
                                    <li>Attendre 2 jours</li>
                                    <li>Si pas de réponse → Envoyer email de relance</li>
                                    <li>Notifier le commercial assigné</li>
                                </ol>
                            </div>

                            <div class="step-card">
                                <h5>Exemple 2 : Relance Opportunité Inactive</h5>
                                <p><strong>Déclencheur:</strong> Opportunité sans activité depuis 7 jours</p>
                                <p><strong>Actions:</strong></p>
                                <ol>
                                    <li>Envoyer email au commercial</li>
                                    <li>Créer tâche de relance</li>
                                    <li>Si toujours inactive après 14 jours → Marquer comme "À risque"</li>
                                </ol>
                            </div>

                            <h3>🔧 Créer une Automation</h3>
                            <div class="step-card">
                                <ol>
                                    <li>Allez dans <a href="automations-list.php"><i class="fas fa-robot"></i> Automations</a></li>
                                    <li>Cliquez sur <strong>"+ Créer Automation"</strong></li>
                                    <li>Choisissez un <strong>déclencheur</strong> :
                                        <ul>
                                            <li>Nouveau contact/lead/opportunité</li>
                                            <li>Email ouvert/cliqué</li>
                                            <li>Score de lead atteint</li>
                                            <li>Date anniversaire</li>
                                            <li>Changement de statut</li>
                                        </ul>
                                    </li>
                                    <li>Ajoutez des <strong>actions</strong> :
                                        <ul>
                                            <li>Envoyer email</li>
                                            <li>Envoyer WhatsApp</li>
                                            <li>Créer tâche</li>
                                            <li>Mettre à jour champ</li>
                                            <li>Ajouter tag</li>
                                            <li>Notifier utilisateur</li>
                                            <li>Attendre X jours/heures</li>
                                        </ul>
                                    </li>
                                    <li>Ajoutez des <strong>conditions</strong> (SI... ALORS...)</li>
                                    <li>Activez l'automation</li>
                                </ol>
                            </div>

                            <h3>📊 Monitoring des Automations</h3>
                            <p>Consultez <a href="automations-view.php">Statistiques</a> pour voir :</p>
                            <ul>
                                <li>Nombre d'exécutions</li>
                                <li>Taux de réussite</li>
                                <li>Erreurs éventuelles</li>
                                <li>ROI par automation</li>
                            </ul>
                        </section>

                        <!-- Analytics -->
                        <section id="analytics" class="doc-section">
                            <h2><i class="fas fa-chart-bar"></i> Analytics & KPIs</h2>

                            <h3>📊 Tableaux de Bord Disponibles</h3>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <i class="fas fa-chart-line"></i> Analytics Ventes
                                        </div>
                                        <div class="card-body">
                                            <p><a href="analytics-sales.php">Performances commerciales</a> :</p>
                                            <ul >
                                                <li class="c-w">CA par mois/trimestre/année</li>
                                                <li class="c-w">Taux de conversion pipeline</li>
                                                <li class="c-w">Durée moyenne du cycle de vente</li>
                                                <li class="c-w">Top 10 commerciaux</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-header bg-warning text-dark">
                                            <i class="fas fa-funnel-dollar"></i> Analytics Funnel
                                        </div>
                                        <div class="card-body">
                                            <p><a href="analytics-funnel.php">Entonnoir de conversion</a> :</p>
                                            <ul >
                                                <li class="c-w">Visiteurs → Leads → Opportunités → Clients</li>
                                                <li class="c-w">Taux de conversion par étape</li>
                                                <li class="c-w">Points de friction identifiés</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-header bg-primary text-white">
                                            <i class="fas fa-users"></i> Analytics Clients
                                        </div>
                                        <div class="card-body">
                                            <p><a href="analytics-customer.php">Analyse clients</a> :</p>
                                            <ul >
                                                <li class="c-w">Valeur vie client (LTV)</li>
                                                <li class="c-w">Taux de rétention</li>
                                                <li class="c-w">Segmentation RFM</li>
                                                <li class="c-w">Clients à risque (churn)</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <i class="fas fa-brain "></i> AI Insights
                                        </div>
                                        <div class="card-body">
                                            <p><a href="ai-insights.php">Insights IA</a> :</p>
                                            <ul >
                                                <li class="c-w">Prédictions de conversion</li>
                                                <li class="c-w">Recommandations actions</li>
                                                <li class="c-w">Anomalies détectées</li>
                                                <li class="c-w">Opportunités identifiées</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h3>🎯 KPIs Clés à Suivre</h3>
                            <table class="table table-features">
                                <thead>
                                    <tr>
                                        <th>KPI</th>
                                        <th>Formule</th>
                                        <th>Objectif</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Taux de conversion Leads → Opportunités</td>
                                        <td>(Opportunités / Leads) × 100</td>
                                        <td>>30%</td>
                                    </tr>
                                    <tr>
                                        <td>Taux de closing</td>
                                        <td>(Deals gagnés / Total opportunités) × 100</td>
                                        <td>>25%</td>
                                    </tr>
                                    <tr>
                                        <td>Durée cycle de vente</td>
                                        <td>Date closing - Date création opportunité</td>
                                        <td><90 jours</td>
                                    </tr>
                                    <tr>
                                        <td>Valeur moyenne deal</td>
                                        <td>CA total / Nombre de deals</td>
                                        <td>Variable</td>
                                    </tr>
                                    <tr>
                                        <td>ROI Campagnes</td>
                                        <td>(CA généré - Coût campagne) / Coût × 100</td>
                                        <td>>300%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </section>

                        <!-- Meilleures Pratiques -->
                        <section id="best-practices" class="doc-section">
                            <h2><i class="fas fa-lightbulb"></i> Meilleures Pratiques</h2>

                            <h3>✅ Hygiène de Données</h3>
                            <ul>
                                <li>🔹 Supprimez les doublons régulièrement</li>
                                <li>🔹 Mettez à jour les informations contacts lors de chaque interaction</li>
                                <li>🔹 Utilisez des tags pour segmenter intelligemment</li>
                                <li>🔹 Nettoyez les emails invalides (bounces)</li>
                            </ul>

                            <h3>⏰ Routine Quotidienne Recommandée</h3>
                            <div class="step-card">
                                <h5>Checklist Matinale (15 min)</h5>
                                <ol>
                                    <li>✅ Consulter le <a href="index.php">dashboard</a> pour les KPIs du jour</li>
                                    <li>✅ Vérifier les <strong>tâches du jour</strong></li>
                                    <li>✅ Relire les <strong>nouveaux leads</strong> de la veille</li>
                                    <li>✅ Vérifier les <strong>opportunités à risque</strong> (date dépassée)</li>
                                    <li>✅ Consulter les emails reçus (via sync OAuth)</li>
                                </ol>
                            </div>

                            <h3>📧 Email : Bonnes Pratiques</h3>
                            <ul>
                                <li>✅ Utilisez des <strong>objets courts</strong> (<50 caractères)</li>
                                <li>✅ Personnalisez avec <code>{firstname}</code></li>
                                <li>✅ Ajoutez un <strong>CTA clair</strong> (bouton d'action)</li>
                                <li>✅ Testez sur mobile avant d'envoyer</li>
                                <li>✅ Envoyez entre 10h-11h ou 14h-15h (meilleur taux d'ouverture)</li>
                                <li>❌ Évitez les mots "GRATUIT", "URGENT" (filtre spam)</li>
                            </ul>

                            <h3>💬 WhatsApp : Bonnes Pratiques</h3>
                            <ul>
                                <li>✅ Utilisez des <strong>templates approuvés</strong> uniquement</li>
                                <li>✅ Respectez la fenêtre de 24h (hors conversation active)</li>
                                <li>✅ Personnalisez les variables <code>{{1}}</code>, <code>{{2}}</code></li>
                                <li>✅ Répondez rapidement (< 1h) pour maintenir la qualité</li>
                                <li>❌ Ne spammez pas → risque de blocage du numéro</li>
                            </ul>

                            <h3>🎯 Leads : Bonnes Pratiques</h3>
                            <ul>
                                <li>✅ <strong>Contactez dans les 5 minutes</strong> après réception (taux de conversion +9x)</li>
                                <li>✅ Utilisez le <strong>lead scoring</strong> pour prioriser</li>
                                <li>✅ Relancez 5-7 fois avant d'abandonner</li>
                                <li>✅ Variez les canaux : email, téléphone, WhatsApp, LinkedIn</li>
                            </ul>
                        </section>

                        <!-- FAQ -->
                        <section id="faq" class="doc-section">
                            <h2><i class="fas fa-question-circle"></i> FAQ - Questions Fréquentes</h2>

                            <div class="accordion" id="faqAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                            Comment importer mes contacts depuis Excel ?
                                        </button>
                                    </h2>
                                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            <ol>
                                                <li>Exportez votre fichier Excel en <strong>CSV</strong></li>
                                                <li>Allez dans <a href="contacts.php">Contacts</a> → <strong>Importer</strong></li>
                                                <li>Chargez votre CSV et mappez les colonnes</li>
                                                <li>Validez l'import</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                            Mon token Gmail est expiré, que faire ?
                                        </button>
                                    </h2>
                                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Allez dans <a href="email-settings.php">Email (OAuth)</a>, cliquez sur <strong>"Reconnecter"</strong> à côté de votre configuration Gmail. Vous serez redirigé vers Google pour ré-autoriser.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                            Comment créer un template WhatsApp ?
                                        </button>
                                    </h2>
                                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Allez dans <a href="whatsapp-templates.php">Templates WhatsApp</a> → <strong>Créer Template</strong>. Remplissez le formulaire et soumettez à Meta. L'approbation prend 24-48h. <a href="#whatsapp">Voir guide détaillé</a>.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                            Comment automatiser l'envoi d'emails de bienvenue ?
                                        </button>
                                    </h2>
                                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Créez une <a href="automations-list.php">automation</a> avec déclencheur <strong>"Nouveau lead"</strong> et action <strong>"Envoyer email"</strong>. <a href="#automations">Voir guide automations</a>.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                            Puis-je utiliser WhatsApp sans approbation Meta ?
                                        </button>
                                    </h2>
                                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Non, l'API WhatsApp Business Cloud nécessite obligatoirement un compte Meta Business et des templates approuvés pour envoyer des messages. C'est une protection anti-spam imposée par Meta.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                            Comment voir les emails synchronisés depuis Gmail ?
                                        </button>
                                    </h2>
                                    <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            Les emails sont automatiquement liés aux fiches contacts/leads. Ouvrez une fiche → onglet <strong>"Historique"</strong> pour voir tous les emails échangés.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-success mt-4">
                                <h5><i class="fas fa-headset"></i> Besoin d'aide supplémentaire ?</h5>
                                <p>Utilisez le <a href="chat-assistant.php" class="btn btn-success btn-sm">Chat Assistant</a> pour poser vos questions techniques !</p>
                            </div>
                        </section>

                        <!-- Footer Documentation -->
                        <div class="text-center mt-5 mb-5">
                            <hr>
                            <p class="text-muted">
                                <i class="fas fa-book"></i> Documentation CRM Webitech - Version 2.0 - Février 2026<br>
                                <small>Dernière mise à jour : 16 février 2026</small>
                            </p>
                            <div class="btn-group mt-3">
                                <a href="documentation-clients.php" class="btn btn-outline-info">
                                    <i class="fas fa-users"></i> Doc Clients
                                </a>
                                <a href="chat-assistant.php" class="btn btn-outline-success">
                                    <i class="fas fa-comments"></i> Chat Assistant
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

    // Highlight section active dans sidebar
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
