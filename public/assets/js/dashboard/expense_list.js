$(document).ready(function () {
    // ===================================
    // INITIALISATION SELECT2
    // ===================================

    // Fonction pour initialiser Select2 sur un élément
    function initializeSelect2(element, placeholder) {
        $(element).select2({
            placeholder: placeholder || 'Choisir une option',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: Infinity,
            language: {
                noResults: function() {
                    return "Aucun résultat trouvé";
                }
            }
        });
    }

    // Initialiser Select2 sur les filtres
    initializeSelect2('#filter-category', 'Toutes les catégories');
    initializeSelect2('#filter-status', 'Tous les statuts');
    initializeSelect2('#edit-category', 'Choisir une catégorie');
    initializeSelect2('#edit-status', 'Choisir un statut');

    // ===================================
    // FILTRES
    // ===================================

    // Fonction pour filtrer les dépenses
    function filterExpenses() {
        const selectedCategory = $('#filter-category').val();
        const selectedStatus = $('#filter-status').val();
        const selectedDate = $('#filter-date').val();
        const searchText = $('#filter-search').val().toLowerCase().trim();
        let visibleCards = 0;

        $('.expense-card').each(function () {
            const card = $(this);
            let showCard = true;

            // Filtre par recherche texte
            if (searchText) {
                const description = (card.data('description') || '').toString().toLowerCase();
                if (!description.includes(searchText)) {
                    showCard = false;
                }
            }

            // Filtre par catégorie
            if (selectedCategory && card.data('category') !== selectedCategory) {
                showCard = false;
            }

            // Filtre par statut
            if (selectedStatus && card.data('status') !== selectedStatus) {
                showCard = false;
            }

            // Filtre par date
            if (selectedDate) {
                const cardDate = new Date(card.data('date')).toISOString().split('T')[0];
                if (cardDate !== selectedDate) {
                    showCard = false;
                }
            }

            // Afficher ou masquer
            if (showCard) {
                visibleCards++;
                card.fadeIn(300);
            } else {
                card.fadeOut(300);
            }
        });

        // Afficher message si aucune dépense
        const noResultsMsg = $('.no-results-message');
        if (visibleCards === 0 && !noResultsMsg.length) {
            const msg = $('<div class="no-results-message alert-info">').html(
                '<i class="fas fa-info-circle"></i> Aucune dépense ne correspond aux filtres sélectionnés.'
            );
            $('.expenses-grid').append(msg);
            msg.hide().fadeIn(300);
        } else if (visibleCards > 0) {
            noResultsMsg.fadeOut(300, function() {
                $(this).remove();
            });
        }

        // Mettre à jour les statistiques
        updateStats();
    }

    // Réinitialiser les filtres
    $('#reset-filters').on('click', function() {
        $('#filter-search').val('');
        $('#filter-category').val('').trigger('change');
        $('#filter-status').val('').trigger('change');
        $('#filter-date').val('');
        filterExpenses();
    });

    // Événements de filtrage
    $('#filter-category, #filter-status, #filter-date').on('change', filterExpenses);

    // Recherche en temps réel avec debouncing
    let searchTimeout;
    $('#filter-search').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            filterExpenses();
        }, 300); // Délai de 300ms pour éviter trop de filtres
    });

    // ===================================
    // STATISTIQUES
    // ===================================

    function updateStats() {
        let totalAmount = 0;
        let pendingAmount = 0;
        let paidAmount = 0;
        let visibleCount = 0;

        $('.expense-card:visible').each(function () {
            const amount = parseFloat($(this).data('amount')) || 0;
            const status = $(this).data('status');

            totalAmount += amount;
            visibleCount++;

            if (status === 'pending') {
                pendingAmount += amount;
            } else if (status === 'paid') {
                paidAmount += amount;
            }
        });

        // Mettre à jour l'affichage
        $('#total-amount').text(formatMoney(totalAmount));
        $('#pending-amount').text(formatMoney(pendingAmount));
        $('#paid-amount').text(formatMoney(paidAmount));
        $('#expenses-count').text(visibleCount);
    }

    // Fonction pour formater les montants
    function formatMoney(amount) {
        return amount.toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' FCFA';
    }

    // ===================================
    // MODALE D'ÉDITION
    // ===================================

    let currentExpenseId = null;

    // Ouvrir la modale d'édition
    $(document).on('click', '.edit-expense-btn', function () {
        const $btn = $(this);
        currentExpenseId = $btn.data('id');

        // Remplir le formulaire
        $('#edit-expense-id').val(currentExpenseId);
        $('#edit-description').val($btn.data('description'));
        $('#edit-amount').val($btn.data('amount'));
        $('#edit-category').val($btn.data('category')).trigger('change');
        $('#edit-date').val($btn.data('date'));
        $('#edit-status').val($btn.data('status')).trigger('change');

        // Afficher la modale
        $('#edit-expense-modal').addClass('active');
        $('body').css('overflow', 'hidden');
    });

    // Fermer la modale d'édition
    function hideEditModal() {
        $('#edit-expense-modal').removeClass('active');
        $('body').css('overflow', '');
        currentExpenseId = null;
    }

    $('#edit-modal-cancel').on('click', hideEditModal);

    // Fermer en cliquant en dehors
    $('#edit-expense-modal').on('click', function(e) {
        if (e.target === this) {
            hideEditModal();
        }
    });

    // Enregistrer les modifications
    $('#edit-modal-confirm').on('click', function () {
        const $btn = $(this);
        const originalText = $btn.html();

        // Désactiver le bouton
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        const formData = {
            description: $('#edit-description').val(),
            amount: parseFloat($('#edit-amount').val()),
            category_type: $('#edit-category').val(),
            payment_date: $('#edit-date').val(),
            status: $('#edit-status').val()
        };

        $.ajax({
            url: `/expenses/update/${currentExpenseId}`,
            method: 'PUT',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function (response) {
                if (response.success) {
                    // Mettre à jour la carte
                    const card = $(`.expense-card[data-id="${currentExpenseId}"]`);

                    card.data('category', formData.category_type);
                    card.data('amount', formData.amount);
                    card.data('date', formData.payment_date);
                    card.data('status', formData.status);

                    // Mettre à jour l'affichage
                    card.find('.expense-title').text(formData.description);
                    card.find('.expense-amount').text(formatMoney(formData.amount));
                    card.find('.expense-date').text(new Date(formData.payment_date).toLocaleDateString('fr-FR'));
                    card.find('.category-badge').text(formData.category_type.charAt(0).toUpperCase() + formData.category_type.slice(1));

                    const statusClass = formData.status === 'paid' ? 'success' : 'warning';
                    const statusText = formData.status === 'paid' ? 'Payé' : 'En attente';

                    card.find('.status-badge')
                        .removeClass('badge-success badge-warning')
                        .addClass(`badge-${statusClass}`)
                        .text(statusText);

                    // Si passé à "payé", masquer le bouton "Marquer payé"
                    if (formData.status === 'paid') {
                        card.find('.mark-paid-btn').remove();
                    }

                    // Mettre à jour les données du bouton edit
                    const editBtn = card.find('.edit-expense-btn');
                    editBtn.data('description', formData.description);
                    editBtn.data('amount', formData.amount);
                    editBtn.data('category', formData.category_type);
                    editBtn.data('date', formData.payment_date);
                    editBtn.data('status', formData.status);

                    // Fermer la modale
                    hideEditModal();

                    // Mettre à jour les stats
                    updateStats();

                    // Notification de succès
                    showNotification('Dépense mise à jour avec succès', 'success');
                } else {
                    showNotification('Erreur : ' + response.message, 'error');
                }
            },
            error: function () {
                showNotification('Erreur de communication avec le serveur', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ===================================
    // MODALE DE SUPPRESSION
    // ===================================

    let deleteExpenseId = null;

    // Ouvrir la modale de suppression
    $(document).on('click', '.delete-expense-btn', function () {
        deleteExpenseId = $(this).data('id');
        $('#delete-expense-modal').addClass('active');
        $('body').css('overflow', 'hidden');
    });

    // Fermer la modale de suppression
    function hideDeleteModal() {
        $('#delete-expense-modal').removeClass('active');
        $('body').css('overflow', '');
        deleteExpenseId = null;
    }

    $('#delete-modal-cancel').on('click', hideDeleteModal);

    // Fermer en cliquant en dehors
    $('#delete-expense-modal').on('click', function(e) {
        if (e.target === this) {
            hideDeleteModal();
        }
    });

    // Confirmer la suppression
    $('#delete-modal-confirm').on('click', function () {
        const $btn = $(this);
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Suppression...');

        $.ajax({
            url: `/expenses/delete/${deleteExpenseId}`,
            method: 'DELETE',
            success: function (response) {
                if (response.success) {
                    // Retirer la carte avec animation
                    const card = $(`.expense-card[data-id="${deleteExpenseId}"]`);
                    card.fadeOut(400, function() {
                        $(this).remove();
                        updateStats();
                    });

                    hideDeleteModal();
                    showNotification('Dépense supprimée avec succès', 'success');
                } else {
                    showNotification('Erreur : ' + response.message, 'error');
                }
            },
            error: function () {
                showNotification('Erreur lors de la suppression', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ===================================
    // MARQUER COMME PAYÉ
    // ===================================

    $(document).on('click', '.mark-paid-btn', function () {
        const expenseId = $(this).data('id');
        const card = $(this).closest('.expense-card');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Traitement...');

        $.ajax({
            url: `/expenses/mark-paid/${expenseId}`,
            method: 'POST',
            success: function (response) {
                if (response.success) {
                    // Mettre à jour la carte
                    card.data('status', 'paid');
                    card.find('.status-badge')
                        .removeClass('badge-warning')
                        .addClass('badge-success')
                        .text('Payé');

                    // Retirer le bouton
                    $btn.fadeOut(300, function() {
                        $(this).remove();
                    });

                    // Mettre à jour le bouton edit
                    card.find('.edit-expense-btn').data('status', 'paid');

                    // Mettre à jour les stats
                    updateStats();

                    showNotification('Dépense marquée comme payée', 'success');
                } else {
                    showNotification('Erreur : ' + response.message, 'error');
                    $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Marquer payé');
                }
            },
            error: function () {
                showNotification('Erreur lors de la mise à jour', 'error');
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Marquer payé');
            }
        });
    });

    // ===================================
    // NOTIFICATIONS
    // ===================================

    function showNotification(message, type = 'info') {
        const notificationId = 'notification-' + Date.now();
        const iconClass = type === 'success' ? 'fa-check-circle' :
                         type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
        const bgColor = type === 'success' ? 'var(--success-color)' :
                       type === 'error' ? 'var(--danger-color)' : 'var(--info-color)';

        const notification = $(`
            <div id="${notificationId}" class="notification-toast" style="background-color: ${bgColor};">
                <i class="fas ${iconClass}"></i>
                <span>${message}</span>
                <button type="button" class="notification-close">&times;</button>
            </div>
        `);

        // Ajouter le conteneur si nécessaire
        if (!$('.notifications-container').length) {
            $('body').append('<div class="notifications-container"></div>');
        }

        $('.notifications-container').append(notification);

        // Animation d'entrée
        notification.css('opacity', 0).animate({opacity: 1}, 300);

        // Bouton fermer
        notification.find('.notification-close').on('click', function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        });

        // Auto-fermer après 5 secondes
        setTimeout(function () {
            notification.fadeOut(500, function () {
                $(this).remove();
            });
        }, 5000);
    }

    // ===================================
    // INITIALISATION
    // ===================================

    updateStats();
});
