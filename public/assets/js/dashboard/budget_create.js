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

        // Validation du montant (sauf si budget indéfini)
        const isUnlimited = $('#unlimited-budget').is(':checked');
        if (!isUnlimited) {
            const amountRaw = $('#amount_raw').val() || $('#amount').val();
            const amount = parseFloat(amountRaw.toString().replace(/\s/g, '').replace(',', '.'));
            if (isNaN(amount) || amount <= 0) {
                showError($('#amount'), 'Veuillez entrer un montant valide supérieur à 0');
                isValid = false;
            } else {
                hideError($('#amount'));
            }
        } else {
            // Budget indéfini : pas de validation du montant
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

        // Désactiver le bouton et afficher le loader
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Création en cours...');

        // Vérifier si le budget est illimité
        const isUnlimited = $('#unlimited-budget').is(':checked');

        // Construire l'objet budget
        const budget = {
            name: $('#name').val().trim(),
            start_date: $('#start_date').val(),
            description: $('#description').val().trim(),
            type: $('input[name="type"]:checked').val() || 'principal',
            color: $('input[name="color"]:checked').val() || '#0d9488',
            csrf_token: $('input[name="csrf_token"]').val(),
            unlimited_budget: isUnlimited
        };

        // Ajouter le montant seulement si ce n'est pas un budget illimité
        if (!isUnlimited) {
            const amountValue = $('#amount_raw').val() || $('#amount').val();
            const parsedAmount = parseFloat(amountValue.toString().replace(/\s/g, '').replace(',', '.'));
            budget.amount = parsedAmount;
            budget.initial_amount = parsedAmount;
        }

        $.ajax({
            url: '/budget/create',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(budget),
            beforeSend: function() {
                // Afficher un overlay de chargement
                $('body').append('<div class="loading-overlay"><div class="loading-spinner"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Création du budget...</p></div></div>');
            },
            success: function (response) {
                if (response.success) {
                    showGlobalMessage('Budget créé avec succès ! Redirection...', 'success');

                    // Switch automatique vers le nouveau budget
                    if (response.budget && response.budget.id) {
                        // Stocker l'ID du nouveau budget dans la session
                        $.ajax({
                            url: '/budget/switch',
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ budget_id: response.budget.id }),
                            complete: function() {
                                // Rediriger vers le dashboard après le switch
                                setTimeout(() => {
                                    window.location.href = '/dashboard';
                                }, 1000);
                            }
                        });
                    } else {
                        setTimeout(() => {
                            window.location.href = '/dashboard';
                        }, 1000);
                    }
                } else {
                    $('.loading-overlay').remove();
                    $submitBtn.prop('disabled', false).html(originalBtnText);
                    showGlobalMessage(response.message || 'Une erreur est survenue');
                }
            },
            error: async function (xhr) {
                // En cas d'erreur réseau ou serveur, essayer de sauvegarder hors ligne
                if (xhr.status === 0 || xhr.status >= 500) {
                    console.log('[BudgetCreate] Erreur réseau/serveur - tentative de sauvegarde hors ligne');

                    // Vérifier si le stockage hors ligne est disponible
                    if (window.offlineStorage) {
                        try {
                            await window.offlineStorage.saveOfflineBudget(budget);

                            // Mettre à jour le badge de synchronisation
                            if (window.syncManager) {
                                await window.syncManager.updateSyncBadge();
                            }

                            showGlobalMessage('Budget enregistré hors ligne. Il sera synchronisé automatiquement.', 'success');

                            setTimeout(() => {
                                window.location.href = '/dashboard';
                            }, 2000);
                            return;
                        } catch (offlineError) {
                            console.error('[BudgetCreate] Erreur sauvegarde hors ligne:', offlineError);
                        }
                    }
                }

                // Retirer l'overlay et réactiver le bouton
                $('.loading-overlay').remove();
                $submitBtn.prop('disabled', false).html(originalBtnText);

                // Afficher l'erreur du serveur
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

    // Initialiser Select2 pour la recherche dans les budgets
    $('#budget-select').select2({
        placeholder: '-- Rechercher un budget --',
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "Aucun budget trouvé";
            },
            searching: function() {
                return "Recherche en cours...";
            }
        }
    });

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
        // Incrémenter intelligemment le nom du budget uniquement s'il existe
        // (les anciens budgets n'ont pas de nom sauvegardé)
        if (name && name.trim() !== '') {
            const newName = incrementBudgetName(name);
            $('#name').val(newName);
            $('#name').closest('.form-group').addClass('valid');
        } else {
            // Laisser le champ nom vide pour les budgets sans nom
            $('#name').val('');
            $('#name').closest('.form-group').removeClass('valid');
        }

        // Remplir le montant
        $('#amount').val(amount);

        // Remplir la description si elle existe
        if (description) {
            $('#description').val(description);
        }

        // Calculer automatiquement la date de début (mois suivant)
        const suggestedDate = calculateNextMonthStart();
        $('#start_date').val(suggestedDate);

        // Marquer les champs montant et date comme valides
        $('#amount, #start_date').each(function() {
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

    // ===================================
    // GESTION DU BUDGET INDÉFINI (TOGGLE)
    // ===================================

    $('#unlimited-budget').on('change', function() {
        const isUnlimited = $(this).is(':checked');
        const $amountInput = $('#amount');
        const $amountField = $('#amount-field');
        const $hintLimited = $('.hint-limited');
        const $hintUnlimited = $('.hint-unlimited');

        if (isUnlimited) {
            // Désactiver le champ montant
            $amountInput.prop('required', false);
            $amountInput.prop('disabled', true);
            $amountInput.val('');
            $amountField.addClass('disabled');
            $amountInput.attr('placeholder', 'Aucun montant requis');

            // Changer le texte d'aide
            $hintLimited.hide();
            $hintUnlimited.show();
        } else {
            // Réactiver le champ montant
            $amountInput.prop('required', true);
            $amountInput.prop('disabled', false);
            $amountField.removeClass('disabled');
            $amountInput.attr('placeholder', '0.00');

            // Changer le texte d'aide
            $hintLimited.show();
            $hintUnlimited.hide();
        }
    });
});