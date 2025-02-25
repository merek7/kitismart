$(document).ready(function () {
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

    // Validation du formulaire de dépense
    function validateForm() {
        let isValid = true;

        $('.expense-item').each(function (index) {
            const amount = parseFloat($(this).find('input[name="amount[]"]').val());
            if (isNaN(amount) || amount <= 0) {
                showError($(this).find('input[name="amount[]"]'), 'Veuillez entrer un montant valide supérieur à 0');
                isValid = false;
            } else {
                hideError($(this).find('input[name="amount[]"]'));
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

    // Gestion de la soumission du formulaire
    $('#expense-form').on('submit', function (e) {
        e.preventDefault();

        if (!validateForm()) {
            showGlobalMessage('Veuillez corriger les erreurs avant de soumettre le formulaire', 'error');
            return;
        }

        // Ajouter l'état de chargement
        const form = $(this);
        form.addClass('form-loading');

        // Afficher la popup de confirmation
        if (!confirm('Voulez-vous vraiment soumettre ces dépenses ?')) {
            return;
        }

        const expense = [];
        $('.expense-item').each(function (index) {
            expense.push({
                amount: parseFloat($(this).find('input[name="amount[]"]').val()),
                payment_date: $(this).find('input[name="date[]"]').val(),
                status: $(this).find('select[name="status[]"]').val(),
                category_type: $(this).find('select[name="category[]"]').val(),
                description: $(this).find('textarea[name="description[]"]').val().trim(),
                
            });
        });

        const csrfToken = $('input[name="csrf_token"]').val();

        $.ajax({
            url: '/expenses/create',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ expenses: expense, csrf_token: csrfToken }),
            success: function (response) {
                if (response.success) {
                    showGlobalMessage('Dépenses enregistrées avec succès ! Redirection...', 'success');
                    setTimeout(() => {
                        window.location.href = '/expenses/list';
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

    // Ajouter une nouvelle dépense
    $('#add-expense').on('click', function () {
        const expenseContainer = $('#expense-container');
        const expenseItem = $('.expense-item').first().clone();
        const expenseCount = $('.expense-item').length + 1;

        // Réinitialiser et personnaliser le nouvel élément
        expenseItem.find('input, select, textarea').val('');
        expenseItem.find('.expense-number').text(`#${expenseCount}`);
        
        // Ajouter une classe pour l'animation
        expenseItem.css('opacity', 0);
        expenseContainer.append(expenseItem);
        
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
        const oldAmount = parseFloat($('#total-amount').text());
        
        const newCount = $('.expense-item').length;
        let newAmount = 0;
        
        $('.expense-item').each(function() {
            const amount = parseFloat($(this).find('input[name="amount[]"]').val()) || 0;
            newAmount += amount;
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
                $('#total-amount').text(this.amount.toFixed(2) + '');
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
    let initialBudget = parseFloat($('#remaining-budget').text().trim());
    let currentBudget = initialBudget;

    // Fonction pour mettre à jour le résumé du budget
    function updateBudgetSummary() {
        let totalExpenses = 0;
        $('.amount-input').each(function() {
            const amount = parseFloat($(this).val()) || 0;
            totalExpenses += amount;
        });

        // Calcul du budget restant
        currentBudget = initialBudget - totalExpenses;
        
        // Calcul du pourcentage utilisé
        const percentageUsed = ((totalExpenses / initialBudget) * 100).toFixed(2);
        
        // Mise à jour des affichages
        $('#remaining-budget').text(currentBudget.toFixed(2) + ' €');
        $('#total-amount').text(totalExpenses.toFixed(2) + ' €');
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

    // Écouter les changements sur les champs de montant
    $(document).on('input', '.amount-input', function() {
        updateBudgetSummary();
    });

    // Initialiser l'indicateur du nombre de dépenses et du montant total
    updateExpenseSummary();
});


    