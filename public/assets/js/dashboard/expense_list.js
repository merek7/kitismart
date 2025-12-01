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
                noResults: function () {
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
            noResultsMsg.fadeOut(300, function () {
                $(this).remove();
            });
        }

        // Mettre à jour les statistiques
        updateStats();
    }

    // Réinitialiser les filtres
    $('#reset-filters').on('click', function () {
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
    $('#filter-search').on('keyup', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
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
    $('#edit-expense-modal').on('click', function (e) {
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

        // Récupérer le montant du champ hidden (valeur brute) ou du champ visible
        const amountValue = $('#edit-amount_raw').val() || $('#edit-amount').val();
        const parsedAmount = parseFloat(amountValue.toString().replace(/\s/g, '').replace(',', '.'));

        const formData = {
            description: $('#edit-description').val(),
            amount: parsedAmount,
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

                    // Mettre à jour la catégorie si fournie
                    if (formData.category_type) {
                        card.find('.category-badge').text(formData.category_type.charAt(0).toUpperCase() + formData.category_type.slice(1));
                    }

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
            complete: function () {
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
    $('#delete-expense-modal').on('click', function (e) {
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
                    card.fadeOut(400, function () {
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
            complete: function () {
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
                    $btn.fadeOut(300, function () {
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
    // PIÈCES JOINTES
    // ===================================

    // Charger les pièces jointes lors de l'ouverture de la modale
    function loadAttachments(expenseId) {
        $('#attachments-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>');

        $.ajax({
            url: `/expenses/${expenseId}/attachments`,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    displayAttachments(response.attachments);
                } else {
                    $('#attachments-list').html('<div class="alert-error">Erreur lors du chargement</div>');
                }
            },
            error: function () {
                $('#attachments-list').html('<div class="alert-error">Erreur de communication</div>');
            }
        });
    }

    // Afficher les pièces jointes
    function displayAttachments(attachments) {
        const container = $('#attachments-list');
        container.empty();

        if (!attachments || attachments.length === 0) {
            return; // Le CSS ::before affichera "Aucune pièce jointe"
        }

        attachments.forEach(function (attachment) {
            const isImage = attachment.icon === 'fa-image';
            const hasPreview = isImage;

            let itemHTML = `<div class="attachment-item ${hasPreview ? 'has-preview' : ''}" data-id="${attachment.id}">`;

            // Ajouter l'aperçu d'image si c'est une image
            if (hasPreview) {
                itemHTML += `
                    <div class="attachment-preview">
                        <img src="${attachment.url}" alt="${attachment.filename}" loading="lazy">
                    </div>
                `;
            }

            itemHTML += `
                <div class="attachment-info">
                    ${!hasPreview ? `<i class="fas ${attachment.icon} attachment-icon"></i>` : ''}
                    <div class="attachment-details">
                        <div class="attachment-name">${attachment.filename}</div>
                        <div class="attachment-meta">${attachment.size} • ${attachment.uploaded_at}</div>
                    </div>
                </div>
                <div class="attachment-actions">
                    ${!hasPreview ? `<button type="button" class="btn btn-sm btn-outline-primary view-attachment-btn"
                        data-url="${attachment.url}"
                        data-filename="${attachment.filename}"
                        data-icon="${attachment.icon}"
                        data-size="${attachment.size}">
                        <i class="fas fa-eye"></i> Voir
                    </button>` : ''}
                    <button type="button" class="btn btn-sm btn-outline-danger delete-attachment-btn" data-id="${attachment.id}" data-filename="${attachment.filename}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>`;

            const item = $(itemHTML);
            container.append(item);
        });
    }

    // Bouton d'ajout de pièce jointe
    $('#add-attachment-btn').on('click', function () {
        // Enlever l'attribut capture pour la sélection de fichier
        $('#attachment-file').removeAttr('capture');
        $('#attachment-file').click();
    });

    // Bouton pour prendre une photo (mobile uniquement)
    $('#take-photo-btn').on('click', function () {
        // Ajouter l'attribut capture pour ouvrir la caméra
        $('#attachment-file').attr('capture', 'environment');
        $('#attachment-file').attr('accept', 'image/*');
        $('#attachment-file').click();
    });

    // Upload de pièce jointe
    $('#attachment-file').on('change', function () {
        const file = this.files[0];
        if (!file) return;

        // Validation de la taille
        if (file.size > 5 * 1024 * 1024) {
            showNotification('Le fichier est trop volumineux (max 5 MB)', 'error');
            this.value = '';
            return;
        }

        // Upload
        const formData = new FormData();
        formData.append('attachment', file);
        formData.append('expense_id', currentExpenseId);

        const uploadBtn = $('#add-attachment-btn');
        const originalText = uploadBtn.html();
        uploadBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Envoi...');

        $.ajax({
            url: '/expenses/attachments/upload',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    loadAttachments(currentExpenseId);
                    showNotification('Pièce jointe ajoutée avec succès', 'success');
                } else {
                    showNotification('Erreur : ' + response.message, 'error');
                }
            },
            error: function () {
                showNotification('Erreur lors de l\'upload', 'error');
            },
            complete: function () {
                uploadBtn.prop('disabled', false).html(originalText);
                $('#attachment-file').val('');
            }
        });
    });

    // Supprimer une pièce jointe
    $(document).on('click', '.delete-attachment-btn', function () {
        const attachmentId = $(this).data('id');
        const filename = $(this).data('filename');
        const item = $(this).closest('.attachment-item');
        const btn = $(this);

        // Créer une mini-modal de confirmation
        const confirmDialog = $(`
            <div class="delete-confirm-overlay">
                <div class="delete-confirm-box">
                    <div class="delete-confirm-icon">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <h4>Supprimer la pièce jointe ?</h4>
                    <p>${filename}</p>
                    <div class="delete-confirm-actions">
                        <button class="btn btn-cancel delete-confirm-cancel">Annuler</button>
                        <button class="btn btn-danger delete-confirm-ok">Supprimer</button>
                    </div>
                </div>
            </div>
        `);

        $('body').append(confirmDialog);
        setTimeout(() => confirmDialog.addClass('active'), 10);

        // Annuler
        confirmDialog.find('.delete-confirm-cancel').on('click', function () {
            confirmDialog.removeClass('active');
            setTimeout(() => confirmDialog.remove(), 300);
        });

        // Confirmer la suppression
        confirmDialog.find('.delete-confirm-ok').on('click', function () {
            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: `/attachments/${attachmentId}`,
                method: 'DELETE',
                success: function (response) {
                    if (response.success) {
                        confirmDialog.removeClass('active');
                        setTimeout(() => confirmDialog.remove(), 300);

                        item.fadeOut(300, function () {
                            $(this).remove();
                        });
                        showNotification('Pièce jointe supprimée', 'success');
                    } else {
                        confirmDialog.removeClass('active');
                        setTimeout(() => confirmDialog.remove(), 300);
                        showNotification('Erreur : ' + response.message, 'error');
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function () {
                    confirmDialog.removeClass('active');
                    setTimeout(() => confirmDialog.remove(), 300);
                    showNotification('Erreur lors de la suppression', 'error');
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Fermer en cliquant sur le fond
        confirmDialog.on('click', function (e) {
            if (e.target === this) {
                confirmDialog.removeClass('active');
                setTimeout(() => confirmDialog.remove(), 300);
            }
        });
    });

    // Visualiser un fichier (images, PDF, Word, Excel)
    $(document).on('click', '.view-attachment-btn', function () {
        showFileInViewer($(this).data('url'), $(this).data('filename'), $(this).data('icon'), $(this).data('size'));
    });

    // Clic sur l'aperçu d'une image pour l'agrandir
    $(document).on('click', '.attachment-preview img', function () {
        const item = $(this).closest('.attachment-item');
        const url = $(this).attr('src');
        const filename = item.find('.attachment-name').text();
        showFileInViewer(url, filename, 'fa-image', '');
    });

    // Fonction pour afficher un fichier dans le viewer
    function showFileInViewer(url, filename, icon, size) {
        $('#file-viewer-title').text(filename);
        $('#file-download-link').attr('href', url.replace('/view', '/download'));

        const fileExtension = filename.split('.').pop().toLowerCase();
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        let content = '';

        if (imageExtensions.includes(fileExtension)) {
            // Afficher l'image
            content = `<div class="file-viewer-image"><img src="${url}" alt="${filename}"></div>`;
        } else if (fileExtension === 'pdf') {
            // Afficher le PDF dans un iframe
            content = `<iframe src="${url}" title="${filename}"></iframe>`;
        } else {
            // Pour Word/Excel, afficher les informations et proposer le téléchargement
            const iconClass = icon;
            let fileType = 'Document';
            if (iconClass === 'fa-file-word') fileType = 'Document Word';
            else if (iconClass === 'fa-file-excel') fileType = 'Feuille Excel';

            content = `
                <div class="file-info-display">
                    <div class="file-info-icon">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="file-info-details">
                        <p><strong>Nom :</strong> ${filename}</p>
                        <p><strong>Type :</strong> ${fileType}</p>
                        ${size ? `<p><strong>Taille :</strong> ${size}</p>` : ''}
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> Ce type de fichier ne peut pas être affiché dans le navigateur.<br>
                        Cliquez sur "Télécharger" pour l'ouvrir.
                    </p>
                </div>
            `;
        }

        $('#file-viewer-content').html(content);
        $('#file-viewer-modal').addClass('active');
        $('body').css('overflow', 'hidden');
    }

    // Fermer le modal de visualisation
    function hideFileViewer() {
        $('#file-viewer-modal').removeClass('active');
        $('body').css('overflow', '');
        $('#file-viewer-content').empty();
    }

    $('#file-viewer-close, #file-viewer-cancel').on('click', hideFileViewer);

    $('#file-viewer-modal').on('click', function (e) {
        if (e.target === this) {
            hideFileViewer();
        }
    });

    // Charger les pièces jointes lors de l'ouverture de la modale d'édition
    const originalEditClick = $(document).find('.edit-expense-btn').length;
    $(document).on('click', '.edit-expense-btn', function () {
        const expenseId = $(this).data('id');
        // Petit délai pour s'assurer que la modale est ouverte
        setTimeout(function () {
            loadAttachments(expenseId);
        }, 100);
    });

    // ===================================
    // INITIALISATION
    // ===================================

    // ===================================
    // VISUALISATION DES PIÈCES JOINTES (LISTE)
    // ===================================

    // Ouvrir la modale de visualisation
    $(document).on('click', '.view-attachments-trigger', function (e) {
        e.preventDefault();
        e.stopPropagation(); // Empêcher d'autres événements (comme l'ouverture de l'édition si la carte est cliquable)

        const expenseId = $(this).data('id');
        loadViewAttachments(expenseId);

        $('#view-attachments-modal').addClass('active');
        $('body').css('overflow', 'hidden');
    });

    // Fermer la modale
    function hideViewAttachmentsModal() {
        $('#view-attachments-modal').removeClass('active');
        $('body').css('overflow', '');
        $('#view-attachments-list').empty();
    }

    $('#view-attachments-close, #view-attachments-cancel').on('click', hideViewAttachmentsModal);

    $('#view-attachments-modal').on('click', function (e) {
        if (e.target === this) {
            hideViewAttachmentsModal();
        }
    });

    // Helper functions for file display
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function getFileIcon(fileType) {
        if (!fileType) return 'fa-file';
        if (fileType.startsWith('image/')) return 'fa-image';
        if (fileType === 'application/pdf') return 'fa-file-pdf';
        if (fileType.includes('word') || fileType.includes('document')) return 'fa-file-word';
        if (fileType.includes('excel') || fileType.includes('spreadsheet')) return 'fa-file-excel';
        return 'fa-file';
    }

    // Charger les pièces jointes pour la visualisation
    function loadViewAttachments(expenseId) {
        $('#view-attachments-list').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>');

        $.ajax({
            url: `/expenses/${expenseId}/attachments`,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    displayReadOnlyAttachments(response.attachments);
                } else {
                    $('#view-attachments-list').html('<div class="alert-error">Erreur lors du chargement</div>');
                }
            },
            error: function () {
                $('#view-attachments-list').html('<div class="alert-error">Erreur de communication</div>');
            }
        });
    }

    // Afficher les pièces jointes en lecture seule
    function displayReadOnlyAttachments(attachments) {
        const container = $('#view-attachments-list');
        container.empty();

        if (!attachments || attachments.length === 0) {
            container.html('<div class="text-center text-muted">Aucune pièce jointe</div>');
            return;
        }

        attachments.forEach(function (attachment) {
            const fileType = attachment.file_type || '';
            const isImage = fileType.startsWith('image/');
            const icon = getFileIcon(fileType);
            const formattedSize = formatFileSize(attachment.file_size);
            const url = attachment.url; // Use secure URL from server

            let itemHTML = `<div class="attachment-item ${isImage ? 'has-preview' : ''}">`;

            if (isImage) {
                itemHTML += `
                    <div class="attachment-preview">
                        <img src="${url}" alt="${attachment.file_name}" loading="lazy">
                    </div>
                `;
            }

            itemHTML += `
                <div class="attachment-info">
                    ${!isImage ? `<i class="fas ${icon} attachment-icon"></i>` : ''}
                    <div class="attachment-details">
                        <div class="attachment-name">${attachment.file_name}</div>
                        <div class="attachment-meta">${formattedSize} • ${attachment.uploaded_at}</div>
                    </div>
                </div>
                <div class="attachment-actions">
                    <button type="button" class="btn btn-sm btn-outline-primary view-attachment-btn"
                        data-url="${url}"
                        data-filename="${attachment.file_name}"
                        data-icon="${icon}"
                        data-size="${formattedSize}">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                </div>
            </div>`;

            container.append(itemHTML);
        });
    }

    updateStats();
});
