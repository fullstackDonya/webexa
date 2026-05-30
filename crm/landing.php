<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title>Webitech CRM/ERP Intelligent - Solution Cloud Tout-en-Un pour PME</title>
    <meta name="description" content="Transformez votre entreprise avec notre CRM/ERP intelligent alimenté par l'IA. Gestion clients, ventes, marketing automation, comptabilité et analytics en une seule plateforme cloud.">
    <meta name="keywords" content="CRM, ERP, Intelligence Artificielle, Gestion Client, Marketing Automation, Cloud, PME, SaaS, Analytics">
    <meta name="author" content="Webitech">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://webitech.com/">
    <meta property="og:title" content="Webitech CRM/ERP Intelligent - Solution Cloud Tout-en-Un">
    <meta property="og:description" content="Transformez votre entreprise avec notre CRM/ERP intelligent alimenté par l'IA.">
    <meta property="og:image" content="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://webitech.com/">
    <meta property="twitter:title" content="Webitech CRM/ERP Intelligent - Solution Cloud Tout-en-Un">
    <meta property="twitter:description" content="Transformez votre entreprise avec notre CRM/ERP intelligent alimenté par l'IA.">
    <meta property="twitter:image" content="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Schema.org markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "Webitech CRM/ERP",
        "applicationCategory": "BusinessApplication",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "EUR"
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "1250"
        }
    }
    </script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #f093fb;
            --dark: #1a202c;
            --light: #f7fafc;
            --gray: #718096;
            --success: #48bb78;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Navigation */
        nav {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        nav.scrolled {
            box-shadow: 0 2px 30px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: var(--primary);
            padding: 0.75rem 2rem;
            border-radius: 50px;
            border: 2px solid var(--primary);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Hero Section */
        .hero {
            margin-top: 80px;
            min-height: 90vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,112C1248,107,1344,117,1392,122.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
        }
        
        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 900;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            animation: fadeInUp 0.8s ease;
        }
        
        .hero-content p {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease 0.2s backwards;
        }
        
        .hero-cta {
            display: flex;
            gap: 1rem;
            animation: fadeInUp 0.8s ease 0.4s backwards;
        }
        
        .hero-image {
            position: relative;
            animation: fadeInRight 0.8s ease;
        }
        
        .hero-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        /* Stats Section */
        .stats {
            background: white;
            padding: 4rem 2rem;
            margin-top: -50px;
            position: relative;
            z-index: 2;
        }
        
        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--gray);
            font-weight: 500;
        }
        
        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: var(--light);
        }
        
        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 4rem;
        }
        
        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .section-header p {
            font-size: 1.125rem;
            color: var(--gray);
        }
        
        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }
        
        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
        }
        
        .feature-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .feature-card p {
            color: var(--gray);
            line-height: 1.7;
        }
        
        /* Benefits Section */
        .benefits {
            padding: 6rem 2rem;
            background: white;
        }
        
        .benefits-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }
        
        .benefits-image {
            position: relative;
        }
        
        .benefits-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .benefits-content h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        
        .benefits-list {
            list-style: none;
        }
        
        .benefits-list li {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: var(--light);
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .benefits-list li:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            transform: translateX(10px);
        }
        
        .benefits-list i {
            color: var(--success);
            font-size: 1.5rem;
            margin-top: 0.25rem;
        }
        
        .benefit-text h4 {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .benefit-text p {
            color: var(--gray);
        }
        
        /* Testimonials */
        .testimonials {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .testimonials-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .testimonial-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .testimonial-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .testimonial-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .testimonial-info h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .testimonial-info p {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        .testimonial-rating {
            color: #ffd700;
            margin-bottom: 1rem;
        }
        
        .testimonial-text {
            line-height: 1.7;
            opacity: 0.9;
        }
        
        /* CTA Section */
        .cta {
            padding: 6rem 2rem;
            background: var(--light);
            text-align: center;
        }
        
        .cta-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .cta h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        
        .cta p {
            font-size: 1.25rem;
            color: var(--gray);
            margin-bottom: 2rem;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 4rem 2rem 2rem;
        }
        
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .footer-brand h3 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .footer-brand p {
            opacity: 0.7;
            line-height: 1.7;
        }
        
        .footer-links h4 {
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .footer-links ul {
            list-style: none;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: block;
            margin-bottom: 0.75rem;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
        }
        
        /* Animations */
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
        
        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 968px) {
            .hero-container,
            .benefits-container {
                grid-template-columns: 1fr;
            }
            
            .features-container,
            .testimonials-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .footer-container {
                grid-template-columns: 1fr;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav id="navbar">
        <div class="nav-container">
            <div class="logo">🚀 Webitech</div>
            <ul class="nav-links">
                <li><a href="#fonctionnalites">Fonctionnalités</a></li>
                <li><a href="#avantages">Avantages</a></li>
                <li><a href="#temoignages">Témoignages</a></li>
                <li><a href="#tarifs">Tarifs</a></li>
                <li><a href="login.php" class="btn-primary">Connexion</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Le CRM/ERP Intelligent qui Révolutionne Votre Business</h1>
                <p>Gérez vos clients, ventes, marketing et comptabilité avec l'Intelligence Artificielle. Une solution cloud complète pour les PME ambitieuses.</p>
                <div class="hero-cta">
                    <a href="setup-wizard.php" class="btn-primary">Démarrer Gratuitement</a>
                    <a href="#demo" class="btn-secondary">Voir la Démo</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&h=600&fit=crop&q=80" alt="Dashboard CRM Moderne" loading="eager">
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stat-item">
                <div class="stat-number">1,250+</div>
                <div class="stat-label">Entreprises Actives</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">98%</div>
                <div class="stat-label">Taux de Satisfaction</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">45%</div>
                <div class="stat-label">Gain de Productivité</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Support Client</div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="fonctionnalites">
        <div class="section-header">
            <h2>Une Suite Complète d'Outils Professionnels</h2>
            <p>Tout ce dont vous avez besoin pour gérer et développer votre entreprise, dans une seule plateforme intuitive</p>
        </div>
        <div class="features-container">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>CRM Intelligent</h3>
                <p>Gérez vos contacts, prospects et clients avec l'IA. Automatisez le suivi, prédisez les ventes et closez plus rapidement.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Analytics Avancés</h3>
                <p>Tableaux de bord en temps réel, insights prédictifs et rapports personnalisables pour des décisions data-driven.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3>Marketing Automation</h3>
                <p>Campagnes email intelligentes, segmentation automatique et workflows pour maximiser vos conversions.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <h3>Comptabilité Intégrée</h3>
                <p>Facturation, devis, paiements et rapports financiers. Simplifiez votre gestion comptable au quotidien.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <h3>Gestion de Projets</h3>
                <p>Planifiez, suivez et livrez vos projets à temps. Collaboration d'équipe et suivi de temps inclus.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-brain"></i>
                </div>
                <h3>IA & Automatisation</h3>
                <p>Assistant IA, prédictions de ventes, scoring de leads et automatisation intelligente des tâches répétitives.</p>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits" id="avantages">
        <div class="benefits-container">
            <div class="benefits-image">
                <img src="https://images.unsplash.com/photo-1551434678-e076c223a692?w=800&h=600&fit=crop&q=80" alt="Équipe travaillant ensemble" loading="lazy">
            </div>
            <div class="benefits-content">
                <h2>Pourquoi Choisir Webitech ?</h2>
                <ul class="benefits-list">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-text">
                            <h4>Déploiement Rapide</h4>
                            <p>Opérationnel en moins de 24h. Configuration guidée en 5 étapes simples.</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-text">
                            <h4>Interface Intuitive</h4>
                            <p>Design moderne inspiré de Monday.com. Aucune formation nécessaire.</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-text">
                            <h4>Synchronisation Automatique</h4>
                            <p>Les données clients se synchonisent automatiquement entre tous les modules.</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-text">
                            <h4>Sécurité Maximale</h4>
                            <p>Hébergement sécurisé, sauvegardes quotidiennes, conformité RGPD.</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <div class="benefit-text">
                            <h4>Support Premium</h4>
                            <p>Équipe dédiée disponible 24/7 par chat, email et téléphone.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials" id="temoignages">
        <div class="testimonials-container">
            <div class="section-header">
                <h2 style="color: white;">Ce Que Nos Clients Disent</h2>
                <p style="color: rgba(255,255,255,0.9)">Plus de 1,250 entreprises nous font confiance pour gérer leur croissance</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <img src="https://i.pravatar.cc/100?img=12" alt="Sophie Martin" class="testimonial-avatar">
                        <div class="testimonial-info">
                            <h4>Sophie Martin</h4>
                            <p>CEO, TechStart SAS</p>
                        </div>
                    </div>
                    <div class="testimonial-rating">
                        ⭐⭐⭐⭐⭐
                    </div>
                    <p class="testimonial-text">"Webitech a transformé notre façon de travailler. Le gain de productivité est impressionnant et l'IA nous aide vraiment à closer plus de deals."</p>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <img src="https://i.pravatar.cc/100?img=33" alt="Marc Dubois" class="testimonial-avatar">
                        <div class="testimonial-info">
                            <h4>Marc Dubois</h4>
                            <p>Directeur Commercial, SalesBoost</p>
                        </div>
                    </div>
                    <div class="testimonial-rating">
                        ⭐⭐⭐⭐⭐
                    </div>
                    <p class="testimonial-text">"L'intégration CRM/ERP est parfaite. Plus besoin de jongler entre plusieurs outils. Tout est centralisé et fluide."</p>
                </div>
                
                <div class="testimonial-card">
                    <div class="testimonial-header">
                        <img src="https://i.pravatar.cc/100?img=45" alt="Claire Legrand" class="testimonial-avatar">
                        <div class="testimonial-info">
                            <h4>Claire Legrand</h4>
                            <p>Fondatrice, Marketing Pro</p>
                        </div>
                    </div>
                    <div class="testimonial-rating">
                        ⭐⭐⭐⭐⭐
                    </div>
                    <p class="testimonial-text">"Le marketing automation nous fait gagner 10h par semaine. Et le support client est exceptionnel, toujours réactif!"</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta" id="tarifs">
        <div class="cta-container">
            <h2>Prêt à Transformer Votre Business ?</h2>
            <p>Essayez Webitech gratuitement pendant 14 jours. Aucune carte bancaire requise.</p>
            <div class="cta-buttons">
                <a href="setup-wizard.php" class="btn-primary" style="font-size: 1.125rem; padding: 1rem 2.5rem;">Démarrer Gratuitement</a>
                <a href="mailto:contact@webitech.com" class="btn-secondary" style="font-size: 1.125rem; padding: 1rem 2.5rem;">Contacter un Expert</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-brand">
                <h3>🚀 Webitech</h3>
                <p>La solution CRM/ERP intelligente qui accélère la croissance des PME. Automatisez, analysez et excellez.</p>
                <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                    <a href="#" style="color: white; font-size: 1.5rem;"><i class="fab fa-linkedin"></i></a>
                    <a href="#" style="color: white; font-size: 1.5rem;"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color: white; font-size: 1.5rem;"><i class="fab fa-facebook"></i></a>
                </div>
            </div>
            
            <div class="footer-links">
                <h4>Produit</h4>
                <ul>
                    <li><a href="#fonctionnalites">Fonctionnalités</a></li>
                    <li><a href="#tarifs">Tarifs</a></li>
                    <li><a href="#">Intégrations</a></li>
                    <li><a href="#">Sécurité</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Ressources</h4>
                <ul>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">API</a></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Webinaires</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Entreprise</h4>
                <ul>
                    <li><a href="#">À Propos</a></li>
                    <li><a href="#">Carrières</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Mentions Légales</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 Webitech. Tous droits réservés. | <a href="#" style="color: rgba(255,255,255,0.7);">Politique de Confidentialité</a> | <a href="#" style="color: rgba(255,255,255,0.7);">CGU</a></p>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe feature cards
        document.querySelectorAll('.feature-card, .testimonial-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
