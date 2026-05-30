// Page d'inscription - JavaScript
// WebiTech 2024

// Variables globales
let isFormSubmitting = false;
let currentTheme = localStorage.getItem('theme') || 'dark';

// Initialisation de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializeAnimations();
    initializeFormValidation();
    setupKeyboardShortcuts();
    loadSavedData();
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

// Animations
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
    if (!element) return;
    
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

// Validation du formulaire en temps réel
function initializeFormValidation() {
    const inputs = ['username', 'email', 'phone', 'password', 'confirm_password'];
    
    inputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', () => validateField(inputId));
            input.addEventListener('blur', () => validateField(inputId));
            
            // Effets de focus
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
        }
    });
}

// Validation des champs individuels
function validateField(fieldId) {
    const input = document.getElementById(fieldId);
    const container = input.parentElement;
    let isValid = false;
    
    switch (fieldId) {
        case 'username':
            isValid = validateUsername(input.value);
            break;
        case 'email':
            isValid = validateEmail(input.value);
            break;
        case 'phone':
            isValid = validatePhone(input.value);
            break;
        case 'password':
            isValid = validatePassword(input.value);
            updatePasswordStrength(input.value);
            // Revalider la confirmation si elle existe
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                validateField('confirm_password');
            }
            break;
        case 'confirm_password':
            isValid = validateConfirmPassword(input.value);
            break;
    }
    
    // Mettre à jour l'interface
    if (input.value.trim() === '') {
        container.classList.remove('valid', 'invalid');
    } else if (isValid) {
        container.classList.add('valid');
        container.classList.remove('invalid');
    } else {
        container.classList.add('invalid');
        container.classList.remove('valid');
    }
    
    return isValid;
}

// Fonctions de validation
function validateUsername(username) {
    return username.length >= 3 && /^[a-zA-Z0-9_-]+$/.test(username);
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function validatePhone(phone) {
    const phoneRegex = /^[\d\s\-\+\(\)]{8,}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

function validatePassword(password) {
    return password.length >= 8;
}

function validateConfirmPassword(confirmPassword) {
    const password = document.getElementById('password').value;
    return confirmPassword === password && confirmPassword.length > 0;
}

// Indicateur de force du mot de passe
function updatePasswordStrength(password) {
    const strengthBar = document.querySelector('.strength-bar');
    if (!strengthBar) return;
    
    let strength = 0;
    let strengthClass = 'weak';
    
    if (password.length >= 8) strength += 25;
    if (/[A-Z]/.test(password)) strength += 25;
    if (/[a-z]/.test(password)) strength += 25;
    if (/[0-9]/.test(password)) strength += 25;
    if (/[^A-Za-z0-9]/.test(password)) strength += 25;
    
    // Déterminer la classe de force
    if (strength >= 100) strengthClass = 'very-strong';
    else if (strength >= 75) strengthClass = 'strong';
    else if (strength >= 50) strengthClass = 'medium';
    
    strengthBar.style.width = Math.min(strength, 100) + '%';
    strengthBar.className = `strength-bar ${strengthClass}`;
}

// Toggle password visibility
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const passwordIcon = document.getElementById(fieldId + '-icon');
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

// Raccourcis clavier
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl+Enter ou Cmd+Enter pour soumettre
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            const form = document.getElementById('registerForm');
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

// Charger les données sauvegardées
function loadSavedData() {
    const savedEmail = localStorage.getItem('registerEmail');
    const savedUsername = localStorage.getItem('registerUsername');
    
    if (savedEmail) {
        const emailInput = document.getElementById('email');
        emailInput.value = savedEmail;
        emailInput.parentElement.classList.add('focused');
    }
    
    if (savedUsername) {
        const usernameInput = document.getElementById('username');
        usernameInput.value = savedUsername;
        usernameInput.parentElement.classList.add('focused');
    }
}

// Sauvegarder les données pendant la saisie
function saveFormData() {
    const email = document.getElementById('email').value;
    const username = document.getElementById('username').value;
    
    if (email.includes('@')) {
        localStorage.setItem('registerEmail', email);
    }
    
    if (username.length >= 3) {
        localStorage.setItem('registerUsername', username);
    }
}

// Soumission du formulaire
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (isFormSubmitting) {
        return;
    }
    
    // Validation finale
    const fields = ['username', 'email', 'phone', 'password', 'confirm_password'];
    let allValid = true;
    
    fields.forEach(fieldId => {
        if (!validateField(fieldId)) {
            allValid = false;
        }
    });
    
    // Vérifier les conditions d'utilisation
    const termsCheckbox = document.getElementById('terms');
    if (!termsCheckbox.checked) {
        showNotification('Vous devez accepter les conditions d\'utilisation', 'error');
        allValid = false;
    }
    
    if (!allValid) {
        showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
        return;
    }
    
    await submitRegistrationForm(e.target);
});

// Fonction de soumission
async function submitRegistrationForm(form) {
    isFormSubmitting = true;
    const formData = new FormData(form);
    const submitBtn = document.getElementById('registerBtn');
    const btnContent = submitBtn.querySelector('.btn-content');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    const btnSuccess = submitBtn.querySelector('.btn-success');
    
    try {
        // Animation de chargement
        submitBtn.classList.add('loading');
        btnContent.style.display = 'none';
        btnLoader.style.display = 'flex';
        submitBtn.disabled = true;
        
        console.log('Envoi de la requête d\'inscription...');
        
        // Envoi de la requête
        const response = await fetch('forms/register_process.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('Réponse reçue, status:', response.status);
        
        // Vérifier si la réponse est OK
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        
        const responseText = await response.text();
        console.log('Réponse brute:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Erreur de parsing JSON:', parseError);
            console.error('Réponse reçue:', responseText);
            throw new Error('Réponse invalide du serveur');
        }
        
        console.log('Résultat parsé:', result);
        
        if (result.success) {
            // Succès - Animation de succès
            submitBtn.classList.remove('loading');
            submitBtn.classList.add('success');
            btnLoader.style.display = 'none';
            btnSuccess.style.display = 'flex';
            
            showNotification(result.message || 'Compte créé avec succès !', 'success');
            
            // Nettoyer les données sauvegardées
            localStorage.removeItem('registerEmail');
            localStorage.removeItem('registerUsername');
            
            // Redirection après un court délai
            setTimeout(() => {
                window.location.href = result.redirect || 'login.php?success=registered';
            }, 2000);
            
        } else {
            // Erreur - Affichage du message d'erreur
            submitBtn.classList.remove('loading');
            btnLoader.style.display = 'none';
            btnContent.style.display = 'flex';
            
            showNotification(result.message || 'Erreur lors de la création du compte', 'error');
            
            // Animation de secousse du formulaire
            form.classList.add('shake');
            setTimeout(() => form.classList.remove('shake'), 600);
            
            // Réactivation du bouton
            submitBtn.disabled = false;
            isFormSubmitting = false;
        }
        
    } catch (error) {
        console.error('Erreur d\'inscription:', error);
        
        // Erreur réseau ou autre
        submitBtn.classList.remove('loading');
        btnLoader.style.display = 'none';
        btnContent.style.display = 'flex';
        submitBtn.disabled = false;
        
        showNotification('Erreur de réseau ou du serveur. Veuillez réessayer.', 'error');
        isFormSubmitting = false;
    }
}

// Notifications
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

// Sauvegarder automatiquement pendant la saisie
document.getElementById('email').addEventListener('input', saveFormData);
document.getElementById('username').addEventListener('input', saveFormData);

console.log("📝 Page d'inscription WebiTech chargée avec succès !");
