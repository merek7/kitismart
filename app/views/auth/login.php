<?php
$title = 'Connexion - KitiSmart';
?>

<section class="auth-container">
    <div class="auth-card">
        <img src="assets/img/logo.svg" alt="KitiSmart">
        <h1>Connexion</h1>
        <p style="color: #6b7280; margin-bottom: 1.5rem;">Bon retour ! Connectez-vous à votre compte</p>

        <div id="error-container" style="display: none;"></div>

        <form id="loginForm" action="/login" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder=" "
                        required
                        autocomplete="email">
                    <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder=" "
                        required
                        autocomplete="current-password">
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <span>Se connecter</span>
                <span class="spinner"></span>
            </button>
        </form>

        <p class="auth-link">
            <a href="/forgot-password">Mot de passe oublié ?</a>
        </p>
        <p class="auth-link">Pas encore de compte ? <a href="/register">Inscrivez-vous</a></p>
    </div>
</section>