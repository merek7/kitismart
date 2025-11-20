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

    // ===================================
    // DUPLICATION DE BUDGET
    // ===================================

    // Activer/désactiver le bouton "Charger" selon la sélection
    $('#budget-select').on('change', function() {
        const selectedValue = $(this).val();
        $('#load-budget-btn').prop('disabled', !selectedValue);
    });

    // Charger les données du budget sélectionné
    $('#load-budget-btn').on('click', function() {
        const selectedOption = $('#budget-select option:selected');

        if (!selectedOption.val()) {
            return;
        }

        // Récupérer les données depuis les attributs data-*
        const budgetName = selectedOption.data('name');
        const budgetAmount = selectedOption.data('amount');
        const budgetDescription = selectedOption.data('description');

        // Pré-remplir les champs
        fillFormWithBudgetData(budgetName, budgetAmount, budgetDescription);

        // Afficher un message de succès
        showGlobalMessage('Budget chargé avec succès! Vous pouvez modifier les champs si nécessaire.', 'success');

        // Scroll vers le formulaire
        $('html, body').animate({
            scrollTop: $('#name').offset().top - 100
        }, 500);
    });

    /**
     * Remplit le formulaire avec les données d'un budget
     */
    function fillFormWithBudgetData(name, amount, description) {
        // Incrémenter intelligemment le nom du budget
        const newName = incrementBudgetName(name);
        $('#name').val(newName);

        // Remplir le montant
        $('#amount').val(amount);

        // Remplir la description si elle existe
        if (description) {
            $('#description').val(description);
        }

        // Calculer automatiquement la date de début (mois suivant)
        const suggestedDate = calculateNextMonthStart();
        $('#start_date').val(suggestedDate);

        // Marquer les champs comme valides
        $('#name, #amount, #start_date').each(function() {
            $(this).closest('.form-group').addClass('valid');
        });
    }

    /**
     * Incrémente intelligemment le nom du budget
     * Ex: "Salaire Janvier" -> "Salaire Février"
     *     "Budget Mars 2024" -> "Budget Avril 2024"
     *     "Budget 1" -> "Budget 2"
     */
    function incrementBudgetName(name) {
        // Mapping des mois français
        const months = {
            'janvier': 'février', 'février': 'mars', 'mars': 'avril',
            'avril': 'mai', 'mai': 'juin', 'juin': 'juillet',
            'juillet': 'août', 'août': 'septembre', 'septembre': 'octobre',
            'octobre': 'novembre', 'novembre': 'décembre', 'décembre': 'janvier'
        };

        // Chercher un mois dans le nom (insensible à la casse)
        let newName = name;
        for (const [current, next] of Object.entries(months)) {
            const regex = new RegExp(current, 'gi');
            if (regex.test(name)) {
                newName = name.replace(regex, next.charAt(0).toUpperCase() + next.slice(1));
                return newName;
            }
        }

        // Chercher un numéro à la fin (ex: "Budget 1" -> "Budget 2")
        const numberMatch = name.match(/(\d+)$/);
        if (numberMatch) {
            const currentNumber = parseInt(numberMatch[1]);
            const newNumber = currentNumber + 1;
            return name.replace(/\d+$/, newNumber);
        }

        // Si aucun pattern détecté, ajouter "(copie)" ou incrémenter le numéro de copie
        if (name.includes('(copie')) {
            const copyMatch = name.match(/\(copie\s*(\d*)\)/);
            if (copyMatch) {
                const copyNumber = copyMatch[1] ? parseInt(copyMatch[1]) + 1 : 2;
                return name.replace(/\(copie\s*\d*\)/, `(copie ${copyNumber})`);
            }
        }

        return name + ' (copie)';
    }

    /**
     * Calcule le premier jour du mois suivant
     */
    function calculateNextMonthStart() {
        const today = new Date();
        const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);

        // Format YYYY-MM-DD pour input type="date"
        const year = nextMonth.getFullYear();
        const month = String(nextMonth.getMonth() + 1).padStart(2, '0');
        const day = '01';

        return `${year}-${month}-${day}`;
    }

    // Validation en temps réel
    $('#name, #amount, #start_date').on('input change', function() {
        const formGroup = $(this).closest('.form-group');
        const value = $(this).val();

        if (value && value.trim() !== '') {
            formGroup.addClass('valid').removeClass('error');
        } else {
            formGroup.removeClass('valid');
        }
    });
});