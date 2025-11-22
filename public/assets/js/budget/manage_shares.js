// Manage Shares JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Copy link buttons
    document.querySelectorAll('.copy-link-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const url = this.dataset.url;
            copyToClipboard(url, this);
        });
    });

    // Revoke share buttons
    document.querySelectorAll('.revoke-share-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const shareId = this.dataset.shareId;
            const budgetName = this.dataset.budgetName;

            if (!confirm(`Êtes-vous sûr de vouloir révoquer l'accès à "${budgetName}" ?\n\nCette action est irréversible et toutes les personnes utilisant ce lien n'auront plus accès au budget.`)) {
                return;
            }

            const csrfToken = document.getElementById('csrf-token').value;

            try {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Révocation...';

                const response = await fetch(`/budget/shares/${shareId}/revoke`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ csrf_token: csrfToken })
                });

                const data = await response.json();

                if (data.success) {
                    // Mark the card as inactive
                    const shareCard = this.closest('.share-card');
                    shareCard.classList.add('share-inactive');

                    // Update the status badge
                    const statusBadge = shareCard.querySelector('.share-status-badge');
                    if (statusBadge) {
                        statusBadge.innerHTML = '<span class="badge badge-danger"><i class="fas fa-ban"></i> Révoqué</span>';
                    }

                    // Remove the footer with actions
                    const footer = shareCard.querySelector('.share-card-footer');
                    if (footer) {
                        footer.remove();
                    }

                    // Show success message
                    showNotification('Partage révoqué avec succès', 'success');

                    // Reload after 2 seconds to update stats
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showNotification('Erreur: ' + data.message, 'error');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-ban"></i> Révoquer l\'accès';
                }
            } catch (error) {
                console.error('Erreur:', error);
                showNotification('Erreur lors de la révocation du partage', 'error');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-ban"></i> Révoquer l\'accès';
            }
        });
    });
});

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
