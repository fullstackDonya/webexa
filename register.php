<!-- filepath: /Applications/MAMP/htdocs/webexa/register.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Webexa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Meta tags -->
    <meta name="theme-color" content="#6366f1">
    <meta name="description" content="Créez votre compte Webexa et rejoignez notre communauté">
</head>
<body>
    <!-- Theme Toggle -->
    <div class="theme-toggle-container">
        <i class="fas fa-sun theme-icon sun"></i>
        <input type="checkbox" id="theme-toggle" class="theme-toggle" />
        <i class="fas fa-moon theme-icon moon"></i>
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
        <div class="login-container register_process">
            <!-- Indicateur de sécurité -->
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Inscription sécurisée SSL</span>
            </div>

            <!-- En-tête du formulaire -->
            <div class="form-header">
                <div class="welcome-section">
                    <h1 class="welcome-title">
                        <span class="typewriter">Rejoignez Webexa</span>
                    </h1>
                    <p class="welcome-subtitle">Créez votre compte et découvrez nos services innovants</p>
                </div>
            </div>

            <!-- Formulaire d'inscription -->
            <form id="registerForm" action="forms/register_process.php" method="POST" class="login-form">
                <!-- Fallback pour browsers sans JavaScript -->
                <noscript>
                    <div style="background: #ffeaa7; border: 1px solid #fdcb6e; color: #2d3436; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
                        JavaScript est désactivé. Le formulaire fonctionnera en mode simple.
                    </div>
                </noscript>
                
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" class="form-input" required minlength="3" maxlength="50">
                        <label for="username" class="input-label">Nom d'utilisateur</label>
                        <div class="input-border"></div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-input" required maxlength="100">
                        <label for="email" class="input-label">Adresse email</label>
                        <div class="input-border"></div>
                    </div>
                </div>

           

                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-input" required minlength="8">
                        <label for="password" class="input-label">Mot de passe</label>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                        <div class="input-border"></div>
                        <div class="strength-indicator">
                            <div class="strength-bar"></div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        <label for="confirm_password" class="input-label">Confirmer le mot de passe</label>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm_password-icon"></i>
                        </button>
                        <div class="input-border"></div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" id="phone" name="phone" class="form-input" maxlength="20" placeholder="Facultatif">
                        <label for="phone" class="input-label">Numéro de téléphone (facultatif)</label>
                        <div class="input-border"></div>
                    </div>
                </div>

                <!-- Options supplémentaires -->
                <div class="form-options">
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms" class="checkbox-label">
                            <span class="checkbox-mark">
                                <i class="fas fa-check"></i>
                            </span>
                            J'accepte les
                            <a href="conditions.php" class="link">conditions d'utilisation</a>
                            et la <a href="rgpd.php" class="link">politique de confidentialité</a>
                        </label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" id="newsletter" name="newsletter">
                        <label for="newsletter" class="checkbox-label">
                            <span class="checkbox-mark">
                                <i class="fas fa-check"></i>
                            </span>
                            Recevoir les actualités et offres par email
                        </label>
                    </div>
                </div>

                  <!-- Conteneur de messages -->
                <div id="message-container" class="message-container"></div>


                <!-- Bouton de soumission -->
                <button type="submit" id="registerBtn" class="login-btn">
                    <span class="btn-content">
                        <i class="fas fa-user-plus"></i>
                        Créer mon compte
                    </span>
                    <div class="btn-loader">
                        <div class="spinner"></div>
                        <span>Création en cours...</span>
                    </div>
                    <div class="btn-success">
                        <i class="fas fa-check"></i>
                        <span>Compte créé !</span>
                    </div>
                </button>
            </form>

            <!-- Liens de navigation -->
            <div class="form-footer">
                <p class="login-link">
                    Déjà inscrit ? 
                    <a href="login.php" class="link">
                        <i class="fas fa-sign-in-alt"></i>
                        Se connecter
                    </a>
                </p>
            </div>
        </div>

        <!-- Section des avantages -->
        <div class="benefits-section">
            <h3 class="benefits-title">Pourquoi rejoindre Webexa ?</h3>
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h4>Performance</h4>
                    <p>Solutions haute performance pour vos projets web</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Sécurité</h4>
                    <p>Protection avancée de vos données et applications</p>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h4>Support 24/7</h4>
                    <p>Assistance technique disponible à tout moment</p>
                </div>
            </div>
        </div>
    </main>

  
    <!-- JavaScript -->
    <script src="assets/js/register.js"></script>
</body>
</html>