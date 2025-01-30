<?php
$title = 'Connexion - KitiSmart';
?>

<section class="auth-container">
    <div class="auth-card">
        <img src="assets/img/logo.svg" alt="KitiSmart">
        <h1>Connexion</h1>
        <form action="/login" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Se connecter</button>
        </form>
        <p class="auth-link">Pas encore de compte ? <a href="/register">Inscrivez-vous</a></p>
    </div>
</section>