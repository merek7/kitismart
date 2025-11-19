/**
 * KitiSmart - Toast Notification System
 * Système de notifications élégantes et fluides
 */

class ToastManager {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Créer le conteneur de toasts s'il n'existe pas
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    }

    /**
     * Afficher une notification
     * @param {string} message - Message à afficher
     * @param {string} type - Type: success, error, warning, info
     * @param {number} duration - Durée en ms (0 = infini)
     */
    show(message, type = 'info', duration = 4000) {
        const toast = this.createToast(message, type);
        this.container.appendChild(toast);

        // Animation d'entrée
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Auto-dismiss
        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(toast);
            }, duration);
        }

        return toast;
    }

    createToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icon = this.getIcon(type);
        const closeBtn = '<button class="toast-close" aria-label="Fermer">&times;</button>';

        toast.innerHTML = `
            <div class="toast-icon">${icon}</div>
            <div class="toast-message">${message}</div>
            ${closeBtn}
        `;

        // Event listener pour fermer
        const closeButton = toast.querySelector('.toast-close');
        closeButton.addEventListener('click', () => {
            this.dismiss(toast);
        });

        // Fermer au clic sur le toast
        toast.addEventListener('click', () => {
            this.dismiss(toast);
        });

        return toast;
    }

    getIcon(type) {
        const icons = {
            success: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
            error: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            warning: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            info: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };
        return icons[type] || icons.info;
    }

    dismiss(toast) {
        toast.classList.add('removing');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    // Méthodes raccourcies
    success(message, duration = 4000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 4500) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 4000) {
        return this.show(message, 'info', duration);
    }

    // Notification de chargement
    loading(message = 'Chargement...') {
        const toast = this.createToast(message, 'info');
        toast.classList.add('toast-loading');

        // Ajouter un spinner
        const spinner = document.createElement('div');
        spinner.className = 'spinner spinner-sm';
        toast.querySelector('.toast-icon').innerHTML = '';
        toast.querySelector('.toast-icon').appendChild(spinner);

        // Retirer le bouton de fermeture
        toast.querySelector('.toast-close').style.display = 'none';

        this.container.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        return toast;
    }

    // Dismiss un toast de chargement et afficher un message
    updateLoading(loadingToast, message, type = 'success') {
        this.dismiss(loadingToast);
        return this.show(message, type);
    }
}

// Créer une instance globale
window.toast = new ToastManager();

// CSS pour les toasts
const toastStyles = document.createElement('style');
toastStyles.textContent = `
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        pointer-events: none;
    }

    .toast {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 300px;
        max-width: 500px;
        padding: 16px 20px;
        margin-bottom: 12px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        opacity: 0;
        transform: translateX(400px);
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        pointer-events: auto;
        cursor: pointer;
    }

    .toast.show {
        opacity: 1;
        transform: translateX(0);
    }

    .toast.removing {
        opacity: 0;
        transform: translateX(400px);
        transition: all 0.3s ease-in;
    }

    .toast-icon {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .toast-message {
        flex: 1;
        font-size: 14px;
        font-weight: 500;
        color: #333;
        line-height: 1.4;
    }

    .toast-close {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        border: none;
        background: transparent;
        font-size: 24px;
        line-height: 1;
        color: #999;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s;
    }

    .toast-close:hover {
        color: #333;
    }

    /* Types de toast */
    .toast-success {
        border-left: 4px solid #28a745;
    }

    .toast-success .toast-icon {
        color: #28a745;
    }

    .toast-error {
        border-left: 4px solid #dc3545;
    }

    .toast-error .toast-icon {
        color: #dc3545;
    }

    .toast-warning {
        border-left: 4px solid #ffc107;
    }

    .toast-warning .toast-icon {
        color: #ffc107;
    }

    .toast-info {
        border-left: 4px solid #17a2b8;
    }

    .toast-info .toast-icon {
        color: #17a2b8;
    }

    .toast-loading {
        pointer-events: none;
        cursor: default;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .toast-container {
            top: 10px;
            right: 10px;
            left: 10px;
        }

        .toast {
            min-width: 0;
            width: 100%;
        }
    }
`;
document.head.appendChild(toastStyles);
