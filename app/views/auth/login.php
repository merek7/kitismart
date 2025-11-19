<?php
$title = 'Connexion - KitiSmart';
?>

<section class="auth-container">
    <div class="auth-card card scale-in">
        <img src="assets/img/logo.svg" alt="KitiSmart" class="fade-in">
        <h1 class="fade-in-up delay-1">Connexion</h1>
        <div id="error-container" class="alert alert-dismissible" style="display: none;"></div>
        <form id="loginForm" action="/login" method="POST" class="fade-in-up delay-2">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="form-group input-group">
                <label for="email">Adresse e-mail</label>
                <i class="fas fa-envelope input-group-icon"></i>
                <input type="email" id="email" name="email" class="form-control transition-all" placeholder="exemple@email.com" required>
            </div>
            <div class="form-group input-group">
                <label for="password">Mot de passe</label>
                <i class="fas fa-lock input-group-icon"></i>
                <input type="password" id="password" name="password" class="form-control transition-all" placeholder="Votre mot de passe" required>
                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="submit" class="btn-primary btn hover-lift transition-all">
                <span class="btn-text">Se connecter</span>
            </button>
        </form>
        <p class="auth-link fade-in-up delay-3">
            <a href="/forgot-password" class="transition-colors">Mot de passe oublié ?</a>
        </p>
        <p class="auth-link fade-in-up delay-4">Pas encore de compte ? <a href="/register" class="transition-colors">Inscrivez-vous</a></p>
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

// Améliorer le formulaire avec loading state
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;
});
</script>