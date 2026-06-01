<?php
/**
 * Webexa - Unified Authentication & Onboarding Page
 * Consolidated login, register, and setup-wizard into single elegant interface
 * Design: Monday.com-inspired (professional, white, clean)
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/crm/config/database.php';
require_once __DIR__ . '/crm/includes/auth.php';

// Check if user is already authenticated and onboarded
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

// Get mode from request or session
$mode = $_GET['mode'] ?? $_SESSION['auth_mode'] ?? 'welcome';
$_SESSION['auth_mode'] = $mode;

// Prepare OAuth redirect URIs
$google_oauth_url = '#'; // Será manejado por JavaScript
$microsoft_oauth_url = '#'; // Será manejado por JavaScript

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webexa - CRM & ERP Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
                'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            background: #f7f8fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Left Sidebar - Feature Showcase */
        .sidebar {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 60px;
            letter-spacing: -0.5px;
        }

        .features {
            flex: 1;
        }

        .feature {
            margin-bottom: 40px;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .feature h3 {
            font-size: 18px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .feature p {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.5;
        }

        .footer-text {
            font-size: 13px;
            opacity: 0.7;
        }

        /* Right Panel - Auth Section */
        .auth-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: white;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .auth-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .auth-header p {
            color: #666;
            font-size: 14px;
        }

        /* Welcome Screen */
        .screen.welcome {
            text-align: center;
        }

        .welcome-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .welcome-buttons button {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-signin {
            background: #667eea;
            color: white;
        }

        .btn-signin:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2);
        }

        .btn-signup {
            background: #f0f1f5;
            color: #333;
            border: 2px solid #e0e1e6;
        }

        .btn-signup:hover {
            background: #e8e9f0;
            transform: translateY(-2px);
        }

        .welcome-text {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: #999;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e0e1e6;
        }

        .divider span {
            padding: 0 12px;
            font-size: 13px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e0e1e6;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* OAuth Buttons */
        .oauth-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .oauth-btn {
            flex: 1;
            padding: 12px;
            border: 1px solid #e0e1e6;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .oauth-btn:hover {
            background: #f7f8fa;
            border-color: #667eea;
        }

        /* Wizard Steps */
        .wizard-steps {
            display: flex;
            gap: 8px;
            margin-bottom: 30px;
            justify-content: center;
        }

        .step {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #e0e1e6;
            transition: all 0.3s ease;
        }

        .step.active {
            background: #667eea;
            width: 30px;
        }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2);
        }

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        /* Navigation */
        .nav-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .nav-buttons button {
            flex: 1;
            padding: 12px;
            border: 1px solid #e0e1e6;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #667eea;
            transition: all 0.3s ease;
        }

        .nav-buttons button:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        /* Module Selection */
        .modules-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .module-item {
            padding: 15px;
            border: 2px solid #e0e1e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
        }

        .module-item input[type="checkbox"] {
            position: absolute;
            opacity: 0;
        }

        .module-item input[type="checkbox"]:checked + label {
            color: #667eea;
        }

        .module-item.selected {
            background: rgba(102, 126, 234, 0.05);
            border-color: #667eea;
        }

        .module-item label {
            cursor: pointer;
            display: block;
            font-weight: 600;
        }

        /* Error/Success Messages */
        .message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .message.success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .message.info {
            background: #eef;
            color: #33c;
            border: 1px solid #ccf;
        }

        /* Loading State */
        .loading {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Hidden screens */
        .screen {
            display: none;
        }

        .screen.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                flex: 0.8;
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                padding: 40px 30px;
                min-height: auto;
            }

            .feature {
                margin-bottom: 30px;
            }

            .auth-panel {
                padding: 30px 20px;
                min-height: auto;
            }

            .modules-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Help text */
        .form-help {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .back-link {
            color: #667eea;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-block;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Left Sidebar -->
        <div class="sidebar">
            <div>
                <div class="logo">🚀 Webexa</div>
                <div class="features">
                    <div class="feature">
                        <div class="feature-icon">📊</div>
                        <h3>CRM Puissant</h3>
                        <p>Gérez vos contacts, leads et opportunités de vente en toute facilité</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">⚙️</div>
                        <h3>ERP Complet</h3>
                        <p>Automatisez vos processus métier et optimisez vos opérations</p>
                    </div>
                    <div class="feature">
                        <div class="feature-icon">🤖</div>
                        <h3>IA Avancée</h3>
                        <p>Analysez vos données et obtenez des insights intelligents</p>
                    </div>
                </div>
            </div>
            <div class="footer-text">
                © 2024 Webexa. Tous droits réservés.
            </div>
        </div>

        <!-- Right Auth Panel -->
        <div class="auth-panel">
            <div class="auth-container">
                <!-- WELCOME SCREEN -->
                <div class="screen welcome active">
                    <div class="auth-header">
                        <h1>Bienvenue</h1>
                        <p>Commencez à gérer votre entreprise avec Webexa</p>
                    </div>

                    <div class="welcome-text">
                        <p>Webexa est une plateforme complète de gestion d'entreprise combinant CRM, ERP et intelligence artificielle.</p>
                    </div>

                    <div class="welcome-buttons">
                        <button class="btn-signin" onclick="switchScreen('login')">Se connecter</button>
                        <button class="btn-signup" onclick="switchScreen('signup-step1')">S'inscrire</button>
                    </div>

                    <div class="divider">
                        <span>ou</span>
                    </div>

                    <div class="oauth-buttons">
                        <button class="oauth-btn" onclick="loginWithGoogle()">
                            <span>🔵</span> Google
                        </button>
                        <button class="oauth-btn" onclick="loginWithMicrosoft()">
                            <span>🟦</span> Microsoft
                        </button>
                    </div>
                </div>

                <!-- LOGIN SCREEN -->
                <div class="screen login">
                    <div onclick="switchScreen('welcome')" class="back-link">← Retour</div>
                    <div class="auth-header">
                        <h1>Se connecter</h1>
                        <p>Accédez à votre compte Webexa</p>
                    </div>

                    <div id="login-message"></div>

                    <form id="login-form" onsubmit="handleLogin(event)">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label>Mot de passe</label>
                            <input type="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Connexion</button>
                    </form>

                    <div style="text-align: center; margin-top: 20px;">
                        <span style="color: #999; font-size: 14px;">Pas encore de compte? </span>
                        <a href="#" onclick="switchScreen('signup-step1'); return false;" style="color: #667eea; font-weight: 600; text-decoration: none;">S'inscrire</a>
                    </div>
                </div>

                <!-- SIGNUP STEP 1: Account -->
                <div class="screen signup-step1">
                    <div class="wizard-steps">
                        <div class="step active"></div>
                        <div class="step"></div>
                        <div class="step"></div>
                    </div>

                    <div class="auth-header">
                        <h1>Créer un compte</h1>
                        <p>Étape 1 sur 3 - Vos informations personnelles</p>
                    </div>

                    <div id="signup-message"></div>

                    <form id="signup-form-step1" onsubmit="handleSignupStep1(event)">
                        <div class="form-group">
                            <label>Prénom</label>
                            <input type="text" name="first_name" id="first_name" required>
                        </div>

                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" name="last_name" id="last_name" required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="signup_email" required>
                        </div>

                        <div class="form-group">
                            <label>Mot de passe</label>
                            <input type="password" name="password" id="signup_password" required>
                            <div class="form-help">Au moins 8 caractères</div>
                        </div>

                        <div class="form-group">
                            <label>Confirmer le mot de passe</label>
                            <input type="password" name="password_confirm" id="password_confirm" required>
                        </div>

                        <div class="form-group">
                            <label>Poste</label>
                            <input type="text" name="position" id="position">
                        </div>

                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="tel" name="phone" id="phone">
                        </div>

                        <div class="nav-buttons">
                            <button type="button" class="btn btn-secondary" onclick="switchScreen('welcome')">Annuler</button>
                            <button type="submit" class="btn btn-primary">Suivant</button>
                        </div>
                    </form>
                </div>

                <!-- SIGNUP STEP 2: Company -->
                <div class="screen signup-step2">
                    <div class="wizard-steps">
                        <div class="step"></div>
                        <div class="step active"></div>
                        <div class="step"></div>
                    </div>

                    <div class="auth-header">
                        <h1>Votre entreprise</h1>
                        <p>Étape 2 sur 3 - Informations de l'entreprise</p>
                    </div>

                    <form id="signup-form-step2" onsubmit="handleSignupStep2(event)">
                        <div class="form-group">
                            <label>Nom de l'entreprise</label>
                            <input type="text" name="company_name" id="company_name" required>
                        </div>

                        <div class="form-group">
                            <label>SIRET (optionnel)</label>
                            <input type="text" name="siret" id="siret">
                        </div>

                        <div class="form-group">
                            <label>Numéro TVA (optionnel)</label>
                            <input type="text" name="vat_number" id="vat_number">
                        </div>

                        <div class="form-group">
                            <label>Site web (optionnel)</label>
                            <input type="url" name="website" id="website">
                        </div>

                        <div class="form-group">
                            <label>Secteur d'activité</label>
                            <select name="industry" id="industry" required>
                                <option value="">-- Sélectionner --</option>
                                <option value="Technology">Technologie</option>
                                <option value="Finance">Finance</option>
                                <option value="Healthcare">Santé</option>
                                <option value="Retail">Commerce</option>
                                <option value="Manufacturing">Fabrication</option>
                                <option value="Services">Services</option>
                                <option value="Other">Autre</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Nombre d'employés</label>
                            <select name="employee_count" id="employee_count">
                                <option value="">-- Sélectionner --</option>
                                <option value="1-10">1-10</option>
                                <option value="11-50">11-50</option>
                                <option value="51-200">51-200</option>
                                <option value="201-1000">201-1000</option>
                                <option value="1000+">1000+</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Chiffre d'affaires annuel (optionnel)</label>
                            <input type="text" name="annual_revenue" id="annual_revenue">
                        </div>

                        <div class="nav-buttons">
                            <button type="button" class="btn btn-secondary" onclick="switchScreen('signup-step1')">Précédent</button>
                            <button type="submit" class="btn btn-primary">Suivant</button>
                        </div>
                    </form>
                </div>

                <!-- SIGNUP STEP 3: Modules -->
                <div class="screen signup-step3">
                    <div class="wizard-steps">
                        <div class="step"></div>
                        <div class="step"></div>
                        <div class="step active"></div>
                    </div>

                    <div class="auth-header">
                        <h1>Sélectionnez vos modules</h1>
                        <p>Étape 3 sur 3 - Choisissez les fonctionnalités dont vous avez besoin</p>
                    </div>

                    <form id="signup-form-step3" onsubmit="handleSignupStep3(event)">
                        <div class="modules-grid">
                            <div class="module-item selected" onclick="toggleModule(this, 'crm')">
                                <input type="checkbox" name="modules" value="crm" checked id="module-crm">
                                <label for="module-crm">📊 CRM</label>
                            </div>
                            <div class="module-item" onclick="toggleModule(this, 'erp')">
                                <input type="checkbox" name="modules" value="erp" id="module-erp">
                                <label for="module-erp">⚙️ ERP</label>
                            </div>
                            <div class="module-item" onclick="toggleModule(this, 'projects')">
                                <input type="checkbox" name="modules" value="projects" id="module-projects">
                                <label for="module-projects">📈 Projets</label>
                            </div>
                            <div class="module-item" onclick="toggleModule(this, 'marketing')">
                                <input type="checkbox" name="modules" value="marketing" id="module-marketing">
                                <label for="module-marketing">📣 Marketing</label>
                            </div>
                            <div class="module-item" onclick="toggleModule(this, 'support')">
                                <input type="checkbox" name="modules" value="support" id="module-support">
                                <label for="module-support">💬 Support</label>
                            </div>
                            <div class="module-item" onclick="toggleModule(this, 'analytics')">
                                <input type="checkbox" name="modules" value="analytics" id="module-analytics">
                                <label for="module-analytics">📉 Analytics</label>
                            </div>
                        </div>

                        <div id="signup-step3-message"></div>

                        <div class="nav-buttons">
                            <button type="button" class="btn btn-secondary" onclick="switchScreen('signup-step2')">Précédent</button>
                            <button type="submit" class="btn btn-primary" id="btn-finish">Terminer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Screen navigation
        function switchScreen(screenName) {
            document.querySelectorAll('.screen').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelector(`.${screenName}`).classList.add('active');
        }

        // Module toggle
        function toggleModule(element, moduleName) {
            element.classList.toggle('selected');
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
        }

        // Handle login
        async function handleLogin(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('login-form'));
            
            try {
                const response = await fetch('crm/api/auth/login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'crm/index.php';
                } else {
                    showMessage('login-message', data.message || 'Erreur de connexion', 'error');
                }
            } catch (error) {
                showMessage('login-message', 'Erreur: ' + error.message, 'error');
            }
        }

        // Handle signup step 1
        async function handleSignupStep1(e) {
            e.preventDefault();
            
            const password = document.getElementById('signup_password').value;
            const confirmPassword = document.getElementById('password_confirm').value;
            
            if (password !== confirmPassword) {
                showMessage('signup-message', 'Les mots de passe ne correspondent pas', 'error');
                return;
            }
            
            if (password.length < 8) {
                showMessage('signup-message', 'Le mot de passe doit contenir au moins 8 caractères', 'error');
                return;
            }
            
            const formData = new FormData(document.getElementById('signup-form-step1'));
            
            try {
                const response = await fetch('crm/api/auth/register.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Store user data for next step
                    sessionStorage.setItem('signup_data', JSON.stringify({
                        first_name: formData.get('first_name'),
                        last_name: formData.get('last_name'),
                        email: formData.get('email'),
                        phone: formData.get('phone'),
                        position: formData.get('position')
                    }));
                    
                    switchScreen('signup-step2');
                } else {
                    showMessage('signup-message', data.message || 'Erreur lors de l\'inscription', 'error');
                }
            } catch (error) {
                showMessage('signup-message', 'Erreur: ' + error.message, 'error');
            }
        }

        // Handle signup step 2
        function handleSignupStep2(e) {
            e.preventDefault();
            
            const companyData = {
                company_name: document.getElementById('company_name').value,
                siret: document.getElementById('siret').value,
                vat_number: document.getElementById('vat_number').value,
                website: document.getElementById('website').value,
                industry: document.getElementById('industry').value,
                employee_count: document.getElementById('employee_count').value,
                annual_revenue: document.getElementById('annual_revenue').value
            };
            
            // Store company data
            sessionStorage.setItem('company_data', JSON.stringify(companyData));
            
            switchScreen('signup-step3');
        }

        // Handle signup step 3 - Final
        async function handleSignupStep3(e) {
            e.preventDefault();
            
            const signupData = JSON.parse(sessionStorage.getItem('signup_data') || '{}');
            const companyData = JSON.parse(sessionStorage.getItem('company_data') || '{}');
            
            const modules = Array.from(document.querySelectorAll('input[name="modules"]:checked'))
                .map(el => el.value);
            
            if (!modules.includes('crm')) {
                modules.push('crm'); // CRM always required
            }
            
            const btn = document.getElementById('btn-finish');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span>';
            
            try {
                // Call complete setup API
                const response = await fetch('crm/api/auth/complete-setup.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        profile: signupData,
                        company: companyData,
                        modules: modules
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('signup-step3-message', 'Inscription réussie! Redirection...', 'success');
                    setTimeout(() => {
                        window.location.href = 'crm/index.php';
                    }, 1500);
                } else {
                    showMessage('signup-step3-message', data.message || 'Erreur lors de la configuration', 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Terminer';
                }
            } catch (error) {
                showMessage('signup-step3-message', 'Erreur: ' + error.message, 'error');
                btn.disabled = false;
                btn.innerHTML = 'Terminer';
            }
        }

        // Show message
        function showMessage(elementId, message, type = 'info') {
            const element = document.getElementById(elementId);
            element.innerHTML = `<div class="message ${type}">${message}</div>`;
        }

        // OAuth handlers
        function loginWithGoogle() {
            // Redirect to Google OAuth endpoint in your API
            window.location.href = 'crm/api/auth/oauth-google.php';
        }

        function loginWithMicrosoft() {
            // Redirect to Microsoft OAuth endpoint in your API
            window.location.href = 'crm/api/auth/oauth-microsoft.php';
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // You can add any initialization code here
        });
    </script>
</body>
</html>
