<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Budget Partagé - KitiSmart' ?></title>

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

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="<?= \App\Core\Config::asset('/assets/css/dashboard/index.css') ?>">

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
            <link rel="stylesheet" href="<?= \App\Core\Config::asset('/assets/css/' . htmlspecialchars($style)) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <style>
        /* Guest-specific styles */
        .guest-navbar {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .guest-nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .guest-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .guest-brand i {
            font-size: 1.5rem;
        }

        .guest-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .guest-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            color: white;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-guest-logout {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .btn-guest-logout:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }

        .guest-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .guest-warning i {
            color: #856404;
            font-size: 1.2rem;
        }

        .dashboard-main {
            padding-top: 20px;
        }

        /* Tablet */
        @media (max-width: 768px) {
            .guest-navbar {
                padding: 10px 0;
            }

            .guest-nav-container {
                padding: 0 15px;
            }

            .guest-brand {
                font-size: 1.1rem;
                gap: 8px;
            }

            .guest-brand i {
                font-size: 1.2rem;
            }

            .guest-info {
                gap: 10px;
            }

            .guest-badge {
                font-size: 0.75rem;
                padding: 5px 10px;
            }

            .btn-guest-logout {
                font-size: 0.75rem;
                padding: 5px 12px;
            }

            .guest-warning {
                margin: 10px;
                padding: 10px;
                font-size: 0.85rem;
            }

            .guest-warning i {
                font-size: 1rem;
            }

            .dashboard-main {
                padding-top: 10px;
            }
        }

        /* Mobile */
        @media (max-width: 480px) {
            .guest-navbar {
                display: none;
            }

            .guest-warning {
                display: none;
            }

            .dashboard-main {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Guest Navbar -->
    <nav class="guest-navbar">
        <div class="guest-nav-container">
            <div class="guest-brand">
                <i class="fas fa-wallet"></i>
                <span>Budget Partagé</span>
            </div>
            <div class="guest-info">
                <div class="guest-badge">
                    <i class="fas fa-user-shield"></i>
                    <span>Mode Invité</span>
                </div>
                <a href="/budget/shared/logout" class="btn-guest-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Guest Warning Banner -->
    <div class="guest-warning">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Accès limité :</strong> Vous consultez ce budget en tant qu'invité.
            Vos actions sont limitées aux permissions qui vous ont été accordées.
        </div>
    </div>

    <!-- Main Content -->
    <main class="dashboard-main">
        <?= $content ?>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (!empty($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="/assets/js/<?= htmlspecialchars($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
