<?php
include 'includes/verify_subscriptions.php';
$page_title = "Documentation Clients - CRM Webitech";
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
        .video-placeholder {
            background: #e9ecef;
            border: 2px dashed #adb5bd;
            padding: 60px;
            text-align: center;
            border-radius: 10px;
            margin: 20px 0;
        }
        .video-placeholder p,
        .video-placeholder small{
            color: #6c757d !important;
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
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-3">
                                <i class="fas fa-graduation-cap"></i> 
                                Guide Utilisateur CRM
                            </h1>
                            <p class="lead">Bienvenue dans votre espace client Webitech CRM. Ce guide vous accompagne pas à pas pour tirer le meilleur parti de votre CRM.</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="chat-assistant.php" class="btn btn-light btn-lg">
                                <i class="fas fa-headset"></i> Assistance en Direct
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid">
                <div class="row">
                    <!-- Navigation Rapide -->
                    <div class="col-md-3">
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
                                        <a class="nav-link" href="#contacts">
                                            <i class="fas fa-users"></i> Contacts
                                        </a>
                                        <a class="nav-link" href="#leads">
                                            <i class="fas fa-user-plus"></i> Leads
                                        </a>
                                        <a class="nav-link" href="#rapports">
                                            <i class="fas fa-chart-bar"></i> Rapports
                                        </a>
                                        <a class="nav-link" href="#faq">
                                            <i class="fas fa-question-circle"></i> FAQ
                                        </a>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <a href="documentation-commerciaux.php" class="btn btn-sm btn-outline-primary w-100">
                                        <i class="fas fa-book"></i> Doc Avancée
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contenu Principal -->
                    <div class="col-md-9">
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

                                    <h4 class="mt-4"><span class="step-badge">1</span> Accéder au CRM</h4>
                                    <div class="feature-box">
                                        <p><strong>URL de connexion :</strong></p>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" value="https://webitech.fr/crm/" readonly>
                                            <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('https://webitech.fr/crm/')">
                                                <i class="fas fa-copy"></i> Copier
                                            </button>
                                        </div>
                                        <p><i class="fas fa-bookmark"></i> Ajoutez cette URL à vos favoris pour un accès rapide.</p>
                                    </div>

                                    <h4 class="mt-4"><span class="step-badge">2</span> Se Connecter</h4>
                                    <ol>
                                        <li>Saisissez votre <strong>email professionnel</strong></li>
                                        <li>Entrez votre <strong>mot de passe</strong> (fourni par email)</li>
                                        <li>Cliquez sur <strong>"Connexion"</strong></li>
                                    </ol>

                                    <div class="video-placeholder">
                                        <i class="fas fa-video fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Vidéo tutoriel : Première connexion au CRM</p>
                                        <small>(À venir)</small>
                                    </div>

                                    <h4 class="mt-4"><span class="step-badge">3</span> Changer votre Mot de Passe</h4>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-shield-alt"></i> <strong>Sécurité :</strong> Nous vous recommandons de changer votre mot de passe dès la première connexion.
                                    </div>
                                    <ol>
                                        <li>Cliquez sur votre profil (en haut à droite)</li>
                                        <li>Sélectionnez <strong>"Paramètres"</strong></li>
                                        <li>Onglet <strong>"Sécurité"</strong></li>
                                        <li>Choisissez un mot de passe fort (min. 8 caractères, majuscules, chiffres, symboles)</li>
                                    </ol>
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
                                    <p>Dès votre connexion, vous arrivez sur le <strong>Dashboard</strong> qui affiche :</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="feature-box">
                                                <h5><i class="fas fa-chart-line text-success"></i> KPIs Clés</h5>
                                                <ul>
                                                    <li>Nombre de contacts actifs</li>
                                                    <li>Leads en cours</li>
                                                    <li>Opportunités de vente</li>
                                                    <li>Chiffre d'affaires du mois</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="feature-box">
                                                <h5><i class="fas fa-tasks text-primary"></i> Activités Récentes</h5>
                                                <ul>
                                                    <li>Derniers contacts ajoutés</li>
                                                    <li>Emails envoyés/reçus</li>
                                                    <li>Tâches en attente</li>
                                                    <li>Notifications importantes</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <h4 class="mt-4">🧭 Menu de Navigation (Sidebar)</h4>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Icône</th>
                                                <th>Section</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><i class="fas fa-home fa-lg text-primary"></i></td>
                                                <td><strong>Dashboard</strong></td>
                                                <td>Vue d'ensemble de vos activités</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-users fa-lg text-info"></i></td>
                                                <td><strong>Contacts</strong></td>
                                                <td>Gérer tous vos contacts professionnels</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-user-plus fa-lg text-success"></i></td>
                                                <td><strong>Leads</strong></td>
                                                <td>Prospects à qualifier</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-handshake fa-lg text-warning"></i></td>
                                                <td><strong>Opportunités</strong></td>
                                                <td>Deals en cours de négociation</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-building fa-lg text-secondary"></i></td>
                                                <td><strong>Entreprises</strong></td>
                                                <td>Base clients B2B</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-envelope fa-lg text-danger"></i></td>
                                                <td><strong>Email</strong></td>
                                                <td>Configuration emails OAuth</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fab fa-whatsapp fa-lg" style="color:#25D366"></i></td>
                                                <td><strong>WhatsApp</strong></td>
                                                <td>Campagnes WhatsApp Business</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-bullhorn fa-lg text-primary"></i></td>
                                                <td><strong>Campagnes</strong></td>
                                                <td>Marketing automation</td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-chart-bar fa-lg text-dark"></i></td>
                                                <td><strong>Analytics</strong></td>
                                                <td>Rapports et statistiques</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div class="alert alert-success">
                                        <i class="fas fa-lightbulb"></i> <strong>Astuce :</strong> Utilisez la barre de recherche (en haut) pour trouver rapidement un contact, lead ou entreprise.
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Contacts -->
                        <section id="contacts" class="mb-5">
                            <div class="doc-card card">
                                <div class="card-header">
                                    <h2 class="mb-0"><i class="fas fa-users"></i> Gérer vos Contacts</h2>
                                </div>
                                <div class="card-body">
                                    <h4>📌 Qu'est-ce qu'un Contact ?</h4>
                                    <p>Un contact est une personne (client, prospect, partenaire) avec qui vous êtes en relation professionnelle.</p>

                                    <h4 class="mt-4">➕ Ajouter un Contact</h4>
                                    <ol>
                                        <li>Cliquez sur <strong><i class="fas fa-users"></i> Contacts</strong> dans le menu</li>
                                        <li>Cliquez sur le bouton <strong>"+ Ajouter Contact"</strong></li>
                                        <li>Remplissez le formulaire :
                                            <ul>
                                                <li><strong>Prénom / Nom</strong> (requis)</li>
                                                <li><strong>Email</strong></li>
                                                <li><strong>Téléphone</strong></li>
                                                <li><strong>Entreprise</strong> (liez à une entreprise existante ou créez-en une nouvelle)</li>
                                                <li><strong>Poste</strong> (ex: Directeur Commercial)</li>
                                                <li><strong>Adresse</strong></li>
                                            </ul>
                                        </li>
                                        <li>Cliquez sur <strong>"Enregistrer"</strong></li>
                                    </ol>

                                    <div class="video-placeholder">
                                        <i class="fas fa-video fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Vidéo tutoriel : Ajouter et gérer un contact</p>
                                    </div>

                                    <h4 class="mt-4">🔍 Rechercher un Contact</h4>
                                    <div class="feature-box">
                                        <p>Utilisez la <strong>barre de recherche</strong> en haut de la page Contacts :</p>
                                        <ul>
                                            <li>Recherche par <strong>nom</strong></li>
                                            <li>Recherche par <strong>email</strong></li>
                                            <li>Recherche par <strong>entreprise</strong></li>
                                            <li>Recherche par <strong>téléphone</strong></li>
                                        </ul>
                                    </div>

                                    <h4 class="mt-4">✏️ Modifier un Contact</h4>
                                    <ol>
                                        <li>Trouvez le contact dans la liste</li>
                                        <li>Cliquez sur son nom pour ouvrir la fiche détaillée</li>
                                        <li>Cliquez sur <strong>"Modifier"</strong></li>
                                        <li>Apportez vos modifications</li>
                                        <li>Cliquez sur <strong>"Enregistrer"</strong></li>
                                    </ol>

                                    <h4 class="mt-4">📧 Envoyer un Email à un Contact</h4>
                                    <p>Depuis la fiche contact :</p>
                                    <ol>
                                        <li>Cliquez sur l'icône <i class="fas fa-envelope text-primary"></i> <strong>Envoyer Email</strong></li>
                                        <li>Rédigez votre message</li>
                                        <li>L'email partira depuis votre adresse Gmail/Outlook connectée</li>
                                        <li>La conversation sera automatiquement enregistrée dans l'historique</li>
                                    </ol>

                                    <h4 class="mt-4">📊 Historique des Interactions</h4>
                                    <p>Chaque fiche contact affiche :</p>
                                    <ul>
                                        <li>✉️ <strong>Emails</strong> envoyés/reçus</li>
                                        <li>📞 <strong>Appels</strong> téléphoniques</li>
                                        <li>📝 <strong>Notes</strong> ajoutées</li>
                                        <li>🗓️ <strong>Rendez-vous</strong> planifiés</li>
                                        <li>💬 <strong>Messages WhatsApp</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </section>

                        <!-- Leads -->
                        <section id="leads" class="mb-5">
                            <div class="doc-card card">
                                <div class="card-header">
                                    <h2 class="mb-0"><i class="fas fa-user-plus"></i> Travailler avec les Leads</h2>
                                </div>
                                <div class="card-body">
                                    <h4>📌 Lead vs Contact : Quelle Différence ?</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="feature-box">
                                                <h5><i class="fas fa-user-plus text-success"></i> Lead</h5>
                                                <p><strong>Prospect potentiel</strong> qui a montré un intérêt mais n'est pas encore qualifié.</p>
                                                <p><em>Exemple :</em> Quelqu'un qui a téléchargé un document sur votre site.</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="feature-box">
                                                <h5><i class="fas fa-users text-primary"></i> Contact</h5>
                                                <p><strong>Personne identifiée</strong> avec qui vous avez une relation établie.</p>
                                                <p><em>Exemple :</em> Un client actuel ou un partenaire commercial.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <h4 class="mt-4">📊 Statuts des Leads</h4>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Statut</th>
                                                <th>Description</th>
                                                <th>Action Recommandée</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><span class="badge bg-info">Nouveau</span></td>
                                                <td>Lead tout juste créé, pas encore contacté</td>
                                                <td>Contacter dans les 5 minutes</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-primary">Contacté</span></td>
                                                <td>Premier contact effectué, en attente de réponse</td>
                                                <td>Planifier une relance sous 48h</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-success">Qualifié</span></td>
                                                <td>Lead intéressé et correspondant au profil cible</td>
                                                <td>Convertir en opportunité</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge bg-danger">Perdu</span></td>
                                                <td>Pas intéressé ou hors cible</td>
                                                <td>Archiver</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <h4 class="mt-4">🎯 Score de Lead</h4>
                                    <p>Chaque lead reçoit automatiquement un <strong>score de 0 à 100</strong> basé sur :</p>
                                    <ul>
                                        <li>Complétude des informations (email, téléphone, entreprise)</li>
                                        <li>Engagement (ouverture emails, clics)</li>
                                        <li>Source d'acquisition</li>
                                        <li>Interactions avec votre contenu</li>
                                    </ul>

                                    <div class="alert alert-success">
                                        <i class="fas fa-trophy"></i> <strong>Priorisez</strong> les leads avec un score > 60 pour maximiser vos chances de conversion !
                                    </div>

                                    <h4 class="mt-4">🔄 Convertir un Lead en Opportunité</h4>
                                    <p>Quand un lead est qualifié et prêt à acheter :</p>
                                    <ol>
                                        <li>Ouvrez la fiche du lead</li>
                                        <li>Cliquez sur <strong>"Convertir en Opportunité"</strong></li>
                                        <li>Le système crée automatiquement :
                                            <ul>
                                                <li>Un <strong>Contact</strong></li>
                                                <li>Une <strong>Opportunité de vente</strong></li>
                                            </ul>
                                        </li>
                                        <li>Renseignez le montant estimé et la date de closing</li>
                                    </ol>
                                </div>
                            </div>
                        </section>

                        <!-- Rapports -->
                        <section id="rapports" class="mb-5">
                            <div class="doc-card card">
                                <div class="card-header">
                                    <h2 class="mb-0"><i class="fas fa-chart-bar"></i> Consulter vos Rapports</h2>
                                </div>
                                <div class="card-body">
                                    <h4>📊 Rapports Disponibles</h4>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card border-info">
                                                <div class="card-header bg-info text-white">
                                                    <i class="fas fa-chart-line"></i> Rapport de Ventes
                                                </div>
                                                <div class="card-body">
                                                    <p>Visualisez vos performances commerciales :</p>
                                                    <ul>
                                                        <li>Chiffre d'affaires par période</li>
                                                        <li>Évolution mensuelle</li>
                                                        <li>Top produits/services vendus</li>
                                                        <li>Taux de conversion</li>
                                                    </ul>
                                                    <a href="analytics-sales.php" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i> Voir Rapport
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <div class="card border-success">
                                                <div class="card-header bg-success text-white">
                                                    <i class="fas fa-users"></i> Rapport Clients
                                                </div>
                                                <div class="card-body">
                                                    <p>Analysez votre base clients :</p>
                                                    <ul>
                                                        <li>Nombre de clients actifs</li>
                                                        <li>Nouveaux clients du mois</li>
                                                        <li>Taux de rétention</li>
                                                        <li>Valeur vie client (LTV)</li>
                                                    </ul>
                                                    <a href="analytics-customer.php" class="btn btn-success btn-sm">
                                                        <i class="fas fa-eye"></i> Voir Rapport
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <div class="card border-warning">
                                                <div class="card-header bg-warning text-dark">
                                                    <i class="fas fa-funnel-dollar"></i> Rapport Pipeline
                                                </div>
                                                <div class="card-body">
                                                    <p>Suivez vos opportunités :</p>
                                                    <ul>
                                                        <li>Opportunités par étape</li>
                                                        <li>Montant total du pipeline</li>
                                                        <li>Prévisions de closing</li>
                                                        <li>Deals à risque</li>
                                                    </ul>
                                                    <a href="opportunities-analytics.php" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-eye"></i> Voir Rapport
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <div class="card border-primary">
                                                <div class="card-header bg-primary text-white">
                                                    <i class="fas fa-bullhorn"></i> Rapport Campagnes
                                                </div>
                                                <div class="card-body">
                                                    <p>Mesurez l'efficacité de vos campagnes :</p>
                                                    <ul>
                                                        <li>Taux d'ouverture emails</li>
                                                        <li>Taux de clic (CTR)</li>
                                                        <li>ROI par campagne</li>
                                                        <li>Conversions générées</li>
                                                    </ul>
                                                    <a href="campaigns-stats.php" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-eye"></i> Voir Rapport
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h4 class="mt-4">📥 Exporter vos Données</h4>
                                    <p>Tous les rapports peuvent être exportés :</p>
                                    <ul>
                                        <li><i class="fas fa-file-csv text-success"></i> <strong>Format CSV</strong> pour Excel</li>
                                        <li><i class="fas fa-file-pdf text-danger"></i> <strong>Format PDF</strong> pour impression</li>
                                        <li><i class="fas fa-file-excel text-success"></i> <strong>Format Excel</strong> avec graphiques</li>
                                    </ul>

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> Les rapports sont mis à jour en <strong>temps réel</strong>. Vous voyez toujours les dernières données.
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- FAQ -->
                        <section id="faq" class="mb-5">
                            <div class="doc-card card">
                                <div class="card-header">
                                    <h2 class="mb-0"><i class="fas fa-question-circle"></i> Questions Fréquentes</h2>
                                </div>
                                <div class="card-body">
                                    <div class="accordion" id="faqAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                                    Comment réinitialiser mon mot de passe ?
                                                </button>
                                            </h2>
                                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    Sur la page de connexion, cliquez sur <strong>"Mot de passe oublié ?"</strong>. Saisissez votre email et suivez les instructions reçues par email.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                                    Puis-je accéder au CRM depuis mon téléphone ?
                                                </button>
                                            </h2>
                                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    Oui ! Le CRM est <strong>responsive</strong> et s'adapte automatiquement à tous les écrans (smartphone, tablette, ordinateur). Utilisez simplement votre navigateur mobile.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                                    Comment importer mes contacts depuis Excel ?
                                                </button>
                                            </h2>
                                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    <ol>
                                                        <li>Exportez votre fichier Excel en <strong>CSV</strong></li>
                                                        <li>Allez dans <strong>Contacts</strong> → <strong>"Importer"</strong></li>
                                                        <li>Chargez votre fichier CSV</li>
                                                        <li>Mappez les colonnes (nom, email, téléphone...)</li>
                                                        <li>Validez l'import</li>
                                                    </ol>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                                    Mes données sont-elles sécurisées ?
                                                </button>
                                            </h2>
                                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    <strong>Absolument !</strong> Votre CRM utilise :
                                                    <ul>
                                                        <li>✅ Chiffrement SSL (HTTPS)</li>
                                                        <li>✅ Chiffrement AES-256 pour les données sensibles</li>
                                                        <li>✅ Sauvegardes quotidiennes automatiques</li>
                                                        <li>✅ Conformité RGPD</li>
                                                        <li>✅ Authentification sécurisée</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                                    Comment contacter le support technique ?
                                                </button>
                                            </h2>
                                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    Plusieurs options :
                                                    <ul>
                                                        <li><i class="fas fa-comments text-success"></i> <a href="chat-assistant.php"><strong>Chat Assistant</strong></a> (réponse immédiate)</li>
                                                        <li><i class="fas fa-envelope text-primary"></i> Email : <strong>support@webitech.fr</strong></li>
                                                        <li><i class="fas fa-phone text-info"></i> Téléphone : <strong>+33 1 23 45 67 89</strong> (9h-18h)</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="accordion-item">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                                    Puis-je personnaliser le CRM selon mes besoins ?
                                                </button>
                                            </h2>
                                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                                <div class="accordion-body">
                                                    Oui ! Contactez votre <strong>administrateur</strong> pour :
                                                    <ul>
                                                        <li>Ajouter des champs personnalisés</li>
                                                        <li>Créer des rapports sur mesure</li>
                                                        <li>Configurer des automations spécifiques</li>
                                                        <li>Intégrer d'autres outils (Zapier, API...)</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-success mt-4">
                                        <h5><i class="fas fa-headset"></i> Besoin d'Aide Supplémentaire ?</h5>
                                        <p class="mb-3">Notre assistant virtuel est disponible 24/7 pour répondre à vos questions !</p>
                                        <a href="chat-assistant.php" class="btn btn-success">
                                            <i class="fas fa-comments"></i> Démarrer une Conversation
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Ressources Complémentaires -->
                        <section class="mb-5">
                            <div class="card">
                                <div class="card-header bg-dark text-white">
                                    <h3 class="mb-0"><i class="fas fa-book-open"></i> Ressources Complémentaires</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="text-center p-3">
                                                <i class="fas fa-book fa-3x text-primary mb-3"></i>
                                                <h5>Documentation Avancée</h5>
                                                <p>Guide technique complet pour utilisateurs experts</p>
                                                <a href="documentation-commerciaux.php" class="btn btn-outline-primary">
                                                    <i class="fas fa-arrow-right"></i> Accéder
                                                </a>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center p-3">
                                                <i class="fas fa-video fa-3x text-danger mb-3"></i>
                                                <h5>Vidéos Tutoriels</h5>
                                                <p>Bibliothèque de tutoriels vidéo pas-à-pas</p>
                                                <button class="btn btn-outline-danger" disabled>
                                                    <i class="fas fa-clock"></i> Bientôt Disponible
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center p-3">
                                                <i class="fas fa-comments fa-3x text-success mb-3"></i>
                                                <h5>Chat Assistant IA</h5>
                                                <p>Assistance intelligente en temps réel</p>
                                                <a href="chat-assistant.php" class="btn btn-outline-success">
                                                    <i class="fas fa-robot"></i> Démarrer
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Footer -->
                        <div class="text-center mt-5 mb-5">
                            <hr>
                            <p class="text-muted">
                                <i class="fas fa-book"></i> Documentation Client CRM Webitech - Version 2.0<br>
                                <small>Dernière mise à jour : 16 février 2026 • <a href="mailto:support@webitech.fr">support@webitech.fr</a></small>
                            </p>
                            <a href="index.php" class="btn btn-primary mt-3">
                                <i class="fas fa-home"></i> Retour au Dashboard
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
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Active nav highlight
    window.addEventListener('scroll', () => {
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-pills .nav-link');
        
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (scrollY >= sectionTop - 150) {
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
    const navLinks = document.querySelectorAll('.nav-pills .nav-link');
    
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
