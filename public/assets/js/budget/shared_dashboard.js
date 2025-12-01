// Shared Budget Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function () {
    // Add expense button
    const addExpenseBtn = document.getElementById('add-expense-btn');
    const expenseModal = document.getElementById('expense-modal');

    if (addExpenseBtn && expenseModal) {
        addExpenseBtn.addEventListener('click', function () {
            expenseModal.classList.add('active');
        });
    }

    // Close modal buttons
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
            }
        });
    });

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // Add expense form submission
    const addExpenseForm = document.getElementById('add-expense-form');
    if (addExpenseForm) {
        addExpenseForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const expenseMessage = document.getElementById('expense-message');
            const submitBtn = document.querySelector('button[form="add-expense-form"]');

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

            // Get form data
            const formData = {
                csrf_token: addExpenseForm.querySelector('input[name="csrf_token"]').value,
                description: document.getElementById('description').value || '',
                amount: parseFloat(document.getElementById('amount').value),
                payment_date: document.getElementById('payment_date').value,
                category_type: document.getElementById('category').value,
                status: document.getElementById('status').value
            };

            try {
                const response = await fetch('/budget/shared/expense/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    // Si on a des pièces jointes, les uploader
                    if (sharedExpenseAttachments.length > 0 && data.expense_id) {
                        await uploadAttachmentsForExpense(data.expense_id);
                    }

                    showMessage(expenseMessage, 'Dépense ajoutée avec succès ! Rechargement...', 'success');

                    // Recharger après 1 seconde
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage(expenseMessage, data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
                }
            } catch (error) {
                console.error('Erreur:', error);
                showMessage(expenseMessage, 'Erreur lors de l\'ajout de la dépense', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
            }
        });
    }
});

// Show message function
function showMessage(element, message, type) {
    element.textContent = message;
    element.className = `message ${type}`;
    element.style.display = 'flex';

    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

// ===================================
// GESTION DES PIÈCES JOINTES
// (Identique à expense_list.js pour cohérence)
// ===================================

// Stocker les fichiers
const sharedExpenseAttachments = [];

// Fonction pour formater la taille du fichier
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Fonction pour obtenir l'icône selon le type de fichier
function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'fa-image';
    if (ext === 'pdf') return 'fa-file-pdf';
    if (['doc', 'docx'].includes(ext)) return 'fa-file-word';
    if (['xls', 'xlsx'].includes(ext)) return 'fa-file-excel';
    return 'fa-file';
}

// Fonction pour vérifier si c'est une image
function isImageFile(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
}

// Fonction pour afficher les aperçus des fichiers
function displayAttachmentPreviews(previewList) {
    previewList.innerHTML = '';

    sharedExpenseAttachments.forEach((file, fileIndex) => {
        const isImage = isImageFile(file.name);
        const icon = getFileIcon(file.name);

        const preview = document.createElement('div');
        preview.className = 'attachment-preview-item ' + (isImage ? 'is-image' : 'is-document');
        preview.setAttribute('data-file-index', fileIndex);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'attachment-remove-btn';
        removeBtn.setAttribute('data-file-index', fileIndex);
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        preview.appendChild(removeBtn);

        const fileSize = document.createElement('span');
        fileSize.className = 'attachment-file-size';
        fileSize.textContent = formatFileSize(file.size);
        preview.appendChild(fileSize);

        if (isImage) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = file.name;
                preview.prepend(img);
                preview.setAttribute('data-file-url', e.target.result);
                preview.setAttribute('data-is-image', 'true');
            };
            reader.readAsDataURL(file);
        } else {
            const iconEl = document.createElement('i');
            iconEl.className = 'fas ' + icon;
            preview.appendChild(iconEl);

            const fileName = document.createElement('div');
            fileName.className = 'file-name';
            fileName.textContent = file.name;
            preview.appendChild(fileName);

            if (file.type === 'application/pdf') {
                const blobUrl = URL.createObjectURL(file);
                preview.setAttribute('data-file-url', blobUrl);
                preview.setAttribute('data-is-pdf', 'true');
            }
        }

        preview.setAttribute('data-file-name', file.name);
        preview.setAttribute('data-file-size', formatFileSize(file.size));
        preview.setAttribute('data-file-icon', icon);
        previewList.appendChild(preview);
    });
}

// Bouton ajouter fichiers
document.addEventListener('click', function (e) {
    if (e.target.closest('.add-attachment-shared-btn')) {
        const btn = e.target.closest('.add-attachment-shared-btn');
        const fileInput = btn.closest('.attachment-upload-zone').querySelector('.attachment-file-input');
        fileInput.removeAttribute('capture');
        fileInput.setAttribute('accept', '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx');
        fileInput.click();
    }
});

// Bouton prendre photo (mobile)
document.addEventListener('click', function (e) {
    if (e.target.closest('.take-photo-shared-btn')) {
        const btn = e.target.closest('.take-photo-shared-btn');
        const fileInput = btn.closest('.attachment-upload-zone').querySelector('.attachment-file-input');
        fileInput.setAttribute('accept', 'image/*');
        fileInput.setAttribute('capture', 'environment');
        fileInput.click();
    }
});

// Gestion de la sélection de fichiers
document.addEventListener('change', function (e) {
    if (e.target.classList.contains('attachment-file-input')) {
        const files = Array.from(e.target.files);
        const previewList = e.target.closest('.attachment-upload-zone').querySelector('.attachments-preview-list');

        files.forEach(file => {
            // Vérifier la taille (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showMessage(document.getElementById('expense-message'), 'Le fichier "' + file.name + '" est trop volumineux (max 5 MB)', 'error');
                return;
            }

            sharedExpenseAttachments.push(file);
        });

        displayAttachmentPreviews(previewList);
        e.target.value = ''; // Reset input
    }
});

// Supprimer un fichier
document.addEventListener('click', function (e) {
    if (e.target.closest('.attachment-remove-btn')) {
        e.stopPropagation();
        const removeBtn = e.target.closest('.attachment-remove-btn');
        const fileIndex = parseInt(removeBtn.getAttribute('data-file-index'));
        sharedExpenseAttachments.splice(fileIndex, 1);
        const previewList = removeBtn.closest('.attachments-preview-list');
        displayAttachmentPreviews(previewList);
    }
});

// Visualiser un fichier
document.addEventListener('click', function (e) {
    const previewItem = e.target.closest('.attachment-preview-item');
    if (previewItem && !e.target.closest('.attachment-remove-btn')) {
        const fileUrl = previewItem.getAttribute('data-file-url');
        const fileName = previewItem.getAttribute('data-file-name');
        const fileSize = previewItem.getAttribute('data-file-size');
        const fileIcon = previewItem.getAttribute('data-file-icon');
        const isPdf = previewItem.getAttribute('data-is-pdf');
        const isImage = previewItem.getAttribute('data-is-image');

        const modal = document.getElementById('file-viewer-modal');
        const title = document.getElementById('file-viewer-title');
        const contentDiv = document.getElementById('file-viewer-content');

        title.textContent = fileName;

        let content = '';
        if (isPdf) {
            content = '<iframe src="' + fileUrl + '" title="' + fileName + '"></iframe>';
        } else if (isImage) {
            content = '<div class="file-info-display">' +
                '<img src="' + fileUrl + '" alt="' + fileName + '" style="max-width: 100%; max-height: 60vh;">' +
                '</div>';
        } else {
            content = '<div class="file-info-display">' +
                '<div class="file-info-icon"><i class="fas ' + fileIcon + '"></i></div>' +
                '<div class="file-info-details">' +
                '<p><strong>Nom :</strong> ' + fileName + '</p>' +
                '<p><strong>Taille :</strong> ' + fileSize + '</p>' +
                '</div>' +
                '<p style="color: var(--text-secondary); font-size: 0.9rem;">' +
                '<i class="fas fa-info-circle"></i> Ce fichier sera uploadé après la création de la dépense' +
                '</p>' +
                '</div>';
        }

        contentDiv.innerHTML = content;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
});

// Fermer le modal de visualisation
function hideFileViewer() {
    const modal = document.getElementById('file-viewer-modal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

document.addEventListener('click', function (e) {
    if (e.target.id === 'file-viewer-close' || e.target.id === 'file-viewer-cancel') {
        hideFileViewer();
    }
});

// Fermer en cliquant en dehors
document.addEventListener('click', function (e) {
    const modal = document.getElementById('file-viewer-modal');
    if (e.target === modal) {
        hideFileViewer();
    }
});

// Upload des pièces jointes après création de la dépense
async function uploadAttachmentsForExpense(expenseId) {
    const uploadPromises = [];

    sharedExpenseAttachments.forEach(file => {
        const formData = new FormData();
        formData.append('attachment', file);
        formData.append('expense_id', expenseId);

        const promise = fetch('/expenses/attachments/upload', {
            method: 'POST',
            body: formData
        });

        uploadPromises.push(promise);
    });

    try {
        await Promise.all(uploadPromises);
        console.log('Tous les fichiers ont été uploadés');
    } catch (error) {
        console.error('Erreur lors de l\'upload des fichiers:', error);
        throw error;
    }
}

// ===================================
// GESTION DU MODAL DE DÉTAILS
// ===================================

// Fonction pour formater la date
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('fr-FR', options);
}

// Fonction pour formater le montant
function formatAmount(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount) + ' FCFA';
}

// Fonction pour afficher les détails d'une dépense
async function showExpenseDetails(expenseId, expenseData) {
    const modal = document.getElementById('expense-details-modal');

    // Remplir les informations de base
    document.getElementById('detail-description').textContent = expenseData.description || 'Sans description';
    document.getElementById('detail-amount').textContent = formatAmount(expenseData.amount);
    document.getElementById('detail-category').textContent = expenseData.category || 'Non catégorisé';
    document.getElementById('detail-date').textContent = formatDate(expenseData.date);

    // Statut avec icône
    const statusEl = document.getElementById('detail-status');
    if (expenseData.status === 'paid') {
        statusEl.innerHTML = '<span class="status-badge status-paid"><i class="fas fa-check-circle"></i> Payé</span>';
    } else {
        statusEl.innerHTML = '<span class="status-badge status-pending"><i class="fas fa-clock"></i> En attente</span>';
    }

    // Charger les pièces jointes si présentes
    if (expenseData.hasAttachments === '1') {
        await loadExpenseAttachments(expenseId);
    } else {
        document.getElementById('detail-attachments-section').style.display = 'none';
    }

    // Afficher le modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Fonction pour charger les pièces jointes d'une dépense
async function loadExpenseAttachments(expenseId) {
    const attachmentsSection = document.getElementById('detail-attachments-section');
    const attachmentsList = document.getElementById('detail-attachments-list');

    try {
        const response = await fetch(`/expenses/${expenseId}/attachments`);
        const data = await response.json();

        if (data.success && data.attachments && data.attachments.length > 0) {
            attachmentsList.innerHTML = '';

            data.attachments.forEach(attachment => {
                const attachmentItem = document.createElement('div');
                attachmentItem.className = 'attachment-item-readonly';
                attachmentItem.setAttribute('data-attachment-id', attachment.id);
                attachmentItem.setAttribute('data-url', attachment.url);
                attachmentItem.setAttribute('data-file-name', attachment.file_name);
                attachmentItem.setAttribute('data-file-type', attachment.file_type);

                const fileType = attachment.file_type || '';
                const isImage = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(fileType);
                const isPdf = fileType === 'application/pdf';

                let icon = 'fa-file';
                if (isImage) icon = 'fa-image';
                else if (isPdf) icon = 'fa-file-pdf';
                else if (fileType.includes('word')) icon = 'fa-file-word';
                else if (fileType.includes('excel') || fileType.includes('spreadsheet')) icon = 'fa-file-excel';

                attachmentItem.innerHTML = `
                    <div class="attachment-icon">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="attachment-info">
                        <div class="attachment-name">${attachment.file_name}</div>
                        <div class="attachment-size">${formatFileSize(attachment.file_size)}</div>
                    </div>
                    <button type="button" class="btn-view-attachment" data-attachment-id="${attachment.id}">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                `;

                attachmentsList.appendChild(attachmentItem);
            });

            attachmentsSection.style.display = 'block';
        } else {
            attachmentsSection.style.display = 'none';
        }
    } catch (error) {
        console.error('Erreur lors du chargement des pièces jointes:', error);
        attachmentsSection.style.display = 'none';
    }
}

// Gérer le clic sur les dépenses pour afficher les détails
document.addEventListener('click', function (e) {
    const expenseItem = e.target.closest('.expense-item-clickable');
    if (expenseItem) {
        const expenseData = {
            id: expenseItem.getAttribute('data-id'),
            description: expenseItem.getAttribute('data-description'),
            amount: parseFloat(expenseItem.getAttribute('data-amount')),
            category: expenseItem.getAttribute('data-category'),
            status: expenseItem.getAttribute('data-status'),
            date: expenseItem.getAttribute('data-date'),
            hasAttachments: expenseItem.getAttribute('data-has-attachments')
        };

        showExpenseDetails(expenseData.id, expenseData);
    }
});

// Fermer le modal de détails
document.addEventListener('click', function (e) {
    if (e.target.id === 'details-modal-close' ||
        (e.target.closest('[data-dismiss="modal"]') && e.target.closest('#expense-details-modal'))) {
        const modal = document.getElementById('expense-details-modal');
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Visualiser une pièce jointe depuis le modal de détails
document.addEventListener('click', async function (e) {
    const viewBtn = e.target.closest('.btn-view-attachment');
    if (viewBtn) {
        e.stopPropagation();
        const attachmentId = viewBtn.getAttribute('data-attachment-id');
        const attachmentItem = viewBtn.closest('.attachment-item-readonly');
        const fileUrl = attachmentItem.getAttribute('data-url');
        const fileName = attachmentItem.getAttribute('data-file-name');
        const fileType = attachmentItem.getAttribute('data-file-type');

        const modal = document.getElementById('file-viewer-modal');
        const title = document.getElementById('file-viewer-title');
        const contentDiv = document.getElementById('file-viewer-content');

        title.textContent = fileName;

        const isImage = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(fileType);
        const isPdf = fileType === 'application/pdf';
        const downloadUrl = `/attachments/${attachmentId}/download`;

        let content = '';
        if (isPdf) {
            content = `<iframe src="${fileUrl}" title="${fileName}"></iframe>`;
        } else if (isImage) {
            content = `<div class="file-info-display">
                <img src="${fileUrl}" alt="${fileName}" style="max-width: 100%; max-height: 70vh;">
            </div>`;
        } else {
            const icon = getFileIcon(fileName);
            content = `<div class="file-info-display">
                <div class="file-info-icon"><i class="fas ${icon}"></i></div>
                <div class="file-info-details">
                    <p><strong>Nom :</strong> ${fileName}</p>
                    <p><strong>Type :</strong> ${fileType}</p>
                </div>
                <a href="${downloadUrl}" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-download"></i> Télécharger
                </a>
            </div>`;
        }

        contentDiv.innerHTML = content;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
});

// ===================================
// FILTRAGE DES DÉPENSES
// ===================================

const searchInput = document.getElementById('expense-search');
const dateFilter = document.getElementById('expense-date-filter');

function filterExpenses() {
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const filterDate = dateFilter ? dateFilter.value : '';

    const expenseItems = document.querySelectorAll('.expense-item');
    let visibleCount = 0;

    expenseItems.forEach(item => {
        const description = item.getAttribute('data-description').toLowerCase();
        const date = item.getAttribute('data-date'); // Format YYYY-MM-DD

        const matchesSearch = description.includes(searchTerm);
        const matchesDate = filterDate === '' || date === filterDate;

        if (matchesSearch && matchesDate) {
            item.style.display = ''; // Reset display to default (flex)
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // Gérer l'état vide si aucun résultat
    const listContainer = document.querySelector('.expenses-list');
    let noResultsMsg = document.getElementById('no-results-message');

    if (visibleCount === 0) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'no-results-message';
            noResultsMsg.className = 'text-center py-4 text-muted';
            noResultsMsg.innerHTML = '<i class="fas fa-search mb-2" style="font-size: 2rem;"></i><p>Aucune dépense ne correspond à votre recherche.</p>';
            listContainer.appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = 'block';
    } else if (noResultsMsg) {
        noResultsMsg.style.display = 'none';
    }
}

if (searchInput) {
    searchInput.addEventListener('input', filterExpenses);
}

if (dateFilter) {
    dateFilter.addEventListener('change', filterExpenses);
}

