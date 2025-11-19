<?php
$title = 'Inscription - KitiSmart';
?>

<section class="auth-container">
    <div class="auth-card">
        <h1>Inscription</h1>
        <div id="global-message" class="message"></div>
        <form action="/register" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="form-group">
                <label for="name">Nom complet</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmez le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-primary">S'inscrire</button>
        </form>
        <p class="auth-link">Déjà un compte ? <a href="/login">Connectez-vous</a></p>
    </div>
</section>