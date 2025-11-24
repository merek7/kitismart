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

            if (data.show && data.updates) {
                showUpdateModal(data.updates);
            }
        } catch (error) {
            console.error('Erreur vérification mise à jour:', error);
        }
    }

    // Afficher le modal de mise à jour (plusieurs versions)
    function showUpdateModal(updates) {
        // Convertir l'objet en tableau et trier par version décroissante
        const versionsArray = Object.entries(updates).sort((a, b) => {
            return b[0].localeCompare(a[0], undefined, { numeric: true });
        });

        let versionsHtml = '';

        versionsArray.forEach(([version, update], index) => {
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

            versionsHtml += `
                <div class="update-version-section ${index > 0 ? 'update-version-older' : ''}">
                    <div class="update-version-header">
                        <h3>${escapeHtml(update.title)}</h3>
                        <span class="update-version-badge">v${escapeHtml(version)}</span>
                    </div>
                    <div class="update-features">
                        ${featuresHtml}
                    </div>
                </div>
            `;
        });

        const latestVersion = versionsArray[0][0];
        const updateCount = versionsArray.length;
        const headerText = updateCount > 1
            ? `${updateCount} mises à jour`
            : 'Nouvelle mise à jour';

        const modalHtml = `
            <div class="update-modal-overlay" id="updateModalOverlay">
                <div class="update-modal ${updateCount > 1 ? 'update-modal-multi' : ''}">
                    <div class="update-modal-header">
                        <div class="update-badge">
                            <i class="fas fa-gift"></i>
                            <span>Nouveautés</span>
                        </div>
                        <h2>${headerText}</h2>
                        <p class="update-version">Version actuelle : ${escapeHtml(latestVersion)}</p>
                    </div>
                    <div class="update-modal-body">
                        ${versionsHtml}
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
