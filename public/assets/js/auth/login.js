$(document).ready(function() {
    // Auto-focus sur le champ email
    $('#email').focus();

    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const passwordInput = $('#password');
        const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
        passwordInput.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // Validation en temps réel pour l'email
    $('#email').on('input blur', function() {
        const email = $(this).val().trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const formGroup = $(this).closest('.form-group');

        if (email === '') {
            formGroup.removeClass('valid error');
        } else if (emailRegex.test(email)) {
            formGroup.removeClass('error').addClass('valid');
        } else {
            formGroup.removeClass('valid').addClass('error');
        }
    });

    // Validation pour le password
    $('#password').on('input', function() {
        const password = $(this).val();
        const formGroup = $(this).closest('.form-group');

        if (password.length >= 6) {
            formGroup.removeClass('error').addClass('valid');
        } else if (password.length > 0) {
            formGroup.removeClass('valid').addClass('error');
        } else {
            formGroup.removeClass('valid error');
        }
    });

    // Gestion de la soumission du formulaire
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        console.log("Formulaire soumis");

        // Réinitialiser les messages d'erreur
        $('#error-container').empty().hide();

        // Ajouter loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.addClass('loading');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: '/login',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                email: $('#email').val(),
                password: $('#password').val(),
                csrf_token: $('input[name="csrf_token"]').val()
            }),
            success: function(response) {
                console.log('Réponse reçue:', response);
                if (response.success) {
                    // Animation de succès
                    submitBtn.removeClass('loading');
                    submitBtn.html('<i class="fas fa-check"></i> Connexion réussie !');

                    // Redirection après un court délai
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 500);
                } else {
                    // Retirer loading state
                    submitBtn.removeClass('loading');
                    submitBtn.prop('disabled', false);

                    // Afficher l'erreur
                    $('#error-container')
                        .html(`<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${response.message}</div>`)
                        .show();

                    // Shake animation sur la carte
                    $('.auth-card').css('animation', 'shake 0.5s');
                    setTimeout(() => {
                        $('.auth-card').css('animation', '');
                    }, 500);
                }
            },
            error: function(xhr) {
                console.log('Erreur:', xhr);

                // Retirer loading state
                submitBtn.removeClass('loading');
                submitBtn.prop('disabled', false);

                let message = 'Une erreur est survenue lors de la connexion';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                $('#error-container')
                    .html(`<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${message}</div>`)
                    .fadeIn();

                // Shake animation sur la carte
                $('.auth-card').css('animation', 'shake 0.5s');
                setTimeout(() => {
                    $('.auth-card').css('animation', '');
                }, 500);
            }
        });
    });
});
