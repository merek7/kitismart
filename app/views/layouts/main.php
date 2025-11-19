<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'KitiSmart - Gérez vos dépenses avec intelligence' ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/animations.css" rel="stylesheet">
    <link href="/assets/css/enhanced-ux.css" rel="stylesheet">
    <?php if (!empty($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link href="/assets/css/<?= htmlspecialchars($style) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <nav class="navbar fade-in-down">
        <div class="nav-content">
            <div class="logo">
            <a href="/" class="nav-links hover-scale">
                KitiSmart
            </a>
        </div>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="nav-links">
                  <a href="#features" class="transition-all">Fonctionnalités</a>
                  <a href="#how-it-works" class="transition-all">Comment ça marche</a>
                  <a href="#testimonials" class="transition-all">Témoignages</a>
                </div>
                <div class="auth-buttons">
                    <a href="/login" class="login-btn btn transition-all">Connexion</a>
                    <a href="/register" class="register-btn btn transition-all">Inscription</a>
                </div>
            <?php else: ?>
                <div class="nav-links">
                    <a href="/dashboard" class="transition-all">Tableau de bord</a>
                    <a href="/expenses" class="transition-all">Dépenses</a>
                    <a href="/profile" class="transition-all">Profil</a>
                    <a href="/logout" class="transition-all">Déconnexion</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>
    <?= $content ?>
    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="/about">À propos</a>
                <a href="/privacy">Confidentialité</a>
                <a href="/terms">Conditions</a>
                <a href="/contact">Contact</a>
            </div>
            <p>&copy; <?= date('Y') ?> KitiSmart. Tous droits réservés.</p>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/toast.js"></script>
    <script src="/assets/js/app.js"></script>
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