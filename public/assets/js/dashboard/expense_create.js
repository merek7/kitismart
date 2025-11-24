$(document).ready(function () {
    // ===================================
    // INITIALISATION SELECT2
    // ===================================

    // Fonction pour initialiser Select2 sur un élément
    function initializeSelect2(element, placeholder) {
        $(element).select2({
            placeholder: placeholder || 'Choisir une option',
            allowClear: false,
            width: '100%',
            minimumResultsForSearch: Infinity, // Désactive la recherche (peu d'options)
            language: {
                noResults: function() {
                    return "Aucun résultat trouvé";
                }
            }
        });
    }

    // Initialiser Select2 sur les selects de catégorie
    $('.category-select').each(function() {
        initializeSelect2(this, 'Choisir un type');
    });

    // Initialiser Select2 sur les selects de statut
    $('select[name="status[]"]').each(function() {
        initializeSelect2(this, 'Choisir un statut');
    });

    // ===================================
    // GESTION SÉLECTEUR OBJECTIF D'ÉPARGNE
    // ===================================

    // Fonction pour afficher/masquer le sélecteur d'objectif d'épargne
    function toggleSavingsGoalSelector(categorySelect) {
        const expenseItem = $(categorySelect).closest('.expense-item');
        const savingsGoalSelector = expenseItem.find('.savings-goal-selector');

        if (savingsGoalSelector.length) {
            const selectedCategory = $(categorySelect).val();

            // Afficher le sélecteur si la catégorie est "epargne"
            if (selectedCategory === 'epargne') {
                savingsGoalSelector.slideDown(300);
            } else {
                savingsGoalSelector.slideUp(300);
                // Réinitialiser la sélection à "Ne pas lier"
                savingsGoalSelector.find('input[type="radio"][value=""]').prop('checked', true);
            }
        }
    }

    // Écouter les changements de catégorie sur tous les selects
    $(document).on('change', '.category-select', function() {
        toggleSavingsGoalSelector(this);
    });

    // Initialiser l'état au chargement de la page
    $('.category-select').each(function() {
        toggleSavingsGoalSelector(this);
    });

    // Fonctions utilitaires de gestion des erreurs
    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        formGroup.addClass('has-error');
        
        let errorElement = formGroup.find('.error-message');
        if (!errorElement.length) {
            errorElement = $('<div class="error-message"><i class="fas fa-exclamation-circle"></i>' + message + '</div>');
            formGroup.append(errorElement);
        }
        
        errorElement.hide().fadeIn(300);
    }

    function hideError(input) {
        const formGroup = input.closest('.form-group');
        formGroup.find('.error-message').hide();
        formGroup.removeClass('has-error');
    }

    function showGlobalMessage(message, type = 'error') {
        const messageElement = $('#global-message');
        const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
        messageElement.removeClass('error success')
            .addClass(type)
            .html(`<i class="fas ${icon}"></i> ${message}`)
            .show();
    }

    // Fonction pour obtenir la valeur brute d'un champ montant (avec ou sans formatage)
    function getAmountValue(expenseItem) {
        // Chercher d'abord le champ hidden créé par amount-formatter
        const hiddenInput = expenseItem.find('input[type="hidden"][name="amount[]"]');
        if (hiddenInput.length) {
            return parseFloat(hiddenInput.val()) || 0;
        }
        // Sinon chercher le champ visible
        const visibleInput = expenseItem.find('.amount-input');
        return parseFloat(visibleInput.val()) || 0;
    }

    // Validation du formulaire de dépense
    function validateForm() {
        let isValid = true;

        $('.expense-item').each(function (index) {
            const amount = getAmountValue($(this));
            const amountInput = $(this).find('.amount-input');
            if (isNaN(amount) || amount <= 0) {
                showError(amountInput, 'Veuillez entrer un montant valide supérieur à 0');
                isValid = false;
            } else {
                hideError(amountInput);
            }

            const expenseDate = $(this).find('input[name="date[]"]').val();
            if (!expenseDate) {
                showError($(this).find('input[name="date[]"]'), 'La date de la dépense est requise');
                isValid = false;
            } else {
                hideError($(this).find('input[name="date[]"]'));
            }
        });

        return isValid;
    }

    // ===================================
    // MODAL DE CONFIRMATION
    // ===================================

    let formToSubmit = null;

    // Fonction pour afficher la modale
    function showConfirmationModal(expenseCount, totalAmount) {
        $('#modal-expense-count').text(expenseCount);
        $('#modal-total-amount').text(totalAmount.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' FCFA');
        $('#confirmation-modal').addClass('active');
        // Empêcher le scroll du body
        $('body').css('overflow', 'hidden');
    }

    // Fonction pour masquer la modale
    function hideConfirmationModal() {
        $('#confirmation-modal').removeClass('active');
        $('body').css('overflow', '');
    }

    // Fermer la modale en cliquant en dehors
    $('#confirmation-modal').on('click', function(e) {
        if (e.target === this) {
            hideConfirmationModal();
            formToSubmit = null;
        }
    });

    // Bouton annuler
    $('#modal-cancel').on('click', function() {
        hideConfirmationModal();
        formToSubmit = null;
    });

    // Bouton confirmer
    $('#modal-confirm').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.html();

        // Désactiver le bouton et afficher un loader
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        hideConfirmationModal();

        if (formToSubmit) {
            submitExpenses();
        }

        // Réactiver le bouton après un court délai
        setTimeout(() => {
            $btn.prop('disabled', false).html(originalText);
        }, 1000);
    });

    // Gestion de la soumission du formulaire
    $('#expense-form').on('submit', function (e) {
        e.preventDefault();

        if (!validateForm()) {
            showGlobalMessage('Veuillez corriger les erreurs avant de soumettre le formulaire', 'error');
            return;
        }

        // Calculer le total et le nombre de dépenses
        const expenseCount = $('.expense-item').length;
        let totalAmount = 0;
        $('.expense-item').each(function() {
            totalAmount += getAmountValue($(this));
        });

        // Sauvegarder le formulaire pour soumission après confirmation
        formToSubmit = $(this);

        // Afficher la modale de confirmation
        showConfirmationModal(expenseCount, totalAmount);
    });

    // Fonction pour soumettre réellement les dépenses
    async function submitExpenses() {
        // Ajouter l'état de chargement
        formToSubmit.addClass('form-loading');

        const expense = [];
        $('.expense-item').each(function (index) {
            const selectedGoal = $(this).find('input[name="savings_goal_id[]"]:checked').val();

            console.log(`[Expense #${index}] Selected goal radio value:`, selectedGoal);

            const expenseData = {
                amount: getAmountValue($(this)),
                payment_date: $(this).find('input[name="date[]"]').val(),
                status: $(this).find('select[name="status[]"]').val(),
                category_type: $(this).find('select[name="category[]"]').val(),
                description: $(this).find('textarea[name="description[]"]').val().trim(),
                savings_goal_id: selectedGoal && selectedGoal !== '' ? parseInt(selectedGoal) : null
            };

            console.log(`[Expense #${index}] Data to send:`, expenseData);
            expense.push(expenseData);
        });

        const csrfToken = $('input[name="csrf_token"]').val();
        const dataToSend = { expenses: expense, csrf_token: csrfToken };

        console.log('[Submit] Final data to send:', dataToSend);

        // Vérifier si on est hors ligne
        if (!navigator.onLine) {
            console.log('[ExpenseCreate] Mode hors ligne détecté');

            try {
                // Sauvegarder dans IndexedDB
                await window.offlineStorage.saveOfflineExpense(dataToSend);

                // Mettre à jour le badge
                if (window.syncManager) {
                    await window.syncManager.updateSyncBadge();
                }

                showGlobalMessage('Dépense enregistrée hors ligne. Elle sera synchronisée dès que vous serez en ligne.', 'success');
                formToSubmit.trigger('reset');
                formToSubmit.removeClass('form-loading');
                return;
            } catch (error) {
                console.error('[ExpenseCreate] Erreur lors de la sauvegarde hors ligne:', error);
                showGlobalMessage('Erreur lors de l\'enregistrement hors ligne', 'error');
                formToSubmit.removeClass('form-loading');
                return;
            }
        }

        // Si en ligne, envoyer au serveur
        $.ajax({
            url: '/expenses/create',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(dataToSend),
            success: function (response) {
                formToSubmit.removeClass('form-loading');

                if (response.success) {
                    // Afficher un message différent selon s'il y a des erreurs partielles
                    if (response.errors && response.errors.length > 0) {
                        let errorsList = response.errors.join('<br>');
                        showGlobalMessage(
                            `${response.created_count} dépense(s) créée(s) avec succès, mais avec des erreurs :<br>${errorsList}`,
                            'warning'
                        );
                        // Rediriger quand même après un succès partiel
                        setTimeout(() => {
                            window.location.href = '/expenses/list';
                        }, 3000);
                    } else {
                        showGlobalMessage('Dépenses enregistrées avec succès ! Redirection...', 'success');
                        setTimeout(() => {
                            window.location.href = '/expenses/list';
                        }, 1500);
                    }
                } else {
                    // Afficher les erreurs détaillées si disponibles
                    if (response.errors && response.errors.length > 0) {
                        let errorsList = response.errors.join('<br>');
                        showGlobalMessage(`${response.message}<br>${errorsList}`, 'error');
                    } else {
                        showGlobalMessage(response.message || 'Une erreur est survenue', 'error');
                    }
                }
            },
            error: function (xhr) {
                formToSubmit.removeClass('form-loading');

                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.errors) {
                        Object.keys(response.errors).forEach(field => {
                            showError($('#' + field), response.errors[field]);
                        });
                        showGlobalMessage(response.message || 'Des erreurs ont été détectées', 'error');
                    } else {
                        showGlobalMessage(response.message || 'Une erreur est survenue', 'error');
                    }
                } catch (e) {
                    showGlobalMessage('Une erreur inattendue est survenue', 'error');
                }
            }
        });
    }

    // Ajouter une nouvelle dépense
    $('#add-expense').on('click', function () {
        const expenseContainer = $('#expense-container');
        const expenseItem = $('.expense-item').first().clone();
        const expenseCount = $('.expense-item').length + 1;

        // Détruire les instances Select2 dans l'élément cloné
        expenseItem.find('.select2-container').remove();
        expenseItem.find('select').removeClass('select2-hidden-accessible');

        // Supprimer les champs hidden générés par amount-formatter
        expenseItem.find('input[type="hidden"][name="amount[]"]').remove();

        // Recréer le champ montant proprement
        const amountInput = expenseItem.find('.amount-input');
        amountInput.attr('type', 'number');
        amountInput.attr('name', 'amount[]');
        amountInput.removeAttr('data-amount-field');

        // Réinitialiser et personnaliser le nouvel élément
        expenseItem.find('input, select, textarea').val('');
        expenseItem.find('.expense-number').text(`#${expenseCount}`);

        // Réinitialiser le sélecteur d'objectif d'épargne
        const savingsGoalSelector = expenseItem.find('.savings-goal-selector');
        if (savingsGoalSelector.length) {
            savingsGoalSelector.hide(); // Masquer par défaut
            savingsGoalSelector.find('input[type="radio"][value=""]').prop('checked', true); // Sélectionner "Ne pas lier"
        }

        // Ajouter une classe pour l'animation
        expenseItem.css('opacity', 0);
        expenseContainer.append(expenseItem);

        // Initialiser Select2 sur les nouveaux selects
        expenseItem.find('.category-select').each(function() {
            initializeSelect2(this, 'Choisir un type');
        });
        expenseItem.find('select[name="status[]"]').each(function() {
            initializeSelect2(this, 'Choisir un statut');
        });

        // Initialiser le formatage des montants sur le nouveau champ
        if (window.AmountFormatter) {
            window.AmountFormatter.initField(amountInput[0]);
        }

        // Animer l'apparition
        expenseItem.animate({
            opacity: 1,
            transform: 'translateY(0)'
        }, 400);

        // Mettre à jour le résumé avec animation
        updateExpenseSummaryWithAnimation();
        // Mettre à jour le résumé
        updateBudgetSummary();
    });

    // Supprimer une dépense
    $(document).on('click', '.btn-remove', function () {
        if (parseInt($('#expense-count').text()) === 1) {
            showGlobalMessage('Vous ne pouvez pas supprimer la dernière dépense', 'error');
            return;
        }
        const expenseItem = $(this).closest('.expense-item');
        expenseItem.animate({
            opacity: 0,
            marginLeft: '100%',
            height: 0
        }, 500, function() {
            $(this).remove();
            updateExpenseSummaryWithAnimation();
            updateBudgetSummary();
            renumberExpenses();
        });
    });

    // Animation du résumé des dépenses
    function updateExpenseSummaryWithAnimation() {
        const oldCount = parseInt($('#expense-count').text());
        const oldAmount = parseFloat($('#total-amount').text().replace(/\s/g, '').replace(',', '.')) || 0;

        const newCount = $('.expense-item').length;
        let newAmount = 0;

        $('.expense-item').each(function() {
            newAmount += getAmountValue($(this));
        });

        // Animer le compteur
        $({count: oldCount}).animate({count: newCount}, {
            duration: 500,
            step: function() {
                $('#expense-count').text(Math.round(this.count));
            }
        });

        // Animer le montant total
        $({amount: oldAmount}).animate({amount: newAmount}, {
            duration: 500,
            step: function() {
                $('#total-amount').text(this.amount.toFixed(2) + ' FCFA');
            }
        });
    }

    // Renuméroter les dépenses
    function renumberExpenses() {
        $('.expense-item').each(function(index) {
            $(this).find('.expense-number').text(`#${index + 1}`);
        });
    }

    // Variables pour suivre le budget
    let initialBudget = parseFloat($('#remaining-budget').data('initial-budget')) || 0;
    let currentBudget = initialBudget;

    // Fonction pour mettre à jour le résumé du budget
    function updateBudgetSummary() {
        let totalExpenses = 0;
        $('.expense-item').each(function() {
            totalExpenses += getAmountValue($(this));
        });

        // Calcul du budget restant
        currentBudget = initialBudget - totalExpenses;

        // Calcul du pourcentage utilisé
        const percentageUsed = ((totalExpenses / initialBudget) * 100).toFixed(2);

        // Mise à jour des affichages avec séparateurs de milliers
        $('#remaining-budget').text(currentBudget.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' FCFA');
        $('#total-amount').text(totalExpenses.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' FCFA');
        $('.progress-bar').css('width', Math.min(percentageUsed, 100) + '%');
        $('.budget-status span:first-child').text(percentageUsed + '%');

        // Changement de couleur selon le pourcentage
        const progressBar = $('.progress-bar');
        if (percentageUsed >= 90) {
            progressBar.css('background-color', 'var(--danger-color)');
        } else if (percentageUsed >= 70) {
            progressBar.css('background-color', '#facc15'); // jaune
        } else {
            progressBar.css('background-color', 'var(--primary-color)');
        }

        // Ajouter une classe si le budget est dépassé
        if (currentBudget < 0) {
            $('#remaining-budget').addClass('text-danger');
            showGlobalMessage('Attention : Vous dépassez votre budget !', 'error');
        } else {
            $('#remaining-budget').removeClass('text-danger');
            $('#global-message').hide();
        }
    }

    // Écouter les changements sur les champs de montant (input et change pour le hidden)
    $(document).on('input change', '.amount-input, input[type="hidden"][name="amount[]"]', function() {
        updateBudgetSummary();
    });

    // Initialiser le résumé des dépenses et du budget au chargement de la page
    updateBudgetSummary();
});


    