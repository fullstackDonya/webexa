<?php

session_start();

// Si l'utilisateur est déjà connecté, on le redirige vers l'accueil du dashboard
if (isset($_SESSION['user'])) {
    header('Location: creator/src/accueil.php');
    exit;
}

// Gestion des erreurs globales (doit rester après le session_start)
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur : ' . $e->getMessage()
    ]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => "Erreur PHP ($errno): $errstr in $errfile:$errline"
    ]);
    exit;
});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Webexa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Meta tags for PWA -->
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="description" content="Connexion sécurisée à votre compte Webexa pour accéder à votre espace créatif personnalisé">
</head>
<body>
    <!-- Theme Toggle -->
    <div class="theme-toggle-container">
        <i class="fas fa-sun theme-icon sun"></i>
        <input type="checkbox" id="theme-toggle" class="theme-toggle" />
        <i class="fas fa-moon theme-icon moon"></i>
    </div>
    
    <!-- Connection Status Indicator -->
    <div id="connection-status" class="connection-status" style="display: none;">
        🔴 Connexion perdue
    </div>
    
    <!-- Particules d'arrière-plan animées -->
    <div class="particles-background">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <!-- Background avec pattern -->
    <div class="background-pattern"></div>
    
    <!-- Navigation header -->
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <i class="fas fa-cube"></i>
                <span>Webexa</span>
            </a>
            <nav class="nav-links">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="register.php" class="nav-link">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="login-container">
            <!-- Indicateur de sécurité -->
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Connexion sécurisée SSL</span>
            </div>

            <!-- Logo et titre avec animation -->
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-cube"></i>
                    <div class="logo-glow"></div>
                </div>
                <h1>Bienvenue sur Webexa</h1>
                <p class="login-subtitle">
                    <span class="typewriter">Accédez à votre espace créatif personnalisé</span>
                </p>
                
                <!-- Statistiques de la plateforme -->
                <div class="platform-stats">
                    <div class="stat-item">
                        <i class="fas fa-users"></i>
                        <span class="stat-number" data-count="1250">0</span>
                        <span class="stat-label">Utilisateurs actifs</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-project-diagram"></i>
                        <span class="stat-number" data-count="3400">0</span>
                        <span class="stat-label">Projets créés</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-star"></i>
                        <span class="stat-number" data-count="98">0</span>
                        <span class="stat-label">% Satisfaction</span>
                    </div>
                </div>
            </div>

            <!-- Messages d'erreur/succès -->
            <div id="message-container"></div>

            <!-- Formulaire de connexion amélioré -->
            <form id="loginForm" action="forms/login_process.php" method="POST" class="login-form">
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="fas fa-sign-in-alt"></i>
                        Connectez-vous à votre compte
                    </h3>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Adresse email
                        </label>
                        <div class="input-container">
                            <input type="email" id="email" name="email" class="form-input" required 
                                   autocomplete="email" placeholder="votre@email.com">
                            <span class="input-focus"></span>
                            <div class="input-validation">
                                <i class="fas fa-check-circle valid-icon"></i>
                                <i class="fas fa-exclamation-circle invalid-icon"></i>
                            </div>
                        </div>
                        <div class="input-hint">Utilisez votre adresse email de connexion</div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Mot de passe
                        </label>
                        <div class="input-container">
                            <input type="password" id="password" name="password" class="form-input" required 
                                   autocomplete="current-password" placeholder="••••••••">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="password-icon"></i>
                            </button>
                            <span class="input-focus"></span>
                            <div class="password-strength">
                                <div class="strength-bar"></div>
                            </div>
                        </div>
                        <div class="input-hint">Minimum 8 caractères requis</div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember_me" id="remember_me">
                            <span class="checkmark">
                                <i class="fas fa-check"></i>
                            </span>
                            <span class="checkbox-text">
                                Se souvenir de moi sur cet appareil
                                <small>Connexion automatique pendant 30 jours</small>
                            </span>
                        </label>
                        <a href="forgot-password.php" class="forgot-link">
                            <i class="fas fa-key"></i>
                            Mot de passe oublié ?
                        </a>
                    </div>

                    <button type="submit" class="login-btn" id="loginBtn">
                        <span class="btn-content">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="btn-text">Se connecter</span>
                        </span>
                        <div class="btn-loader">
                            <div class="spinner"></div>
                            <span>Connexion en cours...</span>
                        </div>
                        <div class="btn-success">
                            <i class="fas fa-check"></i>
                            <span>Connecté !</span>
                        </div>
                    </button>
                </div>
            </form>

            <!-- Liens alternatifs améliorés -->
            <div class="login-footer">
                <div class="divider">
                    <span>Ou continuez avec</span>
                </div>

                <!-- Connexions alternatives avec animations -->
                <div class="social-login">
                    <button type="button" class="social-btn google-btn" onclick="handleSocialLogin('google')">
                        <div class="social-icon">
                            <i class="fab fa-google"></i>
                        </div>
                        <span>Google</span>
                        <div class="social-ripple"></div>
                    </button>
                    <button type="button" class="social-btn github-btn" onclick="handleSocialLogin('github')">
                        <div class="social-icon">
                            <i class="fab fa-github"></i>
                        </div>
                        <span>GitHub</span>
                        <div class="social-ripple"></div>
                    </button>
                    <button type="button" class="social-btn microsoft-btn" onclick="handleSocialLogin('microsoft')">
                        <div class="social-icon">
                            <i class="fab fa-microsoft"></i>
                        </div>
                        <span>Microsoft</span>
                        <div class="social-ripple"></div>
                    </button>
                </div>
                
                <!-- Lien d'inscription avec CTA -->
                <div class="signup-section">
                    <p class="signup-text">Nouveau sur Webexa ?</p>
                    <a href="register.php" class="signup-link">
                        <i class="fas fa-user-plus"></i>
                        Créer un compte gratuitement
                        <span class="link-arrow">→</span>
                    </a>
                    <div class="signup-benefits">
                        <div class="benefit-item">
                            <i class="fas fa-check"></i>
                            <span>Essai gratuit de 14 jours</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-check"></i>
                            <span>Aucune carte de crédit requise</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-check"></i>
                            <span>Support 24/7</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section témoignages en arrière-plan -->
        <div class="testimonials-section">
            <div class="testimonial active">
                <div class="testimonial-content">
                    <p>"Webexa a révolutionné notre façon de créer des projets web. Interface intuitive et résultats professionnels garantis !"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <strong>Sarah Martinez</strong>
                            <span>Designer UI/UX</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"Grâce à Webexa, j'ai pu lancer mon startup en quelques semaines. Les outils sont fantastiques !"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <strong>Thomas Dubois</strong>
                            <span>Entrepreneur</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="testimonial">
                <div class="testimonial-content">
                    <p>"Une plateforme complète qui répond à tous mes besoins de développement. Je la recommande vivement !"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <strong>Marie Leroy</strong>
                            <span>Développeuse Full-Stack</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer amélioré -->
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; 2025 Webexa. Tous droits réservés.</p>
            <div class="footer-links">
                <a href="#" class="footer-link">
                    <i class="fas fa-shield-alt"></i>
                    Confidentialité
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-file-contract"></i>
                    Conditions
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-headset"></i>
                    Support
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-globe"></i>
                    Status
                </a>
            </div>
        </div>
    </footer>
    <script src="assets/js/login.js"></script>
  
</body>
</html>