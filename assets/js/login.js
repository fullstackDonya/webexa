// Variables globales
let isFormSubmitting = false;
let testimonialIndex = 0;
let currentTheme = localStorage.getItem('theme') || 'dark';
let connectionRetries = 0;
const MAX_RETRIES = 3;

// Initialisation de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializeAnimations();
    initializeFormValidation();
    initializeTestimonials();
    initializeAdvancedFeatures();
    checkUrlParameters();
    startCounterAnimations();
    setupKeyboardShortcuts();
});

// Gestion des thèmes
function initializeTheme() {
    document.documentElement.setAttribute('data-theme', currentTheme);
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.checked = currentTheme === 'light';
        themeToggle.addEventListener('change', toggleTheme);
    }
}

function toggleTheme() {
    currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', currentTheme);
    localStorage.setItem('theme', currentTheme);
    
    // Animation de transition douce
    document.body.style.transition = 'all 0.3s ease';
    setTimeout(() => {
        document.body.style.transition = '';
    }, 300);
}

// Fonctionnalités avancées
function initializeAdvancedFeatures() {
    // Détection de la connexion réseau
    setupNetworkDetection();
    
    // Gestion de la visibilité de la page
    setupVisibilityHandling();
    
    // Auto-focus intelligent
    setupSmartFocus();
    
    // Sauvegarde automatique des données du formulaire
    setupFormAutoSave();
}

// Détection réseau
function setupNetworkDetection() {
    function updateConnectionStatus() {
        const statusIndicator = document.getElementById('connection-status');
        if (navigator.onLine) {
            if (statusIndicator) statusIndicator.style.display = 'none';
        } else {
            if (statusIndicator) {
                statusIndicator.style.display = 'block';
                statusIndicator.textContent = '🔴 Connexion perdue';
            }
            showNotification('Connexion réseau perdue. Vérifiez votre connexion.', 'warning', 10000);
        }
    }
    
    window.addEventListener('online', updateConnectionStatus);
    window.addEventListener('offline', updateConnectionStatus);
    updateConnectionStatus();
}

// Gestion de la visibilité
function setupVisibilityHandling() {
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page cachée - pause des animations
            document.body.classList.add('page-hidden');
        } else {
            // Page visible - reprise des animations
            document.body.classList.remove('page-hidden');
        }
    });
}

// Auto-focus intelligent
function setupSmartFocus() {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    // Focus automatique sur le premier champ vide
    setTimeout(() => {
        if (!emailInput.value) {
            emailInput.focus();
        } else if (!passwordInput.value) {
            passwordInput.focus();
        }
    }, 500);
}

// Sauvegarde automatique du formulaire
function setupFormAutoSave() {
    const emailInput = document.getElementById('email');
    
    // Charger l'email sauvegardé (sauf le mot de passe pour la sécurité)
    const savedEmail = localStorage.getItem('loginEmail');
    if (savedEmail) {
        emailInput.value = savedEmail;
        emailInput.parentElement.classList.add('focused');
    }
    
    // Sauvegarder l'email à chaque modification
    emailInput.addEventListener('input', function() {
        if (this.value.includes('@')) {
            localStorage.setItem('loginEmail', this.value);
        }
    });
}

// Raccourcis clavier
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+Enter ou Cmd+Enter pour soumettre
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            const form = document.getElementById('loginForm');
            if (form) {
                e.preventDefault();
                form.dispatchEvent(new Event('submit'));
            }
        }
        
        // Échap pour fermer les notifications
        if (e.key === 'Escape') {
            document.querySelectorAll('.message').forEach(msg => msg.remove());
        }
        
        // Ctrl+D ou Cmd+D pour mode sombre
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            toggleTheme();
        }
    });
}

// Animations des particules et éléments
function initializeAnimations() {
    // Animation des particules
    const particles = document.querySelectorAll('.particle');
    particles.forEach((particle, index) => {
        const size = Math.random() * 4 + 2;
        const duration = Math.random() * 20 + 10;
        const delay = Math.random() * 5;
        
        particle.style.width = size + 'px';
        particle.style.height = size + 'px';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDuration = duration + 's';
        particle.style.animationDelay = delay + 's';
    });

    // Animation de machine à écrire
    typewriterEffect();
    
    // Animation d'entrée pour le formulaire
    setTimeout(() => {
        document.querySelector('.login-container').classList.add('animate-in');
    }, 200);
}

// Effet machine à écrire
function typewriterEffect() {
    const element = document.querySelector('.typewriter');
    const text = element.textContent;
    element.textContent = '';
    
    let i = 0;
    const timer = setInterval(() => {
        if (i < text.length) {
            element.textContent += text.charAt(i);
            i++;
        } else {
            clearInterval(timer);
        }
    }, 50);
}

// Animation des compteurs
function startCounterAnimations() {
    const counters = document.querySelectorAll('.stat-number');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
            }
        });
    });
    
    counters.forEach(counter => observer.observe(counter));
}

function animateCounter(element) {
    const target = parseInt(element.dataset.count);
    const duration = 2000;
    const step = target / (duration / 16);
    let current = 0;
    
    const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            element.textContent = target;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current);
        }
    }, 16);
}

// Gestion des témoignages
function initializeTestimonials() {
    const testimonials = document.querySelectorAll('.testimonial');
    
    setInterval(() => {
        testimonials[testimonialIndex].classList.remove('active');
        testimonialIndex = (testimonialIndex + 1) % testimonials.length;
        testimonials[testimonialIndex].classList.add('active');
    }, 5000);
}

// Validation du formulaire en temps réel
function initializeFormValidation() {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    emailInput.addEventListener('input', validateEmail);
    emailInput.addEventListener('blur', validateEmail);
    
    passwordInput.addEventListener('input', validatePassword);
    passwordInput.addEventListener('blur', validatePassword);
    
    // Effets de focus
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Vérifier si déjà rempli
        if (input.value.trim()) {
            input.parentElement.classList.add('focused');
        }
    });
}

// Validation email (fonction améliorée)
function validateEmail() {
    const email = document.getElementById('email');
    const container = email.parentElement;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email.value.trim() === '') {
        container.classList.remove('valid', 'invalid');
        return false;
    } else if (emailRegex.test(email.value)) {
        container.classList.add('valid');
        container.classList.remove('invalid');
        return true;
    } else {
        container.classList.add('invalid');
        container.classList.remove('valid');
        return false;
    }
}

// Fonction séparée pour validation finale
function validateEmailField() {
    const email = document.getElementById('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return email.value.trim() !== '' && emailRegex.test(email.value);
}

// Validation mot de passe (fonction améliorée)
function validatePassword() {
    const password = document.getElementById('password');
    const container = password.parentElement;
    const strengthBar = container.querySelector('.strength-bar');
    
    if (password.value.length === 0) {
        container.classList.remove('valid', 'invalid');
        strengthBar.style.width = '0%';
        strengthBar.className = 'strength-bar';
        return false;
    }
    
    let strength = 0;
    let strengthClass = 'weak';
    
    if (password.value.length >= 8) strength += 25;
    if (/[A-Z]/.test(password.value)) strength += 25;
    if (/[0-9]/.test(password.value)) strength += 25;
    if (/[^A-Za-z0-9]/.test(password.value)) strength += 25;
    
    // Déterminer la classe de force
    if (strength >= 75) strengthClass = 'strong';
    else if (strength >= 50) strengthClass = 'medium';
    
    strengthBar.style.width = strength + '%';
    strengthBar.className = `strength-bar ${strengthClass}`;
    
    const isValid = password.value.length >= 8;
    
    if (isValid) {
        container.classList.add('valid');
        container.classList.remove('invalid');
    } else {
        container.classList.add('invalid');
        container.classList.remove('valid');
    }
    
    return isValid;
}

// Fonction séparée pour validation finale
function validatePasswordField() {
    const password = document.getElementById('password');
    return password.value.length >= 8;
}

// Toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('password-icon');
    const toggleBtn = passwordIcon.parentElement;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.className = 'fas fa-eye-slash';
        toggleBtn.classList.add('active');
    } else {
        passwordInput.type = 'password';
        passwordIcon.className = 'fas fa-eye';
        toggleBtn.classList.remove('active');
    }
}

function handleSocialLogin(provider) {
    if (provider === 'google') {
        window.location.href = 'login_google.php';
        return;
    }
    // Pour les autres providers, tu peux garder l'effet d'attente ou afficher un message
    const button = event.target.closest('.social-btn');
    button.classList.add('loading');
    // Effet ripple
    const ripple = button.querySelector('.social-ripple');
    ripple.style.animation = 'none';
    ripple.offsetHeight; // Force reflow
    ripple.style.animation = 'ripple 0.6s linear';
    setTimeout(() => {
        button.classList.remove('loading');
        showNotification(`Connexion ${provider} bientôt disponible`, 'info');
    }, 1500);
}

// Soumission du formulaire avec retry automatique et gestion d'erreurs avancée
document.getElementById('loginForm').addEventListener('submit', function(e) {
    // Validation finale avant soumission classique
    const emailValid = validateEmailField();
    const passwordValid = validatePasswordField();
    if (!emailValid || !passwordValid) {
        showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
        e.preventDefault(); // bloque la soumission si invalide
    }
    // Sinon, POST classique vers login_process.php (défini dans l'attribut action du <form>)
});

// Fonction de soumission avec retry automatique
async function submitLoginForm(form, retryCount = 0) {
    if (isFormSubmitting && retryCount === 0) {
        return;
    }
    
    isFormSubmitting = true;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('loginBtn');
    const btnContent = submitBtn.querySelector('.btn-content');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    const btnSuccess = submitBtn.querySelector('.btn-success');
    
    try {
        // Animation de chargement avec retry info
        submitBtn.classList.add('loading');
        btnContent.style.display = 'none';
        btnLoader.style.display = 'flex';
        submitBtn.disabled = true;
        
        if (retryCount > 0) {
            showNotification(`Nouvelle tentative... (${retryCount}/${MAX_RETRIES})`, 'info', 2000);
        }
        
        // Ajouter des headers personnalisés
        const response = await fetch('forms/login_process.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Retry-Count': retryCount.toString()
            },
            // Timeout de 10 secondes
            signal: AbortSignal.timeout(10000)
        });
        
        // Vérifier le statut de la réponse
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        connectionRetries = 0; // Reset du compteur en cas de succès
        
        if (result.success) {
            // Succès - Animation de succès
            submitBtn.classList.remove('loading');
            submitBtn.classList.add('success');
            btnLoader.style.display = 'none';
            btnSuccess.style.display = 'flex';
            
            showNotification(result.message || 'Connexion réussie !', 'success');
            
            // Nettoyer les données sauvegardées
            localStorage.removeItem('loginEmail');
            
            // Analytics de connexion réussie
            logConnectionEvent('login_success', {
                email: formData.get('email'),
                retryCount: retryCount,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            });
            
            // Redirection après un court délai
            setTimeout(() => {
                window.location.href = result.redirect || 'account.php';
            }, 1500);
            
        } else {
            // Erreur côté serveur
            resetLoginButton(submitBtn, btnContent, btnLoader);
            
            showNotification(result.message || 'Erreur de connexion', 'error');
            
            // Animation de secousse du formulaire
            form.classList.add('shake');
            setTimeout(() => form.classList.remove('shake'), 600);
            
            // Analytics d'échec de connexion
            logConnectionEvent('login_failed', {
                email: formData.get('email'),
                error: result.message,
                retryCount: retryCount
            });
            
            isFormSubmitting = false;
        }
        
    } catch (error) {
        console.error('Erreur de connexion:', error);
        
        // Gestion intelligente des erreurs avec retry automatique
        if (retryCount < MAX_RETRIES && shouldRetry(error)) {
            connectionRetries++;
            
            // Attendre avant de réessayer (exponential backoff)
            const delay = Math.min(1000 * Math.pow(2, retryCount), 5000);
            
            setTimeout(() => {
                submitLoginForm(form, retryCount + 1);
            }, delay);
            
        } else {
            // Échec définitif
            resetLoginButton(submitBtn, btnContent, btnLoader);
            
            const errorMessage = getErrorMessage(error);
            showNotification(errorMessage, 'error');
            
            // Proposer des solutions
            if (error.name === 'AbortError') {
                showRetryOption(form);
            }
            
            // Analytics d'erreur réseau
            logConnectionEvent('network_error', {
                error: error.message,
                retryCount: retryCount,
                finalAttempt: true
            });
            
            connectionRetries = 0;
            isFormSubmitting = false;
        }
    }
}

// Fonctions utilitaires pour la gestion d'erreurs
function resetLoginButton(submitBtn, btnContent, btnLoader) {
    submitBtn.classList.remove('loading');
    btnLoader.style.display = 'none';
    btnContent.style.display = 'flex';
    submitBtn.disabled = false;
}

function shouldRetry(error) {
    // Retry pour certains types d'erreurs seulement
    const retryableErrors = ['NetworkError', 'TimeoutError', 'AbortError'];
    return retryableErrors.some(type => error.name.includes(type)) || 
           error.message.includes('fetch');
}

function getErrorMessage(error) {
    if (error.name === 'AbortError') {
        return 'La connexion a pris trop de temps. Vérifiez votre réseau.';
    } else if (error.message.includes('fetch')) {
        return 'Problème de connexion au serveur. Vérifiez votre réseau.';
    } else {
        return 'Erreur de réseau. Veuillez réessayer.';
    }
}

function showRetryOption(form) {
    const retryNotification = document.createElement('div');
    retryNotification.className = 'retry-notification';
    retryNotification.innerHTML = `
        <p>Problème de connexion détecté</p>
        <button onclick="submitLoginForm(document.getElementById('loginForm'))" class="retry-btn">
            <i class="fas fa-redo"></i> Réessayer
        </button>
    `;
    
    document.querySelector('.login-container').appendChild(retryNotification);
    
    setTimeout(() => {
        if (retryNotification.parentElement) {
            retryNotification.remove();
        }
    }, 10000);
}

// Analytics de connexion
function logConnectionEvent(eventType, data) {
    try {
        // Stocker localement pour envoi différé si nécessaire
        const events = JSON.parse(localStorage.getItem('connectionEvents') || '[]');
        events.push({
            type: eventType,
            data: data,
            timestamp: Date.now()
        });
        
        // Garder seulement les 50 derniers événements
        if (events.length > 50) {
            events.splice(0, events.length - 50);
        }
        
        localStorage.setItem('connectionEvents', JSON.stringify(events));
        
        // Envoyer immédiatement si possible (sans bloquer)
        if (navigator.onLine) {
            sendAnalyticsAsync(eventType, data);
        }
    } catch (e) {
        console.log('Analytics logging failed:', e);
    }
}

async function sendAnalyticsAsync(eventType, data) {
    try {
        await fetch('forms/analytics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                event: eventType,
                data: data
            })
        });
    } catch (e) {
        // Échec silencieux pour les analytics
    }
}

// Notifications améliorées
function showNotification(message, type = 'info', duration = 5000) {
    const container = document.getElementById('message-container');
    const notification = document.createElement('div');
    notification.className = `message ${type}`;
    
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    
    notification.innerHTML = `
        <div class="message-icon">
            <i class="fas fa-${icons[type]}"></i>
        </div>
        <div class="message-content">
            <span>${message}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="message-close">
            <i class="fas fa-times"></i>
        </button>
        <div class="message-progress"></div>
    `;
    
    container.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Barre de progression
    const progressBar = notification.querySelector('.message-progress');
    progressBar.style.animationDuration = duration + 'ms';
    
    // Auto-suppression
    setTimeout(() => {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }, duration);
}

// Vérification des paramètres URL
function checkUrlParameters() {
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    const success = urlParams.get('success');
    
    if (error) {
        const errorMessages = {
            'invalid_credentials': 'Email ou mot de passe incorrect',
            'account_disabled': 'Votre compte a été désactivé. Contactez le support.',
            'too_many_attempts': 'Trop de tentatives de connexion. Réessayez dans quelques minutes.',
            'session_expired': 'Votre session a expiré. Veuillez vous reconnecter.',
            'account_not_verified': 'Veuillez vérifier votre adresse email avant de vous connecter.'
        };
        
        showNotification(errorMessages[error] || 'Erreur de connexion', 'error');
    }
    
    if (success) {
        const successMessages = {
            'registered': 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.',
            'logout': 'Vous avez été déconnecté avec succès.',
            'password_reset': 'Votre mot de passe a été réinitialisé. Vérifiez votre email.',
            'email_verified': 'Email vérifié avec succès ! Vous pouvez maintenant vous connecter.'
        };
        
        showNotification(successMessages[success] || 'Opération réussie', 'success');
    }
}
