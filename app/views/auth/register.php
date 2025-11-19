<?php
$title = 'Inscription - KitiSmart';
?>

<section class="auth-container">
    <div class="auth-card">
        <h1>Inscription</h1>
        <p style="color: #6b7280; margin-bottom: 1.5rem;">Créez votre compte gratuitement</p>

        <div id="global-message" class="message"></div>

        <form id="registerForm" action="/register" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="form-group">
                <label for="name">Nom complet</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder=" "
                        required
                        autocomplete="name">
                    <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                </div>
                <small class="error-message" id="name-error"></small>
            </div>

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
                <small class="error-message" id="email-error"></small>
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
                        autocomplete="new-password">
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                </div>

                <!-- Indicateur de force du mot de passe -->
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-bar-fill" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                    <ul class="password-requirements">
                        <li id="req-length">
                            <i class="fas fa-circle"></i>
                            <span>Au moins 8 caractères</span>
                        </li>
                        <li id="req-uppercase">
                            <i class="fas fa-circle"></i>
                            <span>Une lettre majuscule</span>
                        </li>
                        <li id="req-number">
                            <i class="fas fa-circle"></i>
                            <span>Un chiffre</span>
                        </li>
                        <li id="req-special">
                            <i class="fas fa-circle"></i>
                            <span>Un caractère spécial</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmez le mot de passe</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder=" "
                        required
                        autocomplete="new-password">
                    <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                    <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                </div>
                <small class="error-message" id="confirm-password-error"></small>
            </div>

            <button type="submit" class="btn-primary">
                <span>S'inscrire</span>
                <span class="spinner"></span>
            </button>
        </form>

        <p class="auth-link">Déjà un compte ? <a href="/login">Connectez-vous</a></p>
    </div>
</section>