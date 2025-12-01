// Manage Shares JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.getElementById('csrf-token').value;

    // Copy link buttons
    document.querySelectorAll('.copy-link-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const url = this.dataset.url;
            copyToClipboard(url, this);
        });
    });

    // QR Code buttons
    document.querySelectorAll('.qr-code-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const shareId = this.dataset.shareId;
            const shareUrl = this.dataset.shareUrl;
            
            document.getElementById('qr-share-id').value = shareId;
            document.getElementById('qr-share-url').value = shareUrl;
            document.getElementById('qr-url-display').value = shareUrl;
            
            // Reset options
            document.getElementById('qr-size').value = '300';
            document.getElementById('qr-color').value = '#0d9488';
            document.getElementById('qr-bg-color').value = '#ffffff';
            
            // Load QR with defaults
            updateQRPreview();
            openModal('qr-modal');
        });
    });
    
    // QR customization listeners
    document.getElementById('qr-size')?.addEventListener('change', updateQRPreview);
    document.getElementById('qr-color')?.addEventListener('input', updateQRPreview);
    document.getElementById('qr-bg-color')?.addEventListener('input', updateQRPreview);
    
    // Download QR buttons
    document.getElementById('download-qr-png')?.addEventListener('click', function() {
        downloadQR('png');
    });
    
    document.getElementById('download-qr-svg')?.addEventListener('click', function() {
        downloadQR('svg');
    });
    
    // Copy share URL from QR modal
    document.getElementById('copy-share-url')?.addEventListener('click', function() {
        const url = document.getElementById('qr-share-url').value;
        copyToClipboard(url, this);
    });

    // View logs buttons
    document.querySelectorAll('.view-logs-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const shareId = this.dataset.shareId;
            openModal('logs-modal');
            await loadLogs(shareId);
        });
    });

    // Edit share buttons
    document.querySelectorAll('.edit-share-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const shareId = this.dataset.shareId;
            const shareName = this.dataset.shareName;
            const permissions = JSON.parse(this.dataset.permissions || '{}');
            const expiresAt = this.dataset.expiresAt;
            const maxUses = this.dataset.maxUses;

            document.getElementById('edit-share-id').value = shareId;
            document.getElementById('edit-share-name').value = shareName || '';
            document.getElementById('edit-can-add').checked = permissions.can_add || false;
            document.getElementById('edit-can-edit').checked = permissions.can_edit || false;
            document.getElementById('edit-can-delete').checked = permissions.can_delete || false;
            document.getElementById('edit-can-view-stats').checked = permissions.can_view_stats || false;
            
            if (expiresAt) {
                const date = new Date(expiresAt);
                document.getElementById('edit-expires-at').value = date.toISOString().slice(0, 16);
            } else {
                document.getElementById('edit-expires-at').value = '';
            }
            
            document.getElementById('edit-max-uses').value = maxUses || '';

            openModal('edit-share-modal');
        });
    });

    // Save share button
    document.getElementById('save-share-btn').addEventListener('click', async function() {
        const shareId = document.getElementById('edit-share-id').value;
        const data = {
            csrf_token: csrfToken,
            name: document.getElementById('edit-share-name').value,
            permissions: {
                can_view: true,
                can_add: document.getElementById('edit-can-add').checked,
                can_edit: document.getElementById('edit-can-edit').checked,
                can_delete: document.getElementById('edit-can-delete').checked,
                can_view_stats: document.getElementById('edit-can-view-stats').checked
            },
            expires_at: document.getElementById('edit-expires-at').value || null,
            max_uses: document.getElementById('edit-max-uses').value || null
        };

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const response = await fetch(`/budget/shares/${shareId}/update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Partage mis à jour', 'success');
                closeModal('edit-share-modal');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showNotification('Erreur: ' + result.message, 'error');
            }
        } catch (error) {
            showNotification('Erreur lors de la mise à jour', 'error');
        }

        this.disabled = false;
        this.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
    });

    // Regenerate password buttons
    document.querySelectorAll('.regenerate-password-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const shareId = this.dataset.shareId;
            document.getElementById('password-share-id').value = shareId;
            document.getElementById('new-password').value = '';
            document.getElementById('confirm-password').value = '';
            openModal('password-modal');
        });
    });

    // Save password button
    document.getElementById('save-password-btn').addEventListener('click', async function() {
        const shareId = document.getElementById('password-share-id').value;
        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;

        if (newPassword.length < 6) {
            showNotification('Le mot de passe doit contenir au moins 6 caractères', 'error');
            return;
        }

        if (newPassword !== confirmPassword) {
            showNotification('Les mots de passe ne correspondent pas', 'error');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const response = await fetch(`/budget/shares/${shareId}/regenerate-password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: csrfToken, password: newPassword })
            });

            const result = await response.json();

            if (result.success) {
                showNotification('Mot de passe changé avec succès', 'success');
                closeModal('password-modal');
            } else {
                showNotification('Erreur: ' + result.message, 'error');
            }
        } catch (error) {
            showNotification('Erreur lors du changement', 'error');
        }

        this.disabled = false;
        this.innerHTML = '<i class="fas fa-key"></i> Changer';
    });

    // Revoke share buttons
    document.querySelectorAll('.revoke-share-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const shareId = this.dataset.shareId;
            const budgetName = this.dataset.budgetName;

            if (!confirm(`Êtes-vous sûr de vouloir révoquer l'accès à "${budgetName}" ?\n\nCette action est irréversible.`)) {
                return;
            }

            try {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                const response = await fetch(`/budget/shares/${shareId}/revoke`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: csrfToken })
                });

                const data = await response.json();

                if (data.success) {
                    showNotification('Partage révoqué avec succès', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification('Erreur: ' + data.message, 'error');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-ban"></i>';
                }
            } catch (error) {
                showNotification('Erreur lors de la révocation', 'error');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-ban"></i>';
            }
        });
    });

    // Close modal buttons
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
        });
    });

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    });
});

// Load logs for a share
async function loadLogs(shareId, page = 1) {
    const container = document.getElementById('logs-container');
    container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';

    try {
        const response = await fetch(`/budget/shares/${shareId}/logs?page=${page}`);
        const data = await response.json();

        if (data.success) {
            if (data.logs.length === 0) {
                container.innerHTML = '<div class="text-center text-muted">Aucune activité enregistrée</div>';
                return;
            }

            let html = '<div class="logs-list">';
            data.logs.forEach(log => {
                const date = new Date(log.created_at);
                const formattedDate = date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                const icon = getActionIcon(log.action);
                const label = getActionLabel(log.action);
                const guestName = log.metadata?.guest_name || '';

                html += `
                    <div class="log-item ${log.success ? '' : 'log-failed'}">
                        <div class="log-icon"><i class="fas ${icon}"></i></div>
                        <div class="log-content">
                            <div class="log-action">${label} ${guestName ? '<span class="log-guest">(' + guestName + ')</span>' : ''}</div>
                            <div class="log-meta">
                                <span><i class="fas fa-clock"></i> ${formattedDate}</span>
                                ${log.ip_address ? '<span><i class="fas fa-globe"></i> ' + log.ip_address + '</span>' : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            // Pagination
            if (data.pagination.total_pages > 1) {
                html += '<div class="logs-pagination">';
                if (data.pagination.current_page > 1) {
                    html += `<button class="btn btn-sm btn-outline-secondary" onclick="loadLogs(${shareId}, ${data.pagination.current_page - 1})">Précédent</button>`;
                }
                html += `<span>Page ${data.pagination.current_page}/${data.pagination.total_pages}</span>`;
                if (data.pagination.current_page < data.pagination.total_pages) {
                    html += `<button class="btn btn-sm btn-outline-secondary" onclick="loadLogs(${shareId}, ${data.pagination.current_page + 1})">Suivant</button>`;
                }
                html += '</div>';
            }

            container.innerHTML = html;
        } else {
            container.innerHTML = '<div class="alert alert-danger">Erreur: ' + data.message + '</div>';
        }
    } catch (error) {
        container.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement</div>';
    }
}

function getActionIcon(action) {
    const icons = {
        'access_attempt': 'fa-sign-in-alt',
        'access_success': 'fa-check-circle',
        'access_denied': 'fa-times-circle',
        'expense_created': 'fa-plus-circle',
        'expense_updated': 'fa-edit',
        'expense_deleted': 'fa-trash',
        'share_revoked': 'fa-ban',
        'password_regenerated': 'fa-key'
    };
    return icons[action] || 'fa-info-circle';
}

function getActionLabel(action) {
    const labels = {
        'access_attempt': 'Tentative d\'accès',
        'access_success': 'Accès réussi',
        'access_denied': 'Accès refusé',
        'expense_created': 'Dépense créée',
        'expense_updated': 'Dépense modifiée',
        'expense_deleted': 'Dépense supprimée',
        'share_revoked': 'Partage révoqué',
        'password_regenerated': 'Mot de passe changé'
    };
    return labels[action] || action;
}

function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Update QR code preview (utilise SVG pour la preview, plus léger)
function updateQRPreview() {
    const shareId = document.getElementById('qr-share-id').value;
    const size = document.getElementById('qr-size').value;
    const color = document.getElementById('qr-color').value.replace('#', '');
    const bgColor = document.getElementById('qr-bg-color').value.replace('#', '');
    
    const qrImage = document.getElementById('qr-image');
    qrImage.src = `/budget/shares/${shareId}/qrcode?size=${size}&color=${color}&bg=${bgColor}&format=svg&t=${Date.now()}`;
}

// Download QR code
function downloadQR(format) {
    const shareId = document.getElementById('qr-share-id').value;
    const size = document.getElementById('qr-size').value;
    const color = document.getElementById('qr-color').value.replace('#', '');
    const bgColor = document.getElementById('qr-bg-color').value.replace('#', '');
    
    window.location.href = `/budget/shares/${shareId}/qrcode?size=${size}&color=${color}&bg=${bgColor}&format=${format}&download=1`;
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
    document.body.style.overflow = '';
}

// Copy to clipboard function
function copyToClipboard(text, button) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copié !';
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-secondary', 'btn-primary');

            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        }).catch(err => {
            console.error('Erreur copie:', err);
            showNotification('Erreur lors de la copie', 'error');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.select();

        try {
            document.execCommand('copy');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copié !';

            setTimeout(() => {
                button.innerHTML = originalHTML;
            }, 2000);
        } catch (err) {
            console.error('Erreur copie:', err);
            showNotification('Erreur lors de la copie', 'error');
        }

        document.body.removeChild(textArea);
    }
}

// Show notification
function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
    `;

    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
    `;

    // Add to body
    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Add animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }

    .notification-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }
`;
document.head.appendChild(style);
