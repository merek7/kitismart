$(document).ready(function () {
    // Fonction pour afficher les messages d'erreur
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        let errorElement = formGroup.find('.error-message');
        if (!errorElement.length) {
            errorElement = $('<div class="error-message"><i class="fas fa-exclamation-circle"></i> ' + message + '</div>');
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
        const messageElement = $('#global-message');
        const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
        messageElement.removeClass('error success')
            .addClass(type)
            .html(`<i class="fas ${icon}"></i> ${message}`)
            .show();
    }

    // Fonction pour valider le formulaire
    function validateForm() {
        let isValid = true;

        // Validation du nom
        const name = $('#name').val().trim();
        if (name.length < 3) {
            showError($('#name'), 'Le nom du budget doit contenir au moins 3 caractères');
            isValid = false;
        } else {
            hideError($('#name'));
        }

        // Validation du montant
        const amount = parseFloat($('#amount').val());
        if (isNaN(amount) || amount <= 0) {
            showError($('#amount'), 'Veuillez entrer un montant valide supérieur à 0');
            isValid = false;
        } else {
            hideError($('#amount'));
        }

        // Validation de la date de début
        const startDate = $('#start_date').val();
        if (!startDate) {
            showError($('#start_date'), 'La date de début est requise');
            isValid = false;
        } else {
            hideError($('#start_date'));
        }

        return isValid;
    }

    // Gestion de la soumission du formulaire
    $('#budget-form').on('submit', function (e) {
        e.preventDefault();

        if (!validateForm()) {
            showGlobalMessage('Veuillez corriger les erreurs avant de soumettre le formulaire');
            return;
        }

        const budget = {
            name: $('#name').val().trim(),
            amount: parseFloat($('#amount').val()),
            initial_amount: parseFloat($('#amount').val()),
            start_date: $('#start_date').val(),
            description: $('#description').val().trim(),
            csrf_token: $('input[name="csrf_token"]').val()
        };

        $.ajax({
            url: '/budget/create',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(budget),
            success: function (response) {
                if (response.success) {
                    showGlobalMessage('Budget créé avec succès ! Redirection...', 'success');
                    setTimeout(() => {
                        window.location.href = '/dashboard';
                    }, 2000);
                } else {
                    showGlobalMessage(response.message || 'Une erreur est survenue');
                }
            },
            error: function (xhr) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.errors) {
                        Object.keys(response.errors).forEach(field => {
                            showError($('#' + field), response.errors[field]);
                        });
                        showGlobalMessage(response.message || 'Des erreurs ont été détectées');
                    } else {
                        showGlobalMessage(response.message || 'Une erreur est survenue');
                    }
                } catch (e) {
                    showGlobalMessage('Une erreur inattendue est survenue');
                }
            }
        });
    });

    // Formatage du montant en temps réel
    
});