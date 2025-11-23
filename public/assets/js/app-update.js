/**
 * App Update - Affiche les nouveautés après une mise à jour
 */
(function() {
    'use strict';

    // Ne pas exécuter si pas connecté
    if (!document.body.classList.contains('logged-in') && !document.querySelector('.dashboard-nav')) {
        return;
    }

    // Vérifier si une mise à jour doit être affichée
    async function checkForUpdate() {
        try {
            const response = await fetch('/app/update/check');
            const data = await response.json();

            if (data.show && data.update) {
                showUpdateModal(data.version, data.update);
            }
        } catch (error) {
            console.error('Erreur vérification mise à jour:', error);
        }
    }

    // Afficher le modal de mise à jour
    function showUpdateModal(version, update) {
        const featuresHtml = update.features.map(feature => `
            <div class="update-feature">
                <div class="update-feature-icon">
                    <i class="fas ${feature.icon}"></i>
                </div>
                <div class="update-feature-content">
                    <h4>${escapeHtml(feature.title)}</h4>
                    <p>${escapeHtml(feature.description)}</p>
                </div>
            </div>
        `).join('');

        const modalHtml = `
            <div class="update-modal-overlay" id="updateModalOverlay">
                <div class="update-modal">
                    <div class="update-modal-header">
                        <div class="update-badge">
                            <i class="fas fa-gift"></i>
                            <span>Nouveautés</span>
                        </div>
                        <h2>${escapeHtml(update.title)}</h2>
                        <p class="update-version">Version ${escapeHtml(version)}</p>
                    </div>
                    <div class="update-modal-body">
                        <div class="update-features">
                            ${featuresHtml}
                        </div>
                    </div>
                    <div class="update-modal-footer">
                        <button class="update-btn-primary" id="updateDismissBtn">
                            <i class="fas fa-check"></i> C'est compris !
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Animer l'apparition
        requestAnimationFrame(() => {
            document.getElementById('updateModalOverlay').classList.add('visible');
        });

        // Gérer la fermeture
        document.getElementById('updateDismissBtn').addEventListener('click', dismissUpdate);
        document.getElementById('updateModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                dismissUpdate();
            }
        });
    }

    // Fermer et marquer comme vu
    async function dismissUpdate() {
        const overlay = document.getElementById('updateModalOverlay');
        overlay.classList.remove('visible');

        setTimeout(() => {
            overlay.remove();
        }, 300);

        try {
            await fetch('/app/update/seen', { method: 'POST' });
        } catch (error) {
            console.error('Erreur marquage mise à jour:', error);
        }
    }

    // Utilitaire
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Vérifier après un court délai (laisser la page charger)
    setTimeout(checkForUpdate, 1500);
})();
