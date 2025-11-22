<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerte Budget</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: <?= $is_over_budget ? '#ef4444' : '#facc15' ?>;
            color: <?= $is_over_budget ? '#ffffff' : '#2c3e50' ?>;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .alert-message {
            background-color: <?= $is_over_budget ? '#fef2f2' : '#fef3c7' ?>;
            border-left: 4px solid <?= $is_over_budget ? '#ef4444' : '#facc15' ?>;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
        }
        .alert-message p {
            margin: 0;
            color: #2c3e50;
            line-height: 1.6;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }
        .stat-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50;
        }
        .stat-value.danger {
            color: #ef4444;
        }
        .stat-value.success {
            color: #10b981;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background-color: #e5e7eb;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-fill {
            height: 100%;
            background: <?= $is_over_budget ? 'linear-gradient(90deg, #ef4444, #dc2626)' : 'linear-gradient(90deg, #facc15, #f59e0b)' ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            transition: width 0.3s ease;
        }
        .cta-button {
            display: inline-block;
            background-color: #0d9488;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">⚠️</div>
            <h1><?= $is_over_budget ? 'Budget Dépassé !' : 'Alerte Budget' ?></h1>
        </div>

        <div class="content">
            <div class="greeting">
                Bonjour <?= htmlspecialchars($user_name) ?>,
            </div>

            <div class="alert-message">
                <p>
                    <?php if ($is_over_budget): ?>
                        <strong>Attention !</strong> Votre budget a été dépassé. Vous avez dépensé <strong><?= $percentage ?>%</strong> de votre budget initial.
                    <?php else: ?>
                        Vous avez atteint <strong><?= $percentage ?>%</strong> de votre budget. Il est temps de surveiller vos dépenses.
                    <?php endif; ?>
                </p>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= min($percentage, 100) ?>%">
                    <?= round($percentage) ?>%
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Budget Initial</div>
                    <div class="stat-value"><?= $budget_initial ?> FCFA</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Dépensé</div>
                    <div class="stat-value danger"><?= $budget_spent ?> FCFA</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Restant</div>
                    <div class="stat-value <?= $is_over_budget ? 'danger' : 'success' ?>">
                        <?= $budget_remaining ?> FCFA
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Utilisation</div>
                    <div class="stat-value <?= $is_over_budget ? 'danger' : '' ?>">
                        <?= round($percentage) ?>%
                    </div>
                </div>
            </div>

            <p style="margin-top: 25px; color: #6b7280; line-height: 1.6;">
                <?php if ($is_over_budget): ?>
                    Nous vous conseillons de revoir vos dépenses et de planifier un nouveau budget pour mieux contrôler vos finances.
                <?php else: ?>
                    Pensez à ajuster vos dépenses pour ne pas dépasser votre budget.
                <?php endif; ?>
            </p>

            <center>
                <a href="<?= $_ENV['APP_URL'] ?? 'http://localhost:8090' ?>/dashboard" class="cta-button">
                    Voir mon budget
                </a>
            </center>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement par KitiSmart.</p>
            <p>Vous pouvez modifier vos préférences de notification dans les paramètres.</p>
        </div>
    </div>
</body>
</html>
