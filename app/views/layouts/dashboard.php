<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dashboard - KitiSmart' ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/dashboard/index.css">
    <link rel="stylesheet" href="/assets/css/animations.css">
    <link rel="stylesheet" href="/assets/css/enhanced-ux.css">
    <?php if (!empty($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($style) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
    <!-- Navbar -->
    <nav class="dashboard-nav fade-in-down">
        <div class="nav-container">
            <div class="nav-brand fade-in delay-1">
                <img src="/assets/img/logo.svg" alt="KitiSmart" class="nav-logo hover-scale">
            </div>
            <div class="nav-menu">
                <a href="/dashboard" class="nav-link transition-all <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="/budget/create" class="nav-link transition-all <?= $currentPage === 'budget' ? 'active' : '' ?>">
                    <i class="fas fa-wallet"></i> Budget
                </a>
                <a href="/expenses/create" class="nav-link transition-all <?= $currentPage === 'expenses' ? 'active' : '' ?>">
                    <i class="fas fa-receipt"></i> Dépenses
                </a>
                <a href="/settings" class="nav-link transition-all <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> Paramètres
                </a>
            </div>
            <div class="nav-user fade-in delay-2">
                <div class="user-menu">
                    <span class="user-name"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?></span>
                    <a href="/logout" id="logoutBtn" class="btn-logout btn transition-all hover-lift">
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
    <script src="/assets/js/toast.js"></script>

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