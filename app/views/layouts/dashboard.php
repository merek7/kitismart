<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Dashboard - KitiSmart' ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/dashboard/index.css">
    <?php if (!empty($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="/assets/css/<?= htmlspecialchars($style) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">


</head>
<body>
    <!-- Navbar -->
    <nav class="dashboard-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <img src="/assets/img/logo.svg" alt="KitiSmart" class="nav-logo">
            </div>
            <div class="nav-menu">
                <a href="/dashboard" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="/budget/create" class="nav-link <?= $currentPage === 'budget' ? 'active' : '' ?>">
                    <i class="fas fa-wallet"></i> Budget
                </a>
                <a href="/expenses/create" class="nav-link <?= $currentPage === 'expenses' ? 'active' : '' ?>">
                    <i class="fas fa-receipt"></i> Dépenses
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
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <script src="/assets/js/dashboard/dashboard.js"></script>
    
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script> 
    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    
    <!-- DateRangePicker -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    

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