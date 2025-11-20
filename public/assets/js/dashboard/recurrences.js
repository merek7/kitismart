// ===================================
// RECURRENCES MANAGEMENT
// ===================================

$(document).ready(function () {
    let currentRecurrenceId = null;
    let isEditMode = false;

    // ===================================
    // MODALE - OUVRIR/FERMER
    // ===================================

    // Ouvrir modale création
    $('#add-recurrence-btn').on('click', function () {
        isEditMode = false;
        currentRecurrenceId = null;
        $('#modal-title').text('Nouvelle Récurrence');
        $('#recurrence-form')[0].reset();
        $('#recurrence-id').val('');
        $('#recurrence-start-date').val(new Date().toISOString().split('T')[0]);
        $('#recurrence-modal').addClass('active');
        $('body').css('overflow', 'hidden');
    });

    // Ouvrir modale édition
    $(document).on('click', '.edit-recurrence-btn', function () {
        isEditMode = true;
        const $card = $(this).closest('.recurrence-card');
        currentRecurrenceId = $(this).data('id');

        $('#modal-title').text('Modifier la Récurrence');
        $('#recurrence-id').val(currentRecurrenceId);
        $('#recurrence-description').val($card.data('description'));
        $('#recurrence-amount').val($card.data('amount'));
        $('#recurrence-category').val($card.data('category').toLowerCase());
        $('#recurrence-frequency').val($card.data('frequency'));

        $('#recurrence-modal').addClass('active');
        $('body').css('overflow', 'hidden');
    });

    // Fermer modale
    function hideModal() {
        $('#recurrence-modal').removeClass('active');
        $('body').css('overflow', '');
        $('#recurrence-form')[0].reset();
        currentRecurrenceId = null;
        isEditMode = false;
    }

    $('#modal-cancel').on('click', hideModal);

    // Fermer en cliquant en dehors
    $('#recurrence-modal').on('click', function (e) {
        if (e.target === this) {
            hideModal();
        }
    });

    // ===================================
    // CRÉATION / MODIFICATION
    // ===================================

    $('#modal-confirm').on('click', function () {
        const $btn = $(this);
        const originalText = $btn.html();

        // Validation
        if (!$('#recurrence-form')[0].checkValidity()) {
            $('#recurrence-form')[0].reportValidity();
            return;
        }

        // Désactiver le bouton
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        const formData = {
            csrf_token: csrfToken,
            description: $('#recurrence-description').val(),
            amount: parseFloat($('#recurrence-amount').val()),
            category_type: $('#recurrence-category').val(),
            frequency: $('#recurrence-frequency').val(),
            start_date: $('#recurrence-start-date').val()
        };

        const url = isEditMode
            ? `/expenses/recurrences/update/${currentRecurrenceId}`
            : '/expenses/recurrences/create';

        const method = 'POST';

        $.ajax({
            url: url,
            method: method,
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function (response) {
                if (response.success) {
                    showNotification(
                        isEditMode ? 'Récurrence mise à jour avec succès' : 'Récurrence créée avec succès',
                        'success'
                    );

                    // Recharger la page après un court délai
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification('Erreur : ' + response.message, 'error');
                    $btn.prop('disabled', false).html(originalText);
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Erreur de communication avec le serveur';
                showNotification(message, 'error');
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ===================================
    // ACTIVER / DÉSACTIVER
    // ===================================

    $(document).on('click', '.toggle-recurrence-btn', function () {
        const $btn = $(this);
        const recurrenceId = $btn.data('id');
        const isActive = $btn.data('active');
        const $card = $btn.closest('.recurrence-card');
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Traitement...');

        $.ajax({
            url: `/expenses/recurrences/toggle/${recurrenceId}`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ csrf_token: csrfToken }),
            success: function (response) {
                if (response.success) {
                    showNotification(response.message, 'success');

                    // Mettre à jour la carte
                    if (response.is_active) {
                        $card.removeClass('inactive');
                        $card.find('.status-badge')
                            .removeClass('badge-warning')
                            .addClass('badge-success')
                            .html('<i class="fas fa-check-circle"></i> Active');
                        $btn.removeClass('btn-success').addClass('btn-warning');
                        $btn.html('<i class="fas fa-pause"></i> Pause');
                        $btn.data('active', true);
                    } else {
                        $card.addClass('inactive');
                        $card.find('.status-badge')
                            .removeClass('badge-success')
                            .addClass('badge-warning')
                            .html('<i class="fas fa-pause-circle"></i> En pause');
                        $btn.removeClass('btn-warning').addClass('btn-success');
                        $btn.html('<i class="fas fa-play"></i> Activer');
                        $btn.data('active', false);
                    }

                    // Mettre à jour les statistiques
                    updateStats();
                } else {
                    showNotification('Erreur : ' + response.message, 'error');
                }
            },
            error: function () {
                showNotification('Erreur lors de la mise à jour', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    // ===================================
    // SUPPRESSION
    // ===================================

    let deleteRecurrenceId = null;

    // Ouvrir modale de suppression
    $(document).on('click', '.delete-recurrence-btn', function () {
        deleteRecurrenceId = $(this).data('id');
        $('#delete-modal').addClass('active');
        $('body').css('overflow', 'hidden');
    });

    // Fermer modale de suppression
    function hideDeleteModal() {
        $('#delete-modal').removeClass('active');
        $('body').css('overflow', '');
        deleteRecurrenceId = null;
    }

    $('#delete-modal-cancel').on('click', hideDeleteModal);

    // Fermer en cliquant en dehors
    $('#delete-modal').on('click', function (e) {
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
            url: `/expenses/recurrences/delete/${deleteRecurrenceId}`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ csrf_token: csrfToken }),
            success: function (response) {
                if (response.success) {
                    // Retirer la carte avec animation
                    const $card = $(`.recurrence-card[data-id="${deleteRecurrenceId}"]`);
                    $card.fadeOut(400, function () {
                        $(this).remove();
                        updateStats();

                        // Afficher message si plus de récurrences
                        if ($('.recurrence-card').length === 0) {
                            $('.recurrences-grid').html(`
                                <div class="alert-info" role="alert">
                                    <i class="fas fa-info-circle"></i>
                                    Aucune récurrence configurée. Créez votre première dépense récurrente pour automatiser vos finances.
                                </div>
                            `);
                        }
                    });

                    hideDeleteModal();
                    showNotification('Récurrence supprimée avec succès', 'success');
                } else {
                    showNotification('Erreur : ' + response.message, 'error');
                }
            },
            error: function () {
                showNotification('Erreur lors de la suppression', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ===================================
    // STATISTIQUES
    // ===================================

    function updateStats() {
        let activeCount = 0;
        let inactiveCount = 0;
        let monthlyTotal = 0;

        $('.recurrence-card').each(function () {
            const $card = $(this);
            const isActive = !$card.hasClass('inactive');
            const frequency = $card.data('frequency');
            const amount = parseFloat($card.data('amount')) || 0;

            if (isActive) {
                activeCount++;

                // Convertir chaque fréquence en équivalent mensuel
                switch (frequency) {
                    case 'daily':
                        monthlyTotal += amount * 30; // ~30 jours/mois
                        break;
                    case 'weekly':
                        monthlyTotal += amount * 4.33; // ~4.33 semaines/mois
                        break;
                    case 'bimonthly':
                        monthlyTotal += amount * 2; // 2x par mois
                        break;
                    case 'monthly':
                        monthlyTotal += amount; // 1x par mois
                        break;
                    case 'yearly':
                        monthlyTotal += amount / 12; // /12 mois
                        break;
                }
            } else {
                inactiveCount++;
            }
        });

        $('#active-count').text(activeCount);
        $('#inactive-count').text(inactiveCount);
        $('#monthly-total').text(monthlyTotal.toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' FCFA');
    }

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
        notification.css('opacity', 0).animate({ opacity: 1 }, 300);

        // Bouton fermer
        notification.find('.notification-close').on('click', function () {
            notification.fadeOut(300, function () {
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
