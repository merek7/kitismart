<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récapitulatif Mensuel</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 650px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header .subtitle {
            margin-top: 10px;
            font-size: 16px;
            opacity: 0.9;
        }
        .header .icon {
            font-size: 56px;
            margin-bottom: 15px;
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
        .intro-text {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        .stat-card.highlight {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border-color: #10b981;
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-color: #ef4444;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }
        .stat-value.large {
            font-size: 28px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #0d9488;
        }
        .category-list {
            margin: 20px 0;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .category-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        .category-amount {
            font-weight: 700;
            color: #0d9488;
            font-size: 14px;
        }
        .progress-container {
            margin: 25px 0;
        }
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background-color: #e5e7eb;
            border-radius: 15px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0d9488, #0f766e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 13px;
            transition: width 0.3s ease;
        }
        .progress-fill.warning {
            background: linear-gradient(90deg, #facc15, #f59e0b);
        }
        .progress-fill.danger {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }
        .insights-box {
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-left: 4px solid #0ea5e9;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .insights-box h3 {
            margin: 0 0 12px 0;
            font-size: 16px;
            color: #2c3e50;
            font-weight: 700;
        }
        .insights-box ul {
            margin: 0;
            padding-left: 20px;
            color: #2c3e50;
        }
        .insights-box li {
            margin-bottom: 8px;
            line-height: 1.5;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: white;
            padding: 14px 35px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 4px 10px rgba(13, 148, 136, 0.3);
        }
        .footer {
            background-color: #f8f9fa;
            padding: 25px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">📊</div>
            <h1>Récapitulatif Mensuel</h1>
            <div class="subtitle"><?= $period ?></div>
        </div>

        <div class="content">
            <div class="greeting">
                Bonjour <?= htmlspecialchars($user_name) ?>,
            </div>

            <p class="intro-text">
                Voici votre récapitulatif mensuel de gestion budgétaire.
                Découvrez vos performances et des conseils pour optimiser vos finances.
            </p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Budget Initial</div>
                    <div class="stat-value"><?= number_format($budget_initial, 0, ',', ' ') ?> <small>FCFA</small></div>
                </div>
                <div class="stat-card <?= $budget_remaining < 0 ? 'warning' : 'highlight' ?>">
                    <div class="stat-label">Restant</div>
                    <div class="stat-value"><?= number_format($budget_remaining, 0, ',', ' ') ?> <small>FCFA</small></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Dépensé</div>
                    <div class="stat-value large"><?= number_format($total_spent, 0, ',', ' ') ?> <small>FCFA</small></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Nombre de Dépenses</div>
                    <div class="stat-value large"><?= $expense_count ?></div>
                </div>
            </div>

            <div class="progress-container">
                <div class="progress-label">
                    <span><strong>Utilisation du budget</strong></span>
                    <span><strong><?= round($usage_percentage) ?>%</strong></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill <?= $usage_percentage >= 100 ? 'danger' : ($usage_percentage >= 80 ? 'warning' : '') ?>"
                         style="width: <?= min($usage_percentage, 100) ?>%">
                        <?= round($usage_percentage) ?>%
                    </div>
                </div>
            </div>

            <?php if (!empty($categories) && count($categories) > 0): ?>
            <div class="section-title">
                📂 Dépenses par catégorie
            </div>
            <div class="category-list">
                <?php foreach($categories as $category): ?>
                <div class="category-item">
                    <span class="category-name"><?= htmlspecialchars($category['name']) ?></span>
                    <span class="category-amount"><?= number_format($category['total'], 0, ',', ' ') ?> FCFA</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($top_expenses) && count($top_expenses) > 0): ?>
            <div class="section-title">
                💰 Top 5 des dépenses
            </div>
            <div class="category-list">
                <?php foreach($top_expenses as $expense): ?>
                <div class="category-item">
                    <div>
                        <div class="category-name"><?= htmlspecialchars($expense['description']) ?></div>
                        <small style="color: #6b7280;"><?= date('d/m/Y', strtotime($expense['date'])) ?></small>
                    </div>
                    <span class="category-amount"><?= number_format($expense['amount'], 0, ',', ' ') ?> FCFA</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($insights)): ?>
            <div class="insights-box">
                <h3>💡 Conseils personnalisés</h3>
                <ul>
                    <?php foreach($insights as $insight): ?>
                    <li><?= htmlspecialchars($insight) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="cta-section">
                <a href="<?= $_ENV['APP_URL'] ?? 'http://localhost:8090' ?>/dashboard" class="cta-button">
                    📈 Accéder au tableau de bord
                </a>
            </div>

            <p style="margin-top: 30px; color: #6b7280; font-size: 14px; line-height: 1.6; text-align: center;">
                Continuez à suivre vos dépenses régulièrement pour atteindre vos objectifs financiers ! 💪
            </p>
        </div>

        <div class="footer">
            <p><strong>KitiSmart</strong> - Votre assistant de gestion budgétaire</p>
            <p>Cet email a été envoyé automatiquement.</p>
            <p>Vous pouvez modifier vos préférences de notification dans les paramètres.</p>
        </div>
    </div>
</body>
</html>
