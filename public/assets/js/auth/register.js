$(document).ready(function () {
    // Auto-focus sur le champ nom
    $('#name').focus();

    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const passwordInput = $('#password');
        const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
        passwordInput.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    $('#toggleConfirmPassword').on('click', function() {
        const passwordInput = $('#confirm_password');
        const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
        passwordInput.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // Validation du nom en temps réel
    $('#name').on('input blur', function() {
        const name = $(this).val().trim();
        const formGroup = $(this).closest('.form-group');
        const errorElement = $('#name-error');

        if (name === '') {
            formGroup.removeClass('valid error');
            errorElement.hide();
        } else if (name.length < 3) {
            formGroup.removeClass('valid').addClass('error');
            errorElement.text('Le nom doit contenir au moins 3 caractères.').show();
        } else {
            formGroup.removeClass('error').addClass('valid');
            errorElement.hide();
        }
    });

    // Fonction pour détecter si c'est un email Google
    function isGoogleEmail(email) {
        const googleDomains = ['gmail.com', 'googlemail.com'];
        const domain = email.split('@')[1]?.toLowerCase();
        return googleDomains.includes(domain);
    }

    // Fonction pour afficher le message d'utiliser Google
    function showGoogleRequired(show) {
        if (show) {
            if (!$('#google-required-message').length) {
                const message = `
                    <div id="google-required-message" class="google-required-alert">
                        <i class="fab fa-google"></i>
                        <div>
                            <strong>Compte Google détecté</strong>
                            <p>Pour les adresses Gmail, veuillez utiliser le bouton "S'inscrire avec Google" ci-dessous pour une expérience optimale.</p>
                        </div>
                    </div>
                `;
                $('#email').closest('.form-group').after(message);
            }
            // Désactiver les champs de mot de passe
            $('#password, #confirm_password').prop('disabled', true).closest('.form-group').addClass('disabled');
            // Mettre en évidence le bouton Google
            $('.btn-google').addClass('pulse-highlight');
        } else {
            $('#google-required-message').remove();
            $('#password, #confirm_password').prop('disabled', false).closest('.form-group').removeClass('disabled');
            $('.btn-google').removeClass('pulse-highlight');
        }
    }

    // Validation de l'email en temps réel
    $('#email').on('input blur', function() {
        const email = $(this).val().trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const formGroup = $(this).closest('.form-group');
        const errorElement = $('#email-error');

        if (email === '') {
            formGroup.removeClass('valid error');
            errorElement.hide();
            showGoogleRequired(false);
        } else if (emailRegex.test(email)) {
            formGroup.removeClass('error').addClass('valid');
            errorElement.hide();

            // Vérifier si c'est un email Google
            if (isGoogleEmail(email)) {
                showGoogleRequired(true);
            } else {
                showGoogleRequired(false);
            }
        } else {
            formGroup.removeClass('valid').addClass('error');
            errorElement.text('Veuillez entrer une adresse e-mail valide.').show();
            showGoogleRequired(false);
        }
    });

    // Indicateur de force du mot de passe
    $('#password').on('input', function() {
        const password = $(this).val();
        const formGroup = $(this).closest('.form-group');

        // Vérifier les critères
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        // Mettre à jour les icônes des critères
        $('#req-length').toggleClass('met', requirements.length);
        $('#req-uppercase').toggleClass('met', requirements.uppercase);
        $('#req-number').toggleClass('met', requirements.number);
        $('#req-special').toggleClass('met', requirements.special);

        // Calculer le score de force
        const score = Object.values(requirements).filter(Boolean).length;
        const strengthBar = $('#strengthBar');
        const strengthText = $('#strengthText');

        // Retirer toutes les classes
        strengthBar.removeClass('weak medium strong very-strong');

        if (password.length === 0) {
            strengthText.text('');
            formGroup.removeClass('valid error');
        } else if (score === 1) {
            strengthBar.addClass('weak');
            strengthText.text('Faible').css('color', '#ef4444');
            formGroup.removeClass('valid').addClass('error');
        } else if (score === 2) {
            strengthBar.addClass('medium');
            strengthText.text('Moyen').css('color', '#f59e0b');
            formGroup.removeClass('valid error');
        } else if (score === 3) {
            strengthBar.addClass('strong');
            strengthText.text('Fort').css('color', '#10b981');
            formGroup.removeClass('error').addClass('valid');
        } else if (score === 4) {
            strengthBar.addClass('very-strong');
            strengthText.text('Très fort').css('color', '#059669');
            formGroup.removeClass('error').addClass('valid');
        }

        // Valider la confirmation si déjà remplie
        if ($('#confirm_password').val() !== '') {
            validateConfirmPassword();
        }
    });

    // Validation de la confirmation du mot de passe
    function validateConfirmPassword() {
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        const formGroup = $('#confirm_password').closest('.form-group');
        const errorElement = $('#confirm-password-error');

        if (confirmPassword === '') {
            formGroup.removeClass('valid error');
            errorElement.hide();
        } else if (confirmPassword === password) {
            formGroup.removeClass('error').addClass('valid');
            errorElement.hide();
        } else {
            formGroup.removeClass('valid').addClass('error');
            errorElement.text('Les mots de passe ne correspondent pas.').show();
        }
    }

    $('#confirm_password').on('input blur', validateConfirmPassword);

    // Fonction pour valider le formulaire
    function validateForm() {
        let isValid = true;

        // Validation du nom
        const name = $('#name').val().trim();
        if (name.length < 3) {
            $('#name').closest('.form-group').addClass('error');
            $('#name-error').text('Le nom doit contenir au moins 3 caractères.').show();
            isValid = false;
        }

        // Validation de l'e-mail
        const email = $('#email').val().trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            $('#email').closest('.form-group').addClass('error');
            $('#email-error').text('Veuillez entrer une adresse e-mail valide.').show();
            isValid = false;
        } else if (isGoogleEmail(email)) {
            $('#email').closest('.form-group').addClass('error');
            $('#email-error').text('Veuillez utiliser le bouton "S\'inscrire avec Google" pour les adresses Gmail.').show();
            showGoogleRequired(true);
            isValid = false;
        }

        // Validation du mot de passe
        const password = $('#password').val();
        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };
        const score = Object.values(requirements).filter(Boolean).length;

        if (score < 3) {
            $('#password').closest('.form-group').addClass('error');
            isValid = false;
        }

        // Validation de la confirmation
        const confirmPassword = $('#confirm_password').val();
        if (confirmPassword !== password) {
            $('#confirm_password').closest('.form-group').addClass('error');
            $('#confirm-password-error').text('Les mots de passe ne correspondent pas.').show();
            isValid = false;
        }

        return isValid;
    }

    // Soumission du formulaire
    $('#registerForm').on('submit', function (e) {
        e.preventDefault();
        console.log("Formulaire soumis");

        $('#global-message').hide();

        if (!validateForm()) {
            console.log("Validation du formulaire échouée");
            $('#global-message')
                .removeClass('success').addClass('error')
                .html('<i class="fas fa-exclamation-circle"></i> Veuillez corriger les erreurs avant de soumettre le formulaire.')
                .show();

            // Shake animation
            $('.auth-card').css('animation', 'shake 0.5s');
            setTimeout(() => {
                $('.auth-card').css('animation', '');
            }, 500);

            return;
        }

        // Ajouter loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.addClass('loading');
        submitBtn.prop('disabled', true);

        const formData = {
            name: $('#name').val().trim(),
            email: $('#email').val().trim(),
            password: $('#password').val(),
            confirm_password: $('#confirm_password').val(),
            csrf_token: $('input[name="csrf_token"]').val(),
        };

        console.log("Données du formulaire:", formData);

        $.ajax({
            url: '/register',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function (response) {
                if (response.success) {
                    // Retirer loading state
                    submitBtn.removeClass('loading');
                    submitBtn.html('<i class="fas fa-check"></i> Inscription réussie !');

                    // Message de succès
                    $('#global-message')
                        .removeClass('error').addClass('success')
                        .html('<i class="fas fa-check-circle"></i> Inscription réussie ! Redirection en cours...')
                        .show();

                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                } else {
                    // Retirer loading state
                    submitBtn.removeClass('loading');
                    submitBtn.prop('disabled', false);

                    $('#global-message')
                        .removeClass('success').addClass('error')
                        .html(`<i class="fas fa-exclamation-circle"></i> ${response.message || 'Une erreur est survenue.'}`)
                        .show();
                }
            },
            error: function (xhr) {
                console.log("Erreur AJAX:", xhr.responseText);

                // Retirer loading state
                submitBtn.removeClass('loading');
                submitBtn.prop('disabled', false);

                try {
                    const response = xhr.responseJSON;
                    if (response.errors) {
                        Object.keys(response.errors).forEach(field => {
                            const errorElement = $(`#${field}-error`);
                            errorElement.text(response.errors[field]).show();
                            $(`#${field}`).closest('.form-group').addClass('error');
                        });
                        $('#global-message')
                            .removeClass('success').addClass('error')
                            .html(`<i class="fas fa-exclamation-circle"></i> ${response.message || 'Des erreurs ont été détectées.'}`)
                            .show();
                    } else {
                        $('#global-message')
                            .removeClass('success').addClass('error')
                            .html(`<i class="fas fa-exclamation-circle"></i> ${response.message || 'Une erreur est survenue.'}`)
                            .show();
                    }
                } catch (e) {
                    $('#global-message')
                        .removeClass('success').addClass('error')
                        .html('<i class="fas fa-exclamation-circle"></i> Une erreur inattendue est survenue.')
                        .show();
                    console.error("Erreur lors de la gestion de l'erreur AJAX:", e);
                }

                // Shake animation
                $('.auth-card').css('animation', 'shake 0.5s');
                setTimeout(() => {
                    $('.auth-card').css('animation', '');
                }, 500);
            }
        });
    });
});
