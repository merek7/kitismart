<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'KitiSmart - Gérez vos dépenses avec intelligence' ?></title>

    <!-- PWA Meta Tags -->
    <meta name="description" content="Application de gestion de budget personnel intelligente">
    <meta name="theme-color" content="#0d9488">

    <!-- iOS Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="KitiSmart">
    <link rel="apple-touch-icon" href="/assets/img/icons/icon-192x192.png">

    <!-- Android/Chrome Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="KitiSmart">

    <!-- Manifest -->
    <link rel="manifest" href="/manifest.json">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= \App\Core\Config::asset('/assets/css/style.css') ?>" rel="stylesheet">

    <?php if (!empty($_ENV['CLARITY_PROJECT_ID'])): ?>
    <!-- Microsoft Clarity -->
    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "<?= htmlspecialchars($_ENV['CLARITY_PROJECT_ID']) ?>");
    </script>
    <?php endif; ?>
    <?php if (!empty($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link href="<?= \App\Core\Config::asset('/assets/css/' . htmlspecialchars($style)) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <div class="logo">
                <a href="/">KitiSmart</a>
            </div>

            <!-- Hamburger menu button for mobile -->
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <span class="hamburger"></span>
            </button>

            <div class="nav-menu" id="navMenu">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="nav-links">
                        <a href="#features">Fonctionnalites</a>
                        <a href="#how-it-works">Comment ca marche</a>
                        <a href="#testimonials">Temoignages</a>
                    </div>
                    <div class="auth-buttons">
                        <a href="/login" class="login-btn">Connexion</a>
                        <a href="/register" class="register-btn">Inscription</a>
                    </div>
                <?php else: ?>
                    <div class="nav-links">
                        <a href="/dashboard">Tableau de bord</a>
                        <a href="/expenses">Depenses</a>
                        <a href="/profile">Profil</a>
                        <?php if (($_ENV['APP_ENV'] ?? 'prod') === 'dev'): ?>
                            <a href="/admin/email-test" style="color: #facc15;">Test Emails</a>
                        <?php endif; ?>
                        <a href="/logout">Deconnexion</a>
                    </div>
                <?php endif; ?>
            </div>
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
    <script src="/assets/js/app.js"></script>

    <!-- Mobile Navigation Toggle -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');

        if (navToggle && navMenu) {
            navToggle.addEventListener('click', function() {
                navToggle.classList.toggle('active');
                navMenu.classList.toggle('active');
                document.body.classList.toggle('nav-open');
            });

            // Close menu when clicking on a link
            navMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    navToggle.classList.remove('active');
                    navMenu.classList.remove('active');
                    document.body.classList.remove('nav-open');
                });
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (!navMenu.contains(e.target) && !navToggle.contains(e.target)) {
                    navToggle.classList.remove('active');
                    navMenu.classList.remove('active');
                    document.body.classList.remove('nav-open');
                }
            });
        }
    });
    </script>

    <!-- Service Worker - Desactive (optionnel via Parametres) -->
    <!-- Le Service Worker n'est plus enregistre automatiquement -->

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