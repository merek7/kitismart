<?php
$title = 'Inscription - KitiSmart';
?>

<section class="auth-container">
    <div class="auth-card card scale-in">
        <h1 class="fade-in-up">Inscription</h1>
        <div id="global-message" class="alert alert-dismissible" style="display: none;"></div>
        <form id="registerForm" action="/register" method="POST" class="fade-in-up delay-1">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="form-group input-group">
                <label for="name">Nom complet</label>
                <i class="fas fa-user input-group-icon"></i>
                <input type="text" id="name" name="name" class="form-control transition-all" placeholder="Jean Dupont" required>
            </div>
            <div class="form-group input-group">
                <label for="email">Adresse e-mail</label>
                <i class="fas fa-envelope input-group-icon"></i>
                <input type="email" id="email" name="email" class="form-control transition-all" placeholder="exemple@email.com" required>
            </div>
            <div class="form-group input-group">
                <label for="password">Mot de passe</label>
                <i class="fas fa-lock input-group-icon"></i>
                <input type="password" id="password" name="password" class="form-control transition-all" placeholder="Minimum 8 caractères" required minlength="8">
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="form-group input-group">
                <label for="confirm_password">Confirmez le mot de passe</label>
                <i class="fas fa-lock input-group-icon"></i>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control transition-all" placeholder="Retapez votre mot de passe" required>
                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div id="password-strength" class="password-strength" style="display: none;">
                <div class="strength-bar">
                    <div class="strength-fill"></div>
                </div>
                <span class="strength-text"></span>
            </div>
            <button type="submit" class="btn-primary btn hover-lift transition-all">
                <span class="btn-text">S'inscrire</span>
            </button>
        </form>
        <p class="auth-link fade-in-up delay-2">Déjà un compte ? <a href="/login" class="transition-colors">Connectez-vous</a></p>
    </div>
</section>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = event.currentTarget.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength indicator
document.getElementById('password').addEventListener('input', function(e) {
    const password = e.target.value;
    const strengthContainer = document.getElementById('password-strength');
    const strengthFill = strengthContainer.querySelector('.strength-fill');
    const strengthText = strengthContainer.querySelector('.strength-text');

    if (password.length === 0) {
        strengthContainer.style.display = 'none';
        return;
    }

    strengthContainer.style.display = 'block';

    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;

    const percentage = (strength / 5) * 100;
    strengthFill.style.width = percentage + '%';

    if (strength <= 2) {
        strengthFill.style.backgroundColor = '#dc3545';
        strengthText.textContent = 'Faible';
        strengthText.style.color = '#dc3545';
    } else if (strength <= 3) {
        strengthFill.style.backgroundColor = '#ffc107';
        strengthText.textContent = 'Moyen';
        strengthText.style.color = '#ffc107';
    } else {
        strengthFill.style.backgroundColor = '#28a745';
        strengthText.textContent = 'Fort';
        strengthText.style.color = '#28a745';
    }
});

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function(e) {
    const password = document.getElementById('password').value;
    const confirm = e.target.value;

    if (confirm.length > 0) {
        if (password === confirm) {
            e.target.classList.remove('error');
            e.target.classList.add('success');
        } else {
            e.target.classList.remove('success');
            e.target.classList.add('error');
        }
    } else {
        e.target.classList.remove('success', 'error');
    }
});

// Form submission with loading state
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;

    if (password !== confirm) {
        e.preventDefault();
        toast.error('Les mots de passe ne correspondent pas');
        return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;
});
</script>

<style>
.password-strength {
    margin-top: -10px;
    margin-bottom: 15px;
}

.strength-bar {
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-fill {
    height: 100%;
    width: 0;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-text {
    font-size: 12px;
    font-weight: 600;
}
</style>