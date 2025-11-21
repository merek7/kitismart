// Budget Share Form JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');

    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        });
    }

    // Form submission
    const shareForm = document.getElementById('share-form');
    if (shareForm) {
        shareForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formMessage = document.getElementById('form-message');
            const submitBtn = shareForm.querySelector('button[type="submit"]');

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';

            // Get form data
            const formData = {
                csrf_token: shareForm.querySelector('input[name="csrf_token"]').value,
                password: document.getElementById('password').value,
                can_view: true, // Always true
                can_add: document.getElementById('can_add').checked,
                can_edit: document.getElementById('can_edit').checked,
                can_delete: document.getElementById('can_delete').checked,
                can_view_stats: document.getElementById('can_view_stats').checked,
                expires_at: document.getElementById('expires_at').value || null,
                max_uses: document.getElementById('max_uses').value || null
            };

            // Get budget ID from URL
            const budgetId = window.location.pathname.split('/')[2];

            try {
                const response = await fetch(`/budget/${budgetId}/share`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    // Show success modal with share URL
                    showSuccessModal(data.share.url);

                    // Reset form
                    shareForm.reset();

                    // Reload shares list after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    showMessage(formMessage, data.message, 'error');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showMessage(formMessage, 'Erreur lors de la création du partage', 'error');
            } finally {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-share-alt"></i> Créer le lien de partage';
            }
        });
    }

    // Copy link buttons
    document.querySelectorAll('.copy-link-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const token = this.dataset.token;
            const protocol = window.location.protocol;
            const host = window.location.host;
            const url = `${protocol}//${host}/budget/shared/${token}`;

            copyToClipboard(url, this);
        });
    });

    // Revoke buttons
    document.querySelectorAll('.revoke-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const shareId = this.dataset.shareId;

            if (!confirm('Êtes-vous sûr de vouloir révoquer ce partage ? Cette action est irréversible.')) {
                return;
            }

            const csrfToken = document.querySelector('input[name="csrf_token"]').value;

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
                    // Remove the share item from the list
                    const shareItem = this.closest('.share-item');
                    shareItem.style.opacity = '0';
                    setTimeout(() => {
                        shareItem.remove();

                        // Check if there are no more shares
                        const sharesList = document.querySelector('.shares-list');
                        if (sharesList && sharesList.children.length === 0) {
                            sharesList.innerHTML = `
                                <div class="no-shares">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Aucun partage actif pour ce budget</p>
                                </div>
                            `;
                        }
                    }, 300);
                } else {
                    alert('Erreur: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-ban"></i> Révoquer';
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la révocation du partage');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-ban"></i> Révoquer';
            }
        });
    });

    // Copy URL button in success modal
    const copyUrlBtn = document.getElementById('copy-url-btn');
    if (copyUrlBtn) {
        copyUrlBtn.addEventListener('click', function() {
            const shareUrl = document.getElementById('share-url').value;
            copyToClipboard(shareUrl, this);
        });
    }

    // Modal close buttons
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
            }
        });
    });

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });
});

// Show success modal with share URL
function showSuccessModal(url) {
    const modal = document.getElementById('success-modal');
    const urlInput = document.getElementById('share-url');

    if (modal && urlInput) {
        urlInput.value = url;
        modal.classList.add('active');
    }
}

// Show message
function showMessage(element, message, type) {
    element.textContent = message;
    element.className = `message ${type}`;
    element.style.display = 'flex';

    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}

// Copy to clipboard
function copyToClipboard(text, button) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copié !';
            button.classList.add('btn-success');
            button.classList.remove('btn-primary', 'btn-secondary', 'btn-outline-secondary');

            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                if (button.classList.contains('copy-url-btn')) {
                    button.classList.add('btn-primary');
                } else {
                    button.classList.add('btn-outline-secondary');
                }
            }, 2000);
        }).catch(err => {
            console.error('Erreur copie:', err);
            alert('Erreur lors de la copie');
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
            alert('Erreur lors de la copie');
        }

        document.body.removeChild(textArea);
    }
}
