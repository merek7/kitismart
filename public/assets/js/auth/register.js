$(document).ready(function () {
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

    // Fonction pour afficher un message global (erreur ou succès)
    function showGlobalMessage(message, type = 'error') {
        const messageElement = $('#global-message');
        messageElement.removeClass('error success').addClass(type).text(message).show();
    }

    // Fonction pour masquer le message global
    function hideGlobalMessage() {
        $('#global-message').hide();
    }

    // Fonction pour valider le formulaire
    function validateForm() {
        let isValid = true;

        // Validation du nom
        const name = $('#name').val().trim();
        if (name.length < 3) {
            showError($('#name'), 'Le nom doit contenir au moins 3 caractères.');
            isValid = false;
        } else {
            hideError($('#name'));
        }

        // Validation de l'e-mail
        const email = $('#email').val().trim();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            showError($('#email'), 'Veuillez entrer une adresse e-mail valide.');
            isValid = false;
        } else {
            hideError($('#email'));
        }

        // Validation du mot de passe
        const password = $('#password').val().trim();
        if (password.length < 6) {
            showError($('#password'), 'Le mot de passe doit contenir au moins 6 caractères.');
            isValid = false;
        } else {
            hideError($('#password'));
        }

        // Validation de la confirmation du mot de passe
        const confirmPassword = $('#confirm_password').val().trim();
        if (confirmPassword !== password) {
            showError($('#confirm_password'), 'Les mots de passe ne correspondent pas.');
            isValid = false;
        } else {
            hideError($('#confirm_password'));
        }

        return isValid;
    }

    $('form').on('submit', function (e) {
        e.preventDefault();
        console.log("Formulaire soumis"); // Vérifiez si ce log apparaît
    
        hideGlobalMessage();
    
        if (!validateForm()) {
            console.log("Validation du formulaire échouée"); // Vérifiez si ce log apparaît
            showGlobalMessage('Veuillez corriger les erreurs avant de soumettre le formulaire.', 'error');
            return;
        }
    
        const formData = {
            name: $('#name').val().trim(),
            email: $('#email').val().trim(),
            password: $('#password').val().trim(),
            csrf_token: $('input[name="csrf_token"]').val(),
        };
        console.log("Données du formulaire:", formData); // Vérifiez si ce log apparaît
    
        $.ajax({
            url: '/register',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function (response) {
                if (response.success) {
                    showGlobalMessage('Inscription réussie ! Redirection en cours...', 'success');
                    setTimeout(() => {
                        window.location.href = '/login';
                    }, 2000);
                } else {
                    showGlobalMessage(response.message || 'Une erreur est survenue.', 'error');
                }
            },
            error: function (xhr) {
                console.log("Erreur AJAX:", xhr.responseText); // Vérifiez si ce log apparaît
                try {
                    const response = xhr.responseJSON;
                    if (response.errors) {
                        Object.keys(response.errors).forEach(field => {
                            showError($('#' + field), response.errors[field]);
                        });
                        showGlobalMessage(response.message || 'Des erreurs ont été détectées.', 'error');
                    } else {
                        showGlobalMessage(response.message || 'Une erreur est survenue.', 'error');
                    }
                } catch (e) {
                    showGlobalMessage('Une erreur inattendue est survenue.', 'error');
                    console.error("Erreur lors de la gestion de l'erreur AJAX:", e);
                }
            }
        });
    });

});