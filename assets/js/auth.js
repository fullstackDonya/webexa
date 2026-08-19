/* Webexa — Auth page JS (extracted from index.php to avoid PHP linter false positives) */

/* ── NAVIGATION ── */
function switchScreen(name) {
    document.querySelectorAll('.screen').forEach(function (s) { s.classList.remove('active'); });
    document.querySelector('.screen.' + name).classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── PASSWORD TOGGLE ── */
function togglePassword(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

/* ── PASSWORD STRENGTH ── */
function checkPwStrength(val) {
    var wrap  = document.getElementById('pw-strength-wrap');
    var fill  = document.getElementById('pw-strength-fill');
    var label = document.getElementById('pw-strength-label');
    if (!val) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'block';

    var score = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;

    var levels = [
        { w: '25%',  color: '#FF4C4C', text: 'Très faible' },
        { w: '50%',  color: '#FFB800', text: 'Faible' },
        { w: '75%',  color: '#6C63FF', text: 'Moyen' },
        { w: '100%', color: '#00C48C', text: 'Fort' },
    ];
    var l = levels[score - 1] || levels[0];
    fill.style.width      = l.w;
    fill.style.background = l.color;
    label.textContent     = l.text;
    label.style.color     = l.color;
}

/* ── MODULE TOGGLE ── */
function toggleModule(el, name) {
    if (name === 'crm') return; // CRM always required
    var cb = el.querySelector('input[type="checkbox"]');
    el.classList.toggle('selected');
    cb.checked = !cb.checked;
}

/* ── ALERT ── */
function showAlert(id, message, type) {
    type = type || 'info';
    var icons = { error: 'fa-circle-xmark', success: 'fa-circle-check', info: 'fa-circle-info' };
    document.getElementById(id).innerHTML =
        '<div class="alert ' + type + '"><i class="fas ' + icons[type] + '"></i><span>' + message + '</span></div>';
}

function clearAlert(id) { document.getElementById(id).innerHTML = ''; }

/* ── BUTTON LOADING STATE ── */
function setLoading(btn, loading) {
    if (loading) {
        btn.disabled    = true;
        btn._origHTML   = btn.innerHTML;
        btn.innerHTML   = '<div class="spinner"></div> Traitement\u2026';
    } else {
        btn.disabled  = false;
        btn.innerHTML = btn._origHTML;
    }
}

/* ── LOGIN ── */
function handleLogin(e) {
    e.preventDefault();
    clearAlert('login-message');
    var btn  = document.getElementById('btn-login');
    var form = document.getElementById('login-form');
    setLoading(btn, true);

    var body = new window.FormData(form);

    fetch('crm/api/auth/login.php', { method: 'POST', body: body })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
            if (data.success) {
                showAlert('login-message', 'Connexion r\u00e9ussie\u00a0! Redirection\u2026', 'success');
                setTimeout(function () { window.location.href = 'crm/index.php'; }, 800);
            } else {
                showAlert('login-message', data.message || 'Email ou mot de passe incorrect', 'error');
                setLoading(btn, false);
            }
        })
        .catch(function () {
            showAlert('login-message', 'Erreur r\u00e9seau. Veuillez r\u00e9essayer.', 'error');
            setLoading(btn, false);
        });
}

/* ── SIGNUP STEP 1 ── */
function handleSignupStep1(e) {
    e.preventDefault();
    clearAlert('signup-message');

    var pw  = document.getElementById('signup_password').value;
    var pw2 = document.getElementById('password_confirm').value;

    if (pw.length < 8) {
        showAlert('signup-message', 'Le mot de passe doit contenir au moins 8 caract\u00e8res', 'error');
        return;
    }
    if (pw !== pw2) {
        showAlert('signup-message', 'Les mots de passe ne correspondent pas', 'error');
        return;
    }

    var btn  = document.getElementById('btn-step1');
    var form = document.getElementById('signup-form-step1');
    setLoading(btn, true);

    var fd = new window.FormData(form);

    fetch('crm/api/auth/register.php', { method: 'POST', body: fd })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
            if (data.success) {
                sessionStorage.setItem('signup_data', JSON.stringify({
                    first_name: fd.get('first_name'),
                    last_name:  fd.get('last_name'),
                    email:      fd.get('email'),
                    phone:      fd.get('phone'),
                    position:   fd.get('position'),
                }));
                switchScreen('signup-step2');
            } else {
                showAlert('signup-message', data.message || "Erreur lors de l'inscription", 'error');
            }
        })
        .catch(function () {
            showAlert('signup-message', 'Erreur r\u00e9seau. Veuillez r\u00e9essayer.', 'error');
        })
        .finally(function () { setLoading(btn, false); });
}

/* ── SIGNUP STEP 2 ── */
function handleSignupStep2(e) {
    e.preventDefault();
    sessionStorage.setItem('company_data', JSON.stringify({
        company_name:   document.getElementById('company_name').value,
        siret:          document.getElementById('siret').value,
        vat_number:     document.getElementById('vat_number').value,
        website:        document.getElementById('website').value,
        industry:       document.getElementById('industry').value,
        employee_count: document.getElementById('employee_count').value,
        annual_revenue: document.getElementById('annual_revenue').value,
    }));
    switchScreen('signup-step3');
}

/* ── SIGNUP STEP 3 ── */
function handleSignupStep3(e) {
    e.preventDefault();
    var btn = document.getElementById('btn-finish');
    setLoading(btn, true);

    var modules = Array.from(document.querySelectorAll('input[name="modules"]:checked'))
        .map(function (el) { return el.value; });
    if (modules.indexOf('crm') === -1) modules.push('crm');

    var payload = JSON.stringify({
        profile: JSON.parse(sessionStorage.getItem('signup_data') || '{}'),
        company: JSON.parse(sessionStorage.getItem('company_data') || '{}'),
        modules: modules,
    });

    fetch('crm/api/auth/complete-setup.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    payload,
    })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
            if (data.success) {
                showAlert('signup-step3-message', '\ud83c\udf89 Compte cr\u00e9\u00e9 avec succ\u00e8s\u00a0! Redirection\u2026', 'success');
                setTimeout(function () { window.location.href = 'crm/index.php'; }, 1500);
            } else {
                showAlert('signup-step3-message', data.message || 'Erreur lors de la configuration', 'error');
                setLoading(btn, false);
            }
        })
        .catch(function () {
            showAlert('signup-step3-message', 'Erreur r\u00e9seau. Veuillez r\u00e9essayer.', 'error');
            setLoading(btn, false);
        });
}

/* ── OAUTH ── */
function loginWithGoogle()    { window.location.href = 'login_google.php'; }
function loginWithMicrosoft() { window.location.href = 'crm/api/auth/oauth-microsoft.php'; }

/* ── LAST GOOGLE ACCOUNT POPUP ── */
var _googlePopupTimer = null;
var _googlePopupDuration = 8000;

function initGoogleAccountPopup() {
    if (!window._lastGoogleAccount) return;
    var acct = window._lastGoogleAccount;

    // Don't show again if dismissed this session
    if (sessionStorage.getItem('g_popup_dismissed')) return;

    var popup   = document.getElementById('google-account-popup');
    var nameEl  = document.getElementById('g-account-name');
    var emailEl = document.getElementById('g-account-email');
    var avatarEl= document.getElementById('g-account-avatar');
    var fill    = document.getElementById('g-account-progress-fill');
    if (!popup) return;

    nameEl.textContent  = acct.name;
    emailEl.textContent = acct.email;

    if (acct.picture) {
        avatarEl.src = acct.picture;
        avatarEl.style.display = 'block';
        avatarEl.onerror = function () { this.style.display = 'none'; showInitialsAvatar(acct.name); };
    } else {
        avatarEl.style.display = 'none';
        showInitialsAvatar(acct.name);
    }

    // Show after short delay
    setTimeout(function () {
        popup.hidden = false;
        popup.classList.add('is-visible');
        // Start progress bar
        fill.style.transitionDuration = _googlePopupDuration + 'ms';
        fill.style.width = '0%';
        requestAnimationFrame(function () {
            fill.style.width = '100%';
        });
        // Auto-dismiss
        _googlePopupTimer = setTimeout(dismissGooglePopup, _googlePopupDuration);
    }, 1200);
}

function showInitialsAvatar(name) {
    var avatar = document.getElementById('g-account-avatar');
    var initials = (name || '?').split(' ').map(function (w) { return w[0]; }).join('').substring(0, 2).toUpperCase();
    var canvas  = document.createElement('canvas');
    canvas.width = canvas.height = 36;
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#6C63FF';
    ctx.beginPath(); ctx.arc(18, 18, 18, 0, Math.PI * 2); ctx.fill();
    ctx.fillStyle = '#fff';
    ctx.font = 'bold 14px Inter, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(initials, 18, 18);
    avatar.src = canvas.toDataURL();
    avatar.style.display = 'block';
}

function dismissGooglePopup() {
    var popup = document.getElementById('google-account-popup');
    if (!popup) return;
    clearTimeout(_googlePopupTimer);
    popup.classList.remove('is-visible');
    popup.classList.add('is-hiding');
    setTimeout(function () { popup.hidden = true; popup.classList.remove('is-hiding'); }, 350);
    sessionStorage.setItem('g_popup_dismissed', '1');
}

function continueWithLastGoogle() {
    if (!window._lastGoogleAccount) return;
    dismissGooglePopup();
    window.location.href = 'login_google.php?hint=' + encodeURIComponent(window._lastGoogleAccount.email);
}

// Close popup when clicking outside
document.addEventListener('click', function (e) {
    var popup = document.getElementById('google-account-popup');
    if (popup && !popup.hidden && !popup.contains(e.target)) {
        dismissGooglePopup();
    }
});

document.addEventListener('DOMContentLoaded', initGoogleAccountPopup);
