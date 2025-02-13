<?php
$title = 'Mot de passe oublié - KitiSmart';
?>

<section class="auth-container">
    <div class="auth-card">
        <img src="assets/img/logo.svg" alt="KitiSmart">
        <h1>Mot de passe oublié</h1>
        <div id="error-container" style="display: none;"></div>
        <form id="forgotPasswordForm" action="/forgot-password" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn-primary">Envoyer le lien de réinitialisation</button>
        </form>
        <p class="auth-link">
            <a href="/login">Retour à la connexion</a>
        </p>
    </div>
</section> 