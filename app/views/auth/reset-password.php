<?php
$title = 'Réinitialisation du mot de passe - KitiSmart';
?>

<section class="auth-container">
    <div class="auth-card">
        <img src="assets/img/logo.svg" alt="KitiSmart">
        <h1>Réinitialisation du mot de passe</h1>
        <div id="error-container" style="display: none;"></div>
        <form id="resetPasswordForm" action="/reset-password" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="reset_token" value="<?= $resetToken ?>">
            <div class="form-group">
                <label for="password">Nouveau mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-primary">Réinitialiser le mot de passe</button>
        </form>
        <p class="auth-link">
            <a href="/login">Retour à la connexion</a>
        </p>
    </div>
</section> 