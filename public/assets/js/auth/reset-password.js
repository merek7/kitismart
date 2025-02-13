$(document).ready(function() {
    // Fonction pour afficher les messages d'erreur
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        let errorElement = formGroup.find('.error-message');
        if (!errorElement.length) {
            errorElement = $('<span class="error-message"></span>');
            formGroup.append(errorElement);
        }
        errorElement.text(message).show();
        formGroup.addClass('has-error');
    }

    // Fonction pour masquer les messages d'erreur
    function hideError(input) {
        const formGroup = input.closest('.form-group');
        formGroup.find('.error-message').hide();
        formGroup.removeClass('has-error');
    }

    // Fonction pour afficher un message global
    function showGlobalMessage(message, type = 'error') {
        const errorContainer = $('#error-container');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        errorContainer.html(`<div class="alert ${alertClass}">${message}</div>`).fadeIn();
    }

    // Fonction pour masquer le message global
    function hideGlobalMessage() {
        $('#error-container').empty().hide();
    }

    // Fonction pour valider le formulaire
    function validateForm() {
        let isValid = true;
        hideGlobalMessage();

        // Validation du mot de passe
        const password = $('#password').val().trim();
        const confirmPassword = $('#confirm_password').val().trim();

        if (password.length < 6) {
            showError($('#password'), 'Le mot de passe doit contenir au moins 6 caractères.');
            isValid = false;
        } else {
            hideError($('#password'));
        }

        if (password !== confirmPassword) {
            showError($('#confirm_password'), 'Les mots de passe ne correspondent pas.');
            isValid = false;
        } else {
            hideError($('#confirm_password'));
        }

        return isValid;
    }

    $('#resetPasswordForm').on('submit', function(e) {
        e.preventDefault();
        hideGlobalMessage();
        
        if (!validateForm()) {
            return;
        }

        const formData = {
            password: $('#password').val().trim(),
            reset_token: $('input[name="reset_token"]').val(),
            csrf_token: $('input[name="csrf_token"]').val()
        };

        console.log("Données du formulaire:", formData);

        $.ajax({
            url: '/reset-password',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    showGlobalMessage(response.message, 'success');
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                } else {
                    showGlobalMessage(response.message || 'Une erreur est survenue.');
                }
            },
            error: function(xhr) {
                let message = 'Une erreur est survenue lors de la réinitialisation du mot de passe.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showGlobalMessage(message);
            }
        });
    });
});