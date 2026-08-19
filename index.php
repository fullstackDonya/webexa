<?php
/**
 * Webexa - Unified Authentication & Onboarding Page
 * Design: Monday.com-inspired professional SaaS interface
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/crm/config/database.php';
require_once __DIR__ . '/crm/includes/auth.php';

if (isAuthenticated()) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT onboarding_completed FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && $user['onboarding_completed']) {
        header('Location: crm/index.php');
        exit;
    }
}

$mode = $_GET['mode'] ?? $_SESSION['auth_mode'] ?? 'welcome';
$_SESSION['auth_mode'] = $mode;

// Lire le dernier compte Google (cookie non-sensible)
$lastGoogleAccount = null;
if (!empty($_COOKIE['last_google_account'])) {
    $decoded = json_decode($_COOKIE['last_google_account'], true);
    if (!empty($decoded['email']) && !empty($decoded['name'])) {
        $lastGoogleAccount = $decoded;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webexa — CRM & ERP Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
<div class="page-wrapper">

    <!-- ══ LEFT HERO PANEL ══ -->
    <div class="hero-panel">
        <div class="brand">
            <div class="brand-icon">🚀</div>
            <span class="brand-name">Webexa</span>
        </div>

        <div class="hero-content">
            <div class="hero-tag">
                <i class="fas fa-bolt"></i> Plateforme SaaS N°1 en France
            </div>
            <h1 class="hero-title">
                Gérez votre<br>entreprise avec<br><span>l'intelligence</span>
            </h1>
            <p class="hero-subtitle">
                CRM, ERP et IA réunis dans une seule plateforme. Suivez vos leads, automatisez vos processus et prenez de meilleures décisions.
            </p>

            <div class="feature-list">
                <div class="feature-item">
                    <div class="feature-icon purple">📊</div>
                    <div class="feature-text">
                        <h4>CRM Intelligent</h4>
                        <p>Pipeline visuel, scoring IA, suivi des opportunités en temps réel</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon pink">⚙️</div>
                    <div class="feature-text">
                        <h4>ERP Intégré</h4>
                        <p>Facturation, inventaire, RH — tout centralisé en un seul endroit</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon teal">🤖</div>
                    <div class="feature-text">
                        <h4>IA & Automatisations</h4>
                        <p>Workflows intelligents et insights prédictifs pour votre croissance</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-stats">
            <div class="stat">
                <div class="stat-value">+</div>
                <div class="stat-label">Entreprises actives</div>
            </div>
            <div class="stat">
                <div class="stat-value">98%</div>
                <div class="stat-label">Satisfaction client</div>
            </div>
            <div class="stat">
                <div class="stat-value">3x</div>
                <div class="stat-label">Productivité gagnée</div>
            </div>
        </div>
    </div>

    <!-- ══ RIGHT AUTH PANEL ══ -->
    <div class="auth-panel">
        <div class="auth-box">

            <!-- ══ WELCOME SCREEN ══ -->
            <div class="screen welcome active">
                <div class="screen-header">
                    <div class="eyebrow">Bienvenue</div>
                    <h1>Commencer avec Webexa</h1>
                    <p>Rejoignez des milliers d'entreprises qui font confiance à Webexa</p>
                </div>

                <div class="action-cards">
                    <button class="action-card primary-card" onclick="switchScreen('login')">
                        <div class="action-card-icon">🔑</div>
                        <div class="action-card-text">
                            <div class="action-card-title">Se connecter</div>
                            <div class="action-card-subtitle">Accéder à votre espace Webexa</div>
                        </div>
                        <div class="action-card-arrow"><i class="fas fa-arrow-right"></i></div>
                    </button>
                    <button class="action-card" onclick="switchScreen('signup-step1')">
                        <div class="action-card-icon">✨</div>
                        <div class="action-card-text">
                            <div class="action-card-title" style="color:var(--dark)">Créer un compte</div>
                            <div class="action-card-subtitle">Essai gratuit — sans carte bancaire</div>
                        </div>
                        <div class="action-card-arrow" style="color:var(--text-secondary)"><i class="fas fa-arrow-right"></i></div>
                    </button>
                </div>

                <div class="divider"><span>ou continuer avec</span></div>

                <div class="oauth-row">
                    <button class="oauth-btn" onclick="loginWithGoogle()">
                        <i class="fab fa-google"></i> Google
                    </button>
                    <button class="oauth-btn" onclick="loginWithMicrosoft()">
                        <i class="fab fa-microsoft"></i> Microsoft
                    </button>
                </div>

                <div class="auth-footer">
                    En continuant, vous acceptez nos <a href="#">Conditions d'utilisation</a> et notre <a href="#">Politique de confidentialité</a>
                </div>
            </div>

            <!-- ══ LOGIN SCREEN ══ -->
            <div class="screen login">
                <button class="back-btn" onclick="switchScreen('welcome')">
                    <i class="fas fa-arrow-left"></i> Retour
                </button>
                <div class="screen-header">
                    <div class="eyebrow">Connexion</div>
                    <h1>Content de vous revoir</h1>
                    <p>Connectez-vous à votre espace de travail</p>
                </div>

                <div class="oauth-row">
                    <button class="oauth-btn" onclick="loginWithGoogle()">
                        <i class="fab fa-google"></i> Google
                    </button>
                    <button class="oauth-btn" onclick="loginWithMicrosoft()">
                        <i class="fab fa-microsoft"></i> Microsoft
                    </button>
                </div>

                <div class="divider"><span>ou par email</span></div>

                <div id="login-message"></div>

                <form id="login-form" onsubmit="handleLogin(event)" novalidate>
                    <div class="field">
                        <label for="login_email">Adresse e-mail</label>
                        <input type="email" id="login_email" name="email" placeholder="vous@entreprise.com" autocomplete="email" required>
                    </div>

                    <div class="field">
                        <label for="login_password">Mot de passe</label>
                        <div class="input-with-icon">
                            <input type="password" id="login_password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                            <button type="button" class="toggle-pw" onclick="togglePassword('login_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-meta">
                        <label class="remember-me">
                            <input type="checkbox" name="remember"> Se souvenir de moi
                        </label>
                        <a href="#" class="forgot-link">Mot de passe oublié?</a>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btn-login">
                        <span>Se connecter</span>
                    </button>
                </form>

                <div class="auth-footer">
                    Pas encore de compte? <a href="#" onclick="switchScreen('signup-step1'); return false;">Créer un compte gratuit</a>
                </div>
            </div>

            <!-- ══ SIGNUP STEP 1 ══ -->
            <div class="screen signup-step1">
                <button class="back-btn" onclick="switchScreen('welcome')">
                    <i class="fas fa-arrow-left"></i> Retour
                </button>

                <div class="steps-bar">
                    <div class="step-item active">
                        <div class="step-circle">1</div>
                        <div class="step-label">Profil</div>
                    </div>
                    <div class="step-item">
                        <div class="step-circle">2</div>
                        <div class="step-label">Entreprise</div>
                    </div>
                    <div class="step-item">
                        <div class="step-circle">3</div>
                        <div class="step-label">Modules</div>
                    </div>
                </div>

                <div class="screen-header">
                    <div class="eyebrow">Étape 1 sur 3</div>
                    <h1>Vos informations</h1>
                    <p>Créez votre compte administrateur</p>
                </div>

                <div id="signup-message"></div>

                <form id="signup-form-step1" onsubmit="handleSignupStep1(event)" novalidate>
                    <div class="form-row">
                        <div class="field">
                            <label for="first_name">Prénom</label>
                            <input type="text" id="first_name" name="first_name" placeholder="Jean" required autocomplete="given-name">
                        </div>
                        <div class="field">
                            <label for="last_name">Nom</label>
                            <input type="text" id="last_name" name="last_name" placeholder="Dupont" required autocomplete="family-name">
                        </div>
                    </div>

                    <div class="field">
                        <label for="signup_email">Adresse e-mail professionnelle</label>
                        <input type="email" id="signup_email" name="email" placeholder="jean@entreprise.com" required autocomplete="email">
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="signup_password">Mot de passe</label>
                            <div class="input-with-icon">
                                <input type="password" id="signup_password" name="password" placeholder="••••••••" required oninput="checkPwStrength(this.value)">
                                <button type="button" class="toggle-pw" onclick="togglePassword('signup_password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="pw-strength" id="pw-strength-wrap" style="display:none">
                                <div class="pw-strength-bar"><div class="pw-strength-fill" id="pw-strength-fill"></div></div>
                                <div class="pw-strength-label" id="pw-strength-label"></div>
                            </div>
                        </div>
                        <div class="field">
                            <label for="password_confirm">Confirmer</label>
                            <div class="input-with-icon">
                                <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••••" required>
                                <button type="button" class="toggle-pw" onclick="togglePassword('password_confirm', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="position">Poste</label>
                            <input type="text" id="position" name="position" placeholder="Directeur commercial">
                        </div>
                        <div class="field">
                            <label for="phone">Téléphone</label>
                            <input type="tel" id="phone" name="phone" placeholder="+33 6 00 00 00 00">
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn btn-ghost" onclick="switchScreen('welcome')" style="max-width:120px">Annuler</button>
                        <button type="submit" class="btn btn-primary" id="btn-step1">Continuer <i class="fas fa-arrow-right"></i></button>
                    </div>
                </form>

                <div class="auth-footer">
                    Déjà un compte? <a href="#" onclick="switchScreen('login'); return false;">Se connecter</a>
                </div>
            </div>

            <!-- ══ SIGNUP STEP 2 ══ -->
            <div class="screen signup-step2">
                <button class="back-btn" onclick="switchScreen('signup-step1')">
                    <i class="fas fa-arrow-left"></i> Retour
                </button>

                <div class="steps-bar">
                    <div class="step-item done">
                        <div class="step-circle"><i class="fas fa-check" style="font-size:10px"></i></div>
                        <div class="step-label">Profil</div>
                    </div>
                    <div class="step-item active">
                        <div class="step-circle">2</div>
                        <div class="step-label">Entreprise</div>
                    </div>
                    <div class="step-item">
                        <div class="step-circle">3</div>
                        <div class="step-label">Modules</div>
                    </div>
                </div>

                <div class="screen-header">
                    <div class="eyebrow">Étape 2 sur 3</div>
                    <h1>Votre entreprise</h1>
                    <p>Configurez votre espace de travail</p>
                </div>

                <form id="signup-form-step2" onsubmit="handleSignupStep2(event)" novalidate>
                    <div class="field">
                        <label for="company_name">Nom de l'entreprise <span style="color:var(--danger)">*</span></label>
                        <input type="text" id="company_name" name="company_name" placeholder="Acme SAS" required>
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="siret">SIRET</label>
                            <input type="text" id="siret" name="siret" placeholder="12345678901234">
                        </div>
                        <div class="field">
                            <label for="vat_number">N° TVA</label>
                            <input type="text" id="vat_number" name="vat_number" placeholder="FR 12 345678901">
                        </div>
                    </div>

                    <div class="field">
                        <label for="website">Site web</label>
                        <input type="url" id="website" name="website" placeholder="https://www.entreprise.com">
                    </div>

                    <div class="form-row">
                        <div class="field">
                            <label for="industry">Secteur d'activité <span style="color:var(--danger)">*</span></label>
                            <select id="industry" name="industry" required>
                                <option value="">— Sélectionner —</option>
                                <option value="Technology">Technologie</option>
                                <option value="Finance">Finance & Banque</option>
                                <option value="Healthcare">Santé</option>
                                <option value="Retail">Commerce & Distribution</option>
                                <option value="Manufacturing">Industrie & Fabrication</option>
                                <option value="Services">Services aux entreprises</option>
                                <option value="Real Estate">Immobilier</option>
                                <option value="Education">Éducation</option>
                                <option value="Other">Autre</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="employee_count">Effectif</label>
                            <select id="employee_count" name="employee_count">
                                <option value="">— Sélectionner —</option>
                                <option value="1-10">1 – 10</option>
                                <option value="11-50">11 – 50</option>
                                <option value="51-200">51 – 200</option>
                                <option value="201-1000">201 – 1 000</option>
                                <option value="1000+">1 000+</option>
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label for="annual_revenue">Chiffre d'affaires annuel</label>
                        <input type="text" id="annual_revenue" name="annual_revenue" placeholder="ex : 500 000 €">
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn btn-ghost" onclick="switchScreen('signup-step1')" style="max-width:120px">Précédent</button>
                        <button type="submit" class="btn btn-primary">Continuer <i class="fas fa-arrow-right"></i></button>
                    </div>
                </form>
            </div>

            <!-- ══ SIGNUP STEP 3 ══ -->
            <div class="screen signup-step3">
                <button class="back-btn" onclick="switchScreen('signup-step2')">
                    <i class="fas fa-arrow-left"></i> Retour
                </button>

                <div class="steps-bar">
                    <div class="step-item done">
                        <div class="step-circle"><i class="fas fa-check" style="font-size:10px"></i></div>
                        <div class="step-label">Profil</div>
                    </div>
                    <div class="step-item done">
                        <div class="step-circle"><i class="fas fa-check" style="font-size:10px"></i></div>
                        <div class="step-label">Entreprise</div>
                    </div>
                    <div class="step-item active">
                        <div class="step-circle">3</div>
                        <div class="step-label">Modules</div>
                    </div>
                </div>

                <div class="screen-header">
                    <div class="eyebrow">Étape 3 sur 3</div>
                    <h1>Choisissez vos modules</h1>
                    <p>Activez les fonctionnalités adaptées à votre activité</p>
                </div>

                <div id="signup-step3-message"></div>

                <form id="signup-form-step3" onsubmit="handleSignupStep3(event)">
                    <div class="modules-grid">
                        <div class="module-card selected" onclick="toggleModule(this, 'crm')">
                            <input type="checkbox" name="modules" value="crm" checked id="module-crm">
                            <div class="check-badge"><i class="fas fa-check"></i></div>
                            <div class="module-emoji">📊</div>
                            <div class="module-name">CRM</div>
                            <div class="module-desc">Leads & opportunités</div>
                        </div>
                        <div class="module-card" onclick="toggleModule(this, 'erp')">
                            <input type="checkbox" name="modules" value="erp" id="module-erp">
                            <div class="check-badge"><i class="fas fa-check"></i></div>
                            <div class="module-emoji">⚙️</div>
                            <div class="module-name">ERP</div>
                            <div class="module-desc">Gestion opérationnelle</div>
                        </div>
                        <div class="module-card" onclick="toggleModule(this, 'projects')">
                            <input type="checkbox" name="modules" value="projects" id="module-projects">
                            <div class="check-badge"><i class="fas fa-check"></i></div>
                            <div class="module-emoji">📁</div>
                            <div class="module-name">Projets</div>
                            <div class="module-desc">Suivi & collaboration</div>
                        </div>
                        <div class="module-card" onclick="toggleModule(this, 'marketing')">
                            <input type="checkbox" name="modules" value="marketing" id="module-marketing">
                            <div class="check-badge"><i class="fas fa-check"></i></div>
                            <div class="module-emoji">📣</div>
                            <div class="module-name">Marketing</div>
                            <div class="module-desc">Campagnes & emails</div>
                        </div>
                        <div class="module-card" onclick="toggleModule(this, 'support')">
                            <input type="checkbox" name="modules" value="support" id="module-support">
                            <div class="check-badge"><i class="fas fa-check"></i></div>
                            <div class="module-emoji">💬</div>
                            <div class="module-name">Support</div>
                            <div class="module-desc">Tickets & help desk</div>
                        </div>
                        <div class="module-card" onclick="toggleModule(this, 'analytics')">
                            <input type="checkbox" name="modules" value="analytics" id="module-analytics">
                            <div class="check-badge"><i class="fas fa-check"></i></div>
                            <div class="module-emoji">📈</div>
                            <div class="module-name">Analytics</div>
                            <div class="module-desc">Rapports & tableaux</div>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="btn btn-ghost" onclick="switchScreen('signup-step2')" style="max-width:120px">Précédent</button>
                        <button type="submit" class="btn btn-primary" id="btn-finish">
                            Lancer Webexa <i class="fas fa-rocket"></i>
                        </button>
                    </div>
                </form>
            </div>

        </div><!-- /.auth-box -->
    </div><!-- /.auth-panel -->
</div><!-- /.page-wrapper -->

<!-- ══ LAST GOOGLE ACCOUNT POPUP ══ -->
<div id="google-account-popup" class="g-account-popup" role="dialog" aria-label="Connexion rapide" hidden>
    <div class="g-account-popup-inner">
        <button class="g-account-close" onclick="dismissGooglePopup()" aria-label="Fermer">
            <i class="fas fa-times"></i>
        </button>
        <div class="g-account-header">
            <img src="" id="g-account-avatar" class="g-account-avatar" alt="" width="36" height="36">
            <div class="g-account-info">
                <div class="g-account-name" id="g-account-name"></div>
                <div class="g-account-email" id="g-account-email"></div>
            </div>
        </div>
        <div class="g-account-actions">
            <button class="g-account-btn-primary" id="g-account-continue" onclick="continueWithLastGoogle()">
                <img src="https://www.google.com/favicon.ico" width="14" height="14" alt=""> Continuer avec ce compte
            </button>
            <button class="g-account-btn-ghost" onclick="loginWithGoogle()">
                Choisir un autre compte
            </button>
        </div>
        <div class="g-account-progress"><div class="g-account-progress-fill" id="g-account-progress-fill"></div></div>
    </div>
</div>

<script src="assets/js/auth.js"></script>

<?php if ($lastGoogleAccount): ?>
<script>
window._lastGoogleAccount = <?= json_encode([
    'name'    => $lastGoogleAccount['name'],
    'email'   => $lastGoogleAccount['email'],
    'picture' => $lastGoogleAccount['picture'] ?? ''
]) ?>;
</script>
<?php endif; ?>
</body>
</html>
