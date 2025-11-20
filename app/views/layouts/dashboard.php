<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dashboard - KitiSmart' ?></title>

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
</body>
</html> 