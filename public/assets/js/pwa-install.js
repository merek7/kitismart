// ===================================
// PWA INSTALLATION PROMPT
// ===================================

let deferredPrompt = null;

// Écouter l'événement beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
    console.log('💡 PWA peut être installée');

    // Empêcher le mini-infobar automatique de Chrome
    e.preventDefault();

    // Stocker l'événement pour l'utiliser plus tard
    deferredPrompt = e;

    // Afficher le bouton d'installation personnalisé
    showInstallButton();
});

// Créer et afficher le bouton d'installation
function showInstallButton() {
    // Vérifier si le bouton existe déjà
    if (document.getElementById('pwa-install-btn')) {
        return;
    }

    // Créer le bouton
    const installBtn = document.createElement('button');
    installBtn.id = 'pwa-install-btn';
    installBtn.className = 'pwa-install-btn';
    installBtn.innerHTML = `
        <i class="fas fa-download"></i>
        <span>Installer l'app</span>
        <i class="fas fa-times pwa-install-close"></i>
    `;

    // Ajouter le bouton au body
    document.body.appendChild(installBtn);

    // Animation d'apparition
    setTimeout(() => {
        installBtn.classList.add('show');
    }, 1000);

    // Gérer le clic sur le bouton d'installation
    installBtn.addEventListener('click', async (e) => {
        if (e.target.classList.contains('pwa-install-close')) {
            // Bouton fermer
            hideInstallButton();
            localStorage.setItem('pwa-install-dismissed', 'true');
            return;
        }

        if (!deferredPrompt) {
            return;
        }

        // Afficher le prompt d'installation
        deferredPrompt.prompt();

        // Attendre la réponse de l'utilisateur
        const { outcome } = await deferredPrompt.userChoice;

        console.log(`PWA installation: ${outcome}`);

        if (outcome === 'accepted') {
            console.log('✅ PWA installée avec succès');
        } else {
            console.log('❌ Installation PWA refusée');
        }

        // Nettoyer
        deferredPrompt = null;
        hideInstallButton();
    });

    // Ne pas réafficher si déjà fermé
    const dismissed = localStorage.getItem('pwa-install-dismissed');
    if (dismissed === 'true') {
        hideInstallButton();
    }
}

function hideInstallButton() {
    const btn = document.getElementById('pwa-install-btn');
    if (btn) {
        btn.classList.remove('show');
        setTimeout(() => {
            btn.remove();
        }, 300);
    }
}

// Écouter l'événement d'installation réussie
window.addEventListener('appinstalled', () => {
    console.log('✅ PWA installée sur l\'appareil');
    hideInstallButton();

    // Afficher une notification de succès
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('KitiSmart installé!', {
            body: 'L\'application est maintenant disponible sur votre écran d\'accueil',
            icon: '/assets/img/icons/icon-192x192.png',
            badge: '/assets/img/icons/icon-96x96.png'
        });
    }
});

// Détecter si l'app est déjà installée
window.addEventListener('load', () => {
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        console.log('✅ PWA déjà installée et en mode standalone');
    }
});
