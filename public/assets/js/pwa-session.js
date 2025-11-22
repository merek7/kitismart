// ===================================
// PWA SESSION MANAGEMENT
// Gestion de la session et communication avec le Service Worker
// ===================================

(function() {
    'use strict';

    // ===================================
    // Écouter les messages du Service Worker
    // ===================================
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            console.log('[PWA] Message reçu du SW:', event.data);

            switch (event.data.type) {
                case 'SESSION_EXPIRED':
                    handleSessionExpired(event.data.message);
                    break;

                case 'SAVE_OFFLINE_REQUEST':
                    saveOfflineRequest(event.data.data);
                    break;

                case 'SYNC_OFFLINE_DATA':
                    syncOfflineData();
                    break;
            }
        });
    }

    // ===================================
    // Gestion de l'expiration de session
    // ===================================
    function handleSessionExpired(message) {
        console.log('[PWA] Session expirée détectée');

        // Éviter les redirections multiples
        if (window.sessionExpiredHandled) {
            return;
        }
        window.sessionExpiredHandled = true;

        // Afficher un message à l'utilisateur
        showSessionExpiredModal(message || 'Votre session a expiré. Veuillez vous reconnecter.');
    }

    function showSessionExpiredModal(message) {
        // Supprimer tout modal existant
        const existingModal = document.getElementById('session-expired-modal');
        if (existingModal) {
            existingModal.remove();
        }

        // Créer le modal
        const modal = document.createElement('div');
        modal.id = 'session-expired-modal';
        modal.innerHTML = `
            <div class="session-modal-overlay">
                <div class="session-modal-content">
                    <div class="session-modal-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2>Session expirée</h2>
                    <p>${message}</p>
                    <button id="session-expired-login-btn" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Se reconnecter
                    </button>
                </div>
            </div>
        `;

        // Ajouter les styles
        const style = document.createElement('style');
        style.textContent = `
            .session-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                animation: fadeIn 0.3s ease;
            }

            .session-modal-content {
                background: white;
                padding: 2rem;
                border-radius: 16px;
                text-align: center;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                animation: slideUp 0.3s ease;
            }

            .session-modal-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #f59e0b, #d97706);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
            }

            .session-modal-icon i {
                font-size: 2.5rem;
                color: white;
            }

            .session-modal-content h2 {
                color: #1f2937;
                margin: 0 0 0.5rem;
                font-size: 1.5rem;
            }

            .session-modal-content p {
                color: #6b7280;
                margin: 0 0 1.5rem;
                font-size: 1rem;
            }

            .session-modal-content .btn {
                padding: 0.75rem 2rem;
                font-size: 1rem;
                border-radius: 8px;
                cursor: pointer;
                border: none;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                transition: transform 0.2s, box-shadow 0.2s;
            }

            .session-modal-content .btn-primary {
                background: linear-gradient(135deg, #0d9488, #0f766e);
                color: white;
            }

            .session-modal-content .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(13, 148, 136, 0.4);
            }

            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(modal);

        // Gestionnaire de clic sur le bouton
        document.getElementById('session-expired-login-btn').addEventListener('click', () => {
            // Nettoyer le cache et rediriger
            cleanupAndRedirect();
        });
    }

    async function cleanupAndRedirect() {
        try {
            // Notifier le service worker de nettoyer le cache
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'CLEAR_AUTH_CACHE'
                });
            }

            // Attendre un peu pour que le cache soit nettoyé
            await new Promise(resolve => setTimeout(resolve, 100));

        } catch (e) {
            console.error('[PWA] Erreur lors du nettoyage:', e);
        }

        // Rediriger vers la page de connexion
        window.location.href = '/login';
    }

    // ===================================
    // Gestion des requêtes hors ligne
    // ===================================
    const DB_NAME = 'KitiSmartOffline';
    const DB_VERSION = 1;
    const STORE_NAME = 'pendingRequests';

    function openDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                }
            };
        });
    }

    async function saveOfflineRequest(requestData) {
        try {
            const db = await openDB();
            const transaction = db.transaction(STORE_NAME, 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            store.add(requestData);
            console.log('[PWA] Requête sauvegardée pour sync ultérieure');
        } catch (e) {
            console.error('[PWA] Erreur sauvegarde offline:', e);
        }
    }

    async function syncOfflineData() {
        console.log('[PWA] Synchronisation des données hors ligne...');

        try {
            const db = await openDB();
            const transaction = db.transaction(STORE_NAME, 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            const allRequests = await new Promise((resolve, reject) => {
                const request = store.getAll();
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });

            if (allRequests.length === 0) {
                console.log('[PWA] Aucune donnée à synchroniser');
                return;
            }

            console.log(`[PWA] ${allRequests.length} requête(s) à synchroniser`);

            for (const reqData of allRequests) {
                try {
                    const response = await fetch(reqData.url, {
                        method: reqData.method,
                        headers: {
                            ...reqData.headers,
                            'X-Sync-Request': 'true'
                        },
                        body: reqData.body
                    });

                    if (response.ok) {
                        // Supprimer de IndexedDB si réussi
                        const delTransaction = db.transaction(STORE_NAME, 'readwrite');
                        delTransaction.objectStore(STORE_NAME).delete(reqData.id);
                        console.log('[PWA] Requête synchronisée:', reqData.url);
                    }
                } catch (e) {
                    console.error('[PWA] Erreur sync requête:', e);
                }
            }

            showSyncNotification(allRequests.length);

        } catch (e) {
            console.error('[PWA] Erreur sync offline data:', e);
        }
    }

    function showSyncNotification(count) {
        // Afficher un toast de notification
        const toast = document.createElement('div');
        toast.className = 'sync-toast';
        toast.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${count} donnée(s) synchronisée(s)</span>
        `;

        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ===================================
    // Vérifier la connectivité et synchroniser
    // ===================================
    window.addEventListener('online', () => {
        console.log('[PWA] Connexion rétablie - synchronisation...');
        syncOfflineData();
    });

    // ===================================
    // Interception des erreurs API
    // ===================================
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
        try {
            const response = await originalFetch.apply(this, args);

            // Vérifier si c'est une erreur d'authentification
            if (response.status === 401) {
                const url = typeof args[0] === 'string' ? args[0] : args[0].url;
                // Ne pas intercepter pour les pages d'auth
                if (!url.includes('/login') && !url.includes('/register')) {
                    console.log('[PWA] Erreur 401 détectée sur:', url);

                    // Vérifier si c'est une réponse JSON avec un message spécifique
                    const clonedResponse = response.clone();
                    try {
                        const data = await clonedResponse.json();
                        if (data.message && (data.message.includes('Non authentifié') || data.message.includes('Token invalide'))) {
                            handleSessionExpired(data.message);
                        }
                    } catch (e) {
                        // Pas JSON, peut-être une redirection HTML
                        handleSessionExpired('Votre session a expiré.');
                    }
                }
            }

            return response;
        } catch (error) {
            throw error;
        }
    };

    // ===================================
    // Fonction utilitaire de déconnexion propre
    // ===================================
    window.cleanLogout = async function() {
        try {
            // Notifier le service worker
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'LOGOUT'
                });
            }

            // Attendre que le cache soit nettoyé
            await new Promise(resolve => setTimeout(resolve, 200));

        } catch (e) {
            console.error('[PWA] Erreur lors de la déconnexion:', e);
        }

        // Rediriger vers logout
        window.location.href = '/logout';
    };

    console.log('[PWA] Session manager initialisé');
})();
