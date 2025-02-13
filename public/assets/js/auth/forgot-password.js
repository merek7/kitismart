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

    // Fonction pour valider l'email
    function validateEmail(email) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailPattern.test(String(email).toLowerCase());
    }

    // Fonction pour valider le formulaire
    function validateForm() {
        let isValid = true;
        hideGlobalMessage();

        const email = $('#email').val().trim();
        if (!validateEmail(email)) {
            showError($('#email'), 'Veuillez entrer une adresse e-mail valide.');
            isValid = false;
        } else {
            hideError($('#email'));
        }

        return isValid;
    }

    $('#forgotPasswordForm').on('submit', function(e) {
        e.preventDefault();
        hideGlobalMessage();
        
        if (!validateForm()) {
            return;
        }

        const formData = {
            email: $('#email').val().trim(),
            csrf_token: $('input[name="csrf_token"]').val()
        };

        $.ajax({
            url: '/forgot-password',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    showGlobalMessage('Un email contenant les instructions de réinitialisation vous a été envoyé.', 'success');
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 3000);
                } else {
                    showGlobalMessage(response.message || 'Une erreur est survenue.');
                }
            },
            error: function(xhr) {
                let message = 'Une erreur est survenue lors de l\'envoi du lien de réinitialisation.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showGlobalMessage(message);
            }
        });
    });
});