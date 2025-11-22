<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Accès Budget Partagé - KitiSmart' ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #0d9488;
            --primary-hover: #0f766e;
            --danger-color: #dc3545;
            --text-color: #333;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .access-container {
            max-width: 450px;
            width: 100%;
        }

        .access-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 30px;
            text-align: center;
        }

        .card-header i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 35px;
        }

        .welcome-text {
            text-align: center;
            margin-bottom: 30px;
            color: #666;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
            font-size: 0.95rem;
        }

        /* Input with icon pattern */
        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-with-icon > i:first-child {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            font-size: 1rem;
            z-index: 1;
        }

        .input-with-icon input {
            width: 100%;
            padding: 14px 45px 14px 45px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-with-icon input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }

        .input-with-icon .validation-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .form-group.valid .validation-icon {
            opacity: 1;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s;
            z-index: 2;
        }

        .toggle-password:hover {
            color: var(--primary-color);
        }

        .btn-access {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-access:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(13, 148, 136, 0.4);
        }

        .btn-access:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-access .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-access.loading .spinner {
            display: inline-block;
        }

        .btn-access.loading .btn-text {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: #fee;
            border: 1px solid #fcc;
            color: var(--danger-color);
        }

        .alert-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            color: #0066cc;
        }

        .security-info {
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
        }

        .security-info i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .security-info p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }

        .back-home {
            text-align: center;
            margin-top: 20px;
        }

        .back-home a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .back-home a:hover {
            opacity: 0.8;
        }

        @media (max-width: 576px) {
            .card-header {
                padding: 25px;
            }

            .card-header i {
                font-size: 2.5rem;
            }

            .card-body {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="access-container">
        <div class="access-card">
            <div class="card-header">
                <i class="fas fa-lock"></i>
                <h2>Accès Budget Partagé</h2>
            </div>

            <div class="card-body">
                <?php if (isset($invalid) && $invalid): ?>
                    <!-- Partage invalide ou désactivé -->
                    <div class="text-center">
                        <div style="font-size: 4rem; color: #dc3545; margin-bottom: 20px;">
                            <i class="fas fa-unlink"></i>
                        </div>
                        <h3 style="color: #dc3545; margin-bottom: 15px;">Lien Invalide</h3>
                        <p style="color: #666; margin-bottom: 25px;">
                            <?= htmlspecialchars($error ?? 'Ce lien de partage n\'est plus valide.') ?>
                        </p>
                        <div class="alert alert-danger">
                            <i class="fas fa-info-circle"></i>
                            <span>Le propriétaire du budget a peut-être révoqué ce partage ou le lien a expiré.</span>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Formulaire d'accès normal -->
                    <div class="welcome-text">
                        <p>Identifiez-vous pour accéder au budget partagé</p>
                    </div>

                    <div id="message-container"></div>

                    <form id="access-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="form-group">
                            <label for="guest_name">Votre nom</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input
                                    type="text"
                                    id="guest_name"
                                    name="guest_name"
                                    required
                                    placeholder="Entrez votre nom"
                                    autocomplete="name"
                                    minlength="2"
                                    maxlength="100">
                                <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Mot de passe</label>
                            <div class="input-with-icon">
                                <i class="fas fa-key"></i>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    placeholder="Entrez le mot de passe"
                                    autocomplete="off">
                                <button type="button" class="toggle-password" id="togglePassword">
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
                        <i class="fas fa-shield-alt"></i>
                        <p>
                            <strong>Connexion sécurisée</strong><br>
                            Vos données sont protégées et votre accès est limité<br>
                            aux permissions définies par le propriétaire du budget
                        </p>
                    </div>
                <?php endif; ?>

                <div class="back-home">
                    <a href="/">
                        <i class="fas fa-arrow-left"></i> Retour à l'accueil
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
