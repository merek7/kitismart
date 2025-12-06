<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= $title ?? 'Accès Budget Partagé - KitiSmart' ?></title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Page CSS -->
    <link rel="stylesheet" href="/assets/css/budget/shared_access.css">
</head>
<body class="shared-access-page">
    <div class="access-container">
        <div class="access-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h2>Accès Budget Partagé</h2>
                <p class="subtitle">Connectez-vous pour consulter ce budget</p>
            </div>

            <div class="card-body">
                <?php if (isset($invalid) && $invalid): ?>
                    <!-- Partage invalide ou désactivé -->
                    <div class="invalid-state">
                        <div class="invalid-icon">
                            <i class="fas fa-unlink"></i>
                        </div>
                        <h3>Lien Invalide</h3>
                        <p><?= htmlspecialchars($error ?? 'Ce lien de partage n\'est plus valide.') ?></p>
                        <div class="alert alert-danger">
                            <i class="fas fa-info-circle"></i>
                            <span>Le propriétaire du budget a peut-être révoqué ce partage ou le lien a expiré.</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div id="message-container"></div>

                    <form id="access-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="form-group">
                            <label for="guest_name">Votre nom</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input
                                    type="text"
                                    id="guest_name"
                                    name="guest_name"
                                    required
                                    placeholder="Entrez votre nom"
                                    autocomplete="name"
                                    minlength="2"
                                    maxlength="100">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <div class="input-wrapper">
                                <i class="fas fa-key input-icon"></i>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    placeholder="Mot de passe du partage"
                                    autocomplete="off">
                                <button type="button" class="toggle-password" id="togglePassword" aria-label="Afficher le mot de passe">
                                    <i class="fas fa-eye" id="passwordIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-access" id="submitBtn">
                            <div class="spinner"></div>
                            <span class="btn-text">
                                <i class="fas fa-sign-in-alt"></i>
                                Accéder au budget
                            </span>
                        </button>
                    </form>

                    <div class="security-info">
                        <div class="security-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="security-text">
                            <strong>Connexion sécurisée</strong>
                            Vos données sont protégées. Accès limité aux permissions accordées.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="back-home">
                    <a href="/">
                        <i class="fas fa-arrow-left"></i>
                        <span>Retour à l'accueil</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        <?php if (!isset($invalid) || !$invalid): ?>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        });

        // Form submission
        document.getElementById('access-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const messageContainer = document.getElementById('message-container');
            const guestNameInput = document.getElementById('guest_name');
            const passwordInput = document.getElementById('password');

            // Validation du nom
            if (guestNameInput.value.trim().length < 2) {
                messageContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Veuillez entrer votre nom (minimum 2 caractères)</span>
                    </div>
                `;
                guestNameInput.focus();
                return;
            }

            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            messageContainer.innerHTML = '';

            const formData = {
                csrf_token: document.querySelector('input[name="csrf_token"]').value,
                guest_name: guestNameInput.value.trim(),
                password: passwordInput.value
            };

            const token = document.querySelector('input[name="token"]').value;

            try {
                const response = await fetch(`/budget/shared/${token}/authenticate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    messageContainer.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-check-circle"></i>
                            <span>Authentification réussie ! Redirection...</span>
                        </div>
                    `;
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    messageContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>${data.message}</span>
                        </div>
                    `;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');
                    passwordInput.value = '';
                    passwordInput.focus();
                }
            } catch (error) {
                console.error('Erreur:', error);
                messageContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Erreur de connexion. Veuillez réessayer.</span>
                    </div>
                `;
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
            }
        });

        // Focus on guest name field on load
        window.addEventListener('load', function() {
            document.getElementById('guest_name').focus();
        });

        // Real-time validation
        document.getElementById('guest_name').addEventListener('input', function() {
            const formGroup = this.closest('.form-group');
            if (this.value.trim().length >= 2) {
                formGroup.classList.add('valid');
            } else {
                formGroup.classList.remove('valid');
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const formGroup = this.closest('.form-group');
            if (this.value.length >= 1) {
                formGroup.classList.add('valid');
            } else {
                formGroup.classList.remove('valid');
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
