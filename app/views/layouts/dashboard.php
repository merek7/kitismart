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
    <link rel="stylesheet" href="<?= \App\Core\Config::asset('/assets/css/dashboard/index.css') ?>">
    <?php if (!empty($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="<?= \App\Core\Config::asset('/assets/css/' . htmlspecialchars($style)) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Shepherd.js CSS pour l'onboarding -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/css/shepherd.css" />
    <link rel="stylesheet" href="<?= \App\Core\Config::asset('/assets/css/onboarding/onboarding.css') ?>" />

    <!-- App Update Modal -->
    <link rel="stylesheet" href="<?= \App\Core\Config::asset('/assets/css/app-update.css') ?>" />

</head>
<body>
    <!-- Navbar -->
    <nav class="dashboard-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <img src="/assets/img/logo.svg" alt="KitiSmart" class="nav-logo">
            </div>

            <!-- Sélecteur de budget -->
            <div class="budget-switcher" id="budgetSwitcher">
                <button class="budget-switcher-btn" id="budgetSwitcherBtn" title="Changer de budget">
                    <span class="budget-color-dot" id="currentBudgetColor"></span>
                    <span class="budget-name" id="currentBudgetName">Chargement...</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="budget-switcher-dropdown" id="budgetSwitcherDropdown">
                    <div class="budget-switcher-header">
                        <span>Mes budgets</span>
                    </div>
                    <div class="budget-list" id="budgetList">
                        <!-- Chargé dynamiquement -->
                    </div>
                    <div class="budget-switcher-footer">
                        <a href="/budget/create" class="add-budget-btn">
                            <i class="fas fa-plus"></i> Nouveau budget
                        </a>
                    </div>
                </div>
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
                <a href="/expenses/create" class="nav-link <?= $currentPage === 'expenses' ? 'active' : '' ?>">
                    <i class="fas fa-receipt"></i> Dépenses
                </a>
                <a href="/categories" class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>">
                    <i class="fas fa-tags"></i> Catégories
                </a>

                <!-- Menu déroulant "Plus" (visible uniquement en desktop) -->
                <div class="nav-dropdown nav-desktop-only">
                    <button class="nav-link nav-dropdown-toggle <?= in_array($currentPage, ['budgets', 'comparison', 'savings', 'recurrences', 'shares', 'notifications', 'settings']) ? 'active' : '' ?>">
                        <i class="fas fa-ellipsis-h"></i> Plus <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="nav-dropdown-menu">
                        <a href="/budgets/history" class="dropdown-item <?= $currentPage === 'budgets' ? 'active' : '' ?>">
                            <i class="fas fa-history"></i> Historique
                        </a>
                        <a href="/budget/comparison" class="dropdown-item <?= $currentPage === 'comparison' ? 'active' : '' ?>">
                            <i class="fas fa-balance-scale"></i> Comparaison
                        </a>
                        <a href="/savings/goals" class="dropdown-item <?= $currentPage === 'savings' ? 'active' : '' ?>">
                            <i class="fas fa-bullseye"></i> Objectifs d'épargne
                        </a>
                        <a href="/expenses/recurrences" class="dropdown-item <?= $currentPage === 'recurrences' ? 'active' : '' ?>">
                            <i class="fas fa-sync-alt"></i> Récurrences
                        </a>
                        <a href="/budget/shares/manage" class="dropdown-item <?= $currentPage === 'shares' ? 'active' : '' ?>">
                            <i class="fas fa-share-nodes"></i> Partages
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="/notifications/settings" class="dropdown-item <?= $currentPage === 'notifications' ? 'active' : '' ?>">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                        <a href="/settings" class="dropdown-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                            <i class="fas fa-cog"></i> Paramètres
                        </a>
                        <a href="#" id="restart-onboarding" class="dropdown-item">
                            <i class="fas fa-graduation-cap"></i> Tour guidé
                        </a>
                        <?php if (($_ENV['APP_ENV'] ?? 'prod') === 'dev'): ?>
                            <div class="dropdown-divider"></div>
                            <a href="/admin/email-test" class="dropdown-item <?= $currentPage === 'email-test' ? 'active' : '' ?>" style="color: #facc15;">
                                <i class="fas fa-envelope"></i> Test Emails
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Liens directs (visible uniquement en mobile) -->
                <a href="/budgets/history" class="nav-link nav-mobile-only <?= $currentPage === 'budgets' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i> Historique
                </a>
                <a href="/budget/comparison" class="nav-link nav-mobile-only <?= $currentPage === 'comparison' ? 'active' : '' ?>">
                    <i class="fas fa-balance-scale"></i> Comparaison
                </a>
                <a href="/savings/goals" class="nav-link nav-mobile-only <?= $currentPage === 'savings' ? 'active' : '' ?>">
                    <i class="fas fa-bullseye"></i> Objectifs
                </a>
                <a href="/expenses/recurrences" class="nav-link nav-mobile-only <?= $currentPage === 'recurrences' ? 'active' : '' ?>">
                    <i class="fas fa-sync-alt"></i> Récurrences
                </a>
                <a href="/budget/shares/manage" class="nav-link nav-mobile-only <?= $currentPage === 'shares' ? 'active' : '' ?>">
                    <i class="fas fa-share-nodes"></i> Partages
                </a>
                <a href="/notifications/settings" class="nav-link nav-mobile-only <?= $currentPage === 'notifications' ? 'active' : '' ?>">
                    <i class="fas fa-bell"></i> Notifications
                </a>
                <a href="/settings" class="nav-link nav-mobile-only <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> Paramètres
                </a>
                <a href="#" id="restart-onboarding-mobile" class="nav-link nav-mobile-only">
                    <i class="fas fa-graduation-cap"></i> Tour guidé
                </a>

                <!-- User menu (visible en mobile dans le menu) -->
                <div class="nav-user nav-user-mobile">
                    <div class="user-menu">
                        <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?></span>
                        <a href="#" id="logoutBtnMobile" class="btn-logout" onclick="event.preventDefault(); if(window.cleanLogout) window.cleanLogout(); else window.location.href='/logout';">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </div>
                </div>
            </div>

            <!-- User menu desktop (hors du nav-menu) -->
            <div class="nav-user nav-user-desktop">
                <div class="user-menu">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?></span>
                    <a href="#" id="logoutBtn" class="btn-logout" onclick="event.preventDefault(); if(window.cleanLogout) window.cleanLogout(); else window.location.href='/logout';">
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

    <!-- Formatage des montants avec séparateur de milliers -->
    <script src="/assets/js/amount-formatter.js?v=<?= time() ?>"></script>

    <!-- Budget Switcher -->
    <script src="/assets/js/budget-switcher.js?v=<?= time() ?>"></script>

    <!-- App Update Notification -->
    <script src="/assets/js/app-update.js?v=<?= time() ?>"></script>

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
                const navLinks = navMenu.querySelectorAll('.nav-link:not(.nav-dropdown-toggle)');
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

            // Menu déroulant "Plus"
            const dropdownToggle = document.querySelector('.nav-dropdown-toggle');
            const dropdown = document.querySelector('.nav-dropdown');

            if (dropdownToggle && dropdown) {
                dropdownToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdown.classList.toggle('open');
                });

                // Fermer le dropdown si on clique en dehors
                document.addEventListener('click', function(event) {
                    if (!dropdown.contains(event.target)) {
                        dropdown.classList.remove('open');
                    }
                });

                // Fermer le dropdown quand on clique sur un item
                const dropdownItems = dropdown.querySelectorAll('.dropdown-item');
                dropdownItems.forEach(item => {
                    item.addEventListener('click', function() {
                        dropdown.classList.remove('open');
                    });
                });
            }

            // Bouton "Tour guidé" - Desktop et Mobile
            const restartOnboardingBtn = document.getElementById('restart-onboarding');
            const restartOnboardingBtnMobile = document.getElementById('restart-onboarding-mobile');

            function restartOnboarding(e) {
                e.preventDefault();

                // Afficher un message de chargement
                const loadingToast = document.createElement('div');
                loadingToast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #0d9488; color: white; padding: 1rem 1.5rem; border-radius: 8px; z-index: 10000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);';
                loadingToast.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement du tour guidé...';
                document.body.appendChild(loadingToast);

                // Reset l'onboarding via API
                fetch('/api/onboarding/reset', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Supprimer le toast de chargement
                        loadingToast.remove();

                        // Rediriger vers le dashboard si on n'y est pas déjà
                        if (window.location.pathname !== '/dashboard' && window.location.pathname !== '/') {
                            window.location.href = '/dashboard';
                        } else {
                            // Si on est déjà sur le dashboard, recharger la page
                            window.location.reload();
                        }
                    } else {
                        loadingToast.remove();
                        alert('Erreur lors du reset de l\'onboarding');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    loadingToast.remove();
                    alert('Erreur lors du reset de l\'onboarding');
                });
            }

            if (restartOnboardingBtn) {
                restartOnboardingBtn.addEventListener('click', restartOnboarding);
            }
            if (restartOnboardingBtnMobile) {
                restartOnboardingBtnMobile.addEventListener('click', restartOnboarding);
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

    <!-- PWA Session Management -->
    <script src="/assets/js/pwa-session.js"></script>

    <!-- Onboarding Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/js/shepherd.min.js"></script>
    <?php if (!empty($onboarding) && !empty($onboarding['stepsToShow'])): ?>
        <script>
            window.onboardingConfig = <?= json_encode($onboarding) ?>;
        </script>
        <script src="/assets/js/onboarding/onboarding.js"></script>
    <?php endif; ?>

    <!-- ================================
         PWA Service Worker Registration (Optionnel)
         ================================ -->
    <script>
        // Gestion du mode hors ligne optionnel
        const PWAManager = {
            // Vérifie si le mode hors ligne est activé
            isOfflineModeEnabled() {
                return localStorage.getItem('pwa_offline_enabled') === 'true';
            },

            // Vérifie si on est sur mobile
            isMobile() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            },

            // Active le mode hors ligne
            async enableOfflineMode() {
                if (!('serviceWorker' in navigator)) {
                    alert('Votre navigateur ne supporte pas le mode hors ligne.');
                    return false;
                }

                try {
                    const registration = await navigator.serviceWorker.register('/sw.js');
                    console.log('✅ Service Worker enregistré avec succès:', registration.scope);
                    localStorage.setItem('pwa_offline_enabled', 'true');

                    // Initialiser le gestionnaire de synchronisation
                    if (window.syncManager) {
                        await window.syncManager.init();
                    }

                    // Gérer les mises à jour
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

                    this.updateUI();
                    return true;
                } catch (error) {
                    console.error('❌ Erreur d\'enregistrement du Service Worker:', error);
                    return false;
                }
            },

            // Désactive le mode hors ligne
            async disableOfflineMode() {
                try {
                    // Désenregistrer tous les service workers
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    for (const registration of registrations) {
                        await registration.unregister();
                    }

                    // Supprimer tous les caches
                    const cacheNames = await caches.keys();
                    await Promise.all(cacheNames.map(name => caches.delete(name)));

                    localStorage.setItem('pwa_offline_enabled', 'false');
                    console.log('✅ Mode hors ligne désactivé');

                    this.updateUI();
                    return true;
                } catch (error) {
                    console.error('❌ Erreur lors de la désactivation:', error);
                    return false;
                }
            },

            // Met à jour l'interface utilisateur
            updateUI() {
                const enableBtn = document.getElementById('pwa-enable-btn');
                const disableBtn = document.getElementById('pwa-disable-btn');
                const statusIndicator = document.getElementById('pwa-status');

                if (this.isOfflineModeEnabled()) {
                    if (enableBtn) enableBtn.style.display = 'none';
                    if (disableBtn) disableBtn.style.display = 'inline-flex';
                    if (statusIndicator) {
                        statusIndicator.textContent = 'Mode hors ligne actif';
                        statusIndicator.className = 'pwa-status active';
                    }
                } else {
                    if (enableBtn) enableBtn.style.display = 'inline-flex';
                    if (disableBtn) disableBtn.style.display = 'none';
                    if (statusIndicator) {
                        statusIndicator.textContent = 'Mode hors ligne désactivé';
                        statusIndicator.className = 'pwa-status inactive';
                    }
                }
            },

            // Initialise au chargement
            async init() {
                // Si le mode hors ligne est déjà activé, réenregistrer le SW
                if (this.isOfflineModeEnabled() && 'serviceWorker' in navigator) {
                    try {
                        const registration = await navigator.serviceWorker.register('/sw.js');
                        console.log('✅ Service Worker restauré');

                        if (window.syncManager) {
                            await window.syncManager.init();
                        }
                    } catch (error) {
                        console.error('Erreur restauration SW:', error);
                    }
                }

                this.updateUI();

                // Afficher le bouton d'activation sur mobile si pas encore activé
                if (this.isMobile() && !this.isOfflineModeEnabled()) {
                    this.showMobilePrompt();
                }
            },

            // Affiche une suggestion sur mobile
            showMobilePrompt() {
                // Ne pas afficher si déjà fermé récemment
                const dismissed = localStorage.getItem('pwa_prompt_dismissed');
                if (dismissed) {
                    const dismissedTime = parseInt(dismissed, 10);
                    // Ne pas réafficher pendant 7 jours
                    if (Date.now() - dismissedTime < 7 * 24 * 60 * 60 * 1000) {
                        return;
                    }
                }

                setTimeout(() => {
                    const prompt = document.createElement('div');
                    prompt.id = 'pwa-mobile-prompt';
                    prompt.innerHTML = `
                        <div class="pwa-prompt-content">
                            <i class="fas fa-wifi-slash"></i>
                            <div class="pwa-prompt-text">
                                <strong>Mode hors ligne disponible</strong>
                                <p>Activez-le pour utiliser l'app sans connexion</p>
                            </div>
                            <button id="pwa-prompt-enable" class="btn btn-sm btn-primary">Activer</button>
                            <button id="pwa-prompt-close" class="btn btn-sm btn-link"><i class="fas fa-times"></i></button>
                        </div>
                    `;
                    document.body.appendChild(prompt);

                    document.getElementById('pwa-prompt-enable').addEventListener('click', async () => {
                        await this.enableOfflineMode();
                        prompt.remove();
                    });

                    document.getElementById('pwa-prompt-close').addEventListener('click', () => {
                        localStorage.setItem('pwa_prompt_dismissed', Date.now().toString());
                        prompt.remove();
                    });
                }, 3000);
            }
        };

        // Exposer globalement
        window.PWAManager = PWAManager;

        // Initialiser au chargement
        document.addEventListener('DOMContentLoaded', () => {
            PWAManager.init();
        });

        // Détecter le mode offline/online
        window.addEventListener('online', () => {
            console.log('🌐 Connexion rétablie');
        });

        window.addEventListener('offline', () => {
            console.log('📡 Mode hors ligne activé');
        });
    </script>

    <!-- Style pour le prompt PWA -->
    <style>
        #pwa-mobile-prompt {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            animation: slideUp 0.3s ease;
        }

        .pwa-prompt-content {
            display: flex;
            align-items: center;
            padding: 1rem;
            gap: 0.75rem;
        }

        .pwa-prompt-content > i {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        .pwa-prompt-text {
            flex: 1;
        }

        .pwa-prompt-text strong {
            display: block;
            font-size: 0.95rem;
            color: #1f2937;
        }

        .pwa-prompt-text p {
            margin: 0;
            font-size: 0.8rem;
            color: #6b7280;
        }

        #pwa-prompt-close {
            padding: 0.25rem;
            color: #9ca3af;
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

        /* Mode sombre */
        [data-theme="dark"] #pwa-mobile-prompt {
            background: #1f2937;
        }

        [data-theme="dark"] .pwa-prompt-text strong {
            color: #f9fafb;
        }

        [data-theme="dark"] .pwa-prompt-text p {
            color: #9ca3af;
        }
    </style>

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