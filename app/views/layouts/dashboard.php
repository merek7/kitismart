<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dashboard - KitiSmart' ?></title>

    <!-- PWA Meta Tags -->
    <meta name="description" content="Application de gestion de budget personnel intelligente">
    <meta name="theme-color" content="#0d9488">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="KitiSmart">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/img/icons/icon-192x192.png">

    <!-- Charger le mode sombre immédiatement (éviter le flash) -->
    <script>
        (function() {
            const isDarkMode = localStorage.getItem('darkMode') === 'true';
            if (isDarkMode) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/dashboard/index.css">
    <?php if (!empty($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($style) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

</head>
<body>
    <!-- Navbar -->
    <nav class="dashboard-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <img src="/assets/img/logo.svg" alt="KitiSmart" class="nav-logo">
            </div>

            <!-- Bouton hamburger pour mobile -->
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <span class="nav-toggle-icon"></span>
            </button>

            <div class="nav-menu" id="navMenu">
                <a href="/dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="/budget/create" class="nav-link <?= $currentPage === 'budget' ? 'active' : '' ?>">
                    <i class="fas fa-wallet"></i> Budget
                </a>
                <a href="/budgets/history" class="nav-link <?= $currentPage === 'budgets' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i> Historique
                </a>
                <a href="/expenses/create" class="nav-link <?= $currentPage === 'expenses' ? 'active' : '' ?>">
                    <i class="fas fa-receipt"></i> Dépenses
                </a>
                <a href="/categories" class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>">
                    <i class="fas fa-tags"></i> Catégories
                </a>
                <a href="/expenses/recurrences" class="nav-link <?= $currentPage === 'recurrences' ? 'active' : '' ?>">
                    <i class="fas fa-sync-alt"></i> Récurrences
                </a>
                <a href="/budget/shares/manage" class="nav-link <?= $currentPage === 'shares' ? 'active' : '' ?>">
                    <i class="fas fa-share-nodes"></i> Partages
                </a>
                <a href="/notifications/settings" class="nav-link <?= $currentPage === 'notifications' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i> Notifications
                </a>
                <a href="/settings" class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> Paramètres
                </a>
            </div>
            <div class="nav-user">
                <div class="user-menu">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?></span>
                    <a href="/logout" id="logoutBtn" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="dashboard-main">
        <?= $content ?>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- PWA Offline Scripts -->
    <script src="/assets/js/offline-storage.js?v=<?= time() ?>"></script>
    <script src="/assets/js/sync-manager.js?v=<?= time() ?>"></script>
    <script src="/assets/js/offline-forms.js?v=<?= time() ?>"></script>

    <!-- Script pour le toggle du navbar mobile -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navToggle = document.getElementById('navToggle');
            const navMenu = document.getElementById('navMenu');

            if (navToggle && navMenu) {
                navToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('nav-menu-active');
                    navToggle.classList.toggle('active');
                    document.body.classList.toggle('nav-open');
                });

                // Fermer le menu quand on clique sur un lien
                const navLinks = navMenu.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        navMenu.classList.remove('nav-menu-active');
                        navToggle.classList.remove('active');
                        document.body.classList.remove('nav-open');
                    });
                });

                // Fermer le menu si on clique en dehors
                document.addEventListener('click', function(event) {
                    if (!navMenu.contains(event.target) && !navToggle.contains(event.target)) {
                        navMenu.classList.remove('nav-menu-active');
                        navToggle.classList.remove('active');
                        document.body.classList.remove('nav-open');
                    }
                });
            }
        });
    </script>

    <?php
    error_log("Scripts disponibles dans le layout: " . print_r($pageScripts ?? [], true));
    if (!empty($pageScripts)): ?>
        <!-- Scripts spécifiques -->
        <?php foreach ($pageScripts as $script): ?>
            <script src="/assets/js/<?= htmlspecialchars($script) ?>" defer></script>
            <?php error_log("Chargement du script: " . $script); ?>
        <?php endforeach; ?>
    <?php else: ?>
        <?php error_log("Aucun script spécifique à charger"); ?>
    <?php endif; ?>

    <!-- PWA Install Script -->
    <script src="/assets/js/pwa-install.js"></script>

    <!-- ================================
         PWA Service Worker Registration
         ================================ -->
    <script>
        // Enregistrer le Service Worker pour PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const registration = await navigator.serviceWorker.register('/sw.js');
                    console.log('✅ Service Worker enregistré avec succès:', registration.scope);

                    // Initialiser le gestionnaire de synchronisation
                    if (window.syncManager) {
                        await window.syncManager.init();
                        console.log('✅ Sync Manager initialisé');
                    }

                    // Demander la permission pour les notifications
                    if (window.syncManager) {
                        await window.syncManager.requestNotificationPermission();
                    }

                    // Vérifier les mises à jour toutes les heures
                    setInterval(() => {
                        registration.update();
                    }, 60 * 60 * 1000);

                    // Vérifier les mises à jour du SW
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('[PWA] Nouvelle version disponible');
                                if (confirm('Une nouvelle version est disponible. Voulez-vous actualiser ?')) {
                                    newWorker.postMessage({ type: 'SKIP_WAITING' });
                                    window.location.reload();
                                }
                            }
                        });
                    });
                } catch (error) {
                    console.error('❌ Erreur d\'enregistrement du Service Worker:', error);
                }
            });

            // Écouter les mises à jour du Service Worker
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('🔄 Nouveau Service Worker activé');
            });
        }

        // Détecter le mode offline/online (géré par sync-manager.js maintenant)
        window.addEventListener('online', () => {
            console.log('🌐 Connexion rétablie');
        });

        window.addEventListener('offline', () => {
            console.log('📡 Mode hors ligne activé');
        });
    </script>

    <!-- Badge de synchronisation -->
    <div id="sync-badge" style="display: none;">
        <i class="fas fa-sync-alt"></i>
        <span class="sync-text">Synchroniser</span>
        <span class="sync-count">0</span>
    </div>

    <!-- Listener pour le badge -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const syncBadge = document.getElementById('sync-badge');
            if (syncBadge && window.syncManager) {
                // Mettre à jour l'affichage du badge
                const updateBadgeDisplay = async () => {
                    if (window.offlineStorage) {
                        const counts = await window.offlineStorage.getPendingCount();
                        const syncCount = syncBadge.querySelector('.sync-count');
                        const syncText = syncBadge.querySelector('.sync-text');

                        // Vérifier que les éléments existent
                        if (!syncCount || !syncText) {
                            console.warn('[Badge] Éléments du badge introuvables');
                            return;
                        }

                        if (counts.total > 0) {
                            syncBadge.style.display = 'flex';
                            syncCount.textContent = counts.total;

                            // Texte dynamique selon le nombre
                            if (counts.total === 1) {
                                syncText.textContent = 'Synchroniser';
                            } else {
                                syncText.textContent = 'Synchroniser';
                            }
                        } else {
                            syncBadge.style.display = 'none';
                        }
                    }
                };

                // Clic sur le badge
                syncBadge.addEventListener('click', async function() {
                    console.log('[Badge] Synchronisation manuelle déclenchée');

                    // Animation de synchronisation
                    this.classList.add('syncing');
                    const syncText = this.querySelector('.sync-text');

                    // Vérifier que l'élément existe
                    if (!syncText) {
                        console.warn('[Badge] Élément sync-text introuvable');
                        return;
                    }

                    const originalText = syncText.textContent;
                    syncText.textContent = 'Synchronisation...';

                    if (window.syncManager) {
                        await window.syncManager.syncAll();
                    }

                    // Remettre le texte original et supprimer l'animation
                    this.classList.remove('syncing');
                    syncText.textContent = originalText;
                    await updateBadgeDisplay();
                });

                // Mettre à jour au chargement
                setTimeout(updateBadgeDisplay, 500);

                // Mettre à jour périodiquement
                setInterval(updateBadgeDisplay, 3000);
            }

            // Forcer la vérification de la connexion et la synchronisation au chargement
            setTimeout(async () => {
                if (window.syncManager && window.offlineStorage) {
                    console.log('[Dashboard] Vérification des données en attente...');
                    const counts = await window.offlineStorage.getPendingCount();
                    console.log('[Dashboard] Éléments en attente:', counts);

                    if (navigator.onLine && counts.total > 0) {
                        console.log('[Dashboard] Connexion active + données en attente -> Synchronisation');
                        await window.syncManager.syncAll();
                    }
                }
            }, 1000);
        });
    </script>

    <!-- Style pour le badge de synchronisation amélioré -->
    <style>
        #sync-badge {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            box-shadow: 0 8px 24px rgba(13, 148, 136, 0.4);
            z-index: 9999;
            transition: all 0.3s ease;
            animation: pulse 2s infinite;
        }

        #sync-badge:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 32px rgba(13, 148, 136, 0.5);
        }

        #sync-badge i {
            font-size: 1.2rem;
        }

        #sync-badge.syncing i {
            animation: fa-spin 1s infinite linear;
        }

        #sync-badge .sync-count {
            background: white;
            color: var(--primary-color);
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
            min-width: 24px;
            text-align: center;
        }

        #sync-badge .sync-text {
            font-size: 0.9rem;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 8px 24px rgba(13, 148, 136, 0.4);
            }
            50% {
                box-shadow: 0 8px 24px rgba(13, 148, 136, 0.6), 0 0 0 8px rgba(13, 148, 136, 0.1);
            }
        }

        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            #sync-badge {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
                bottom: 15px;
                right: 15px;
            }

            #sync-badge .sync-text {
                display: none;
            }
        }
    </style>
</body>
</html> 