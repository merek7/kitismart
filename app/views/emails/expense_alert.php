<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerte Dépense Importante</title>
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
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #ffffff;
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
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
        }
        .alert-message p {
            margin: 0;
            color: #2c3e50;
            line-height: 1.6;
        }
        .expense-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        .detail-value {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 600;
        }
        .amount-highlight {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .amount-highlight .label {
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
            opacity: 0.9;
        }
        .amount-highlight .value {
            font-size: 32px;
            font-weight: 700;
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
        .info-box {
            background-color: #e0f2fe;
            border-left: 4px solid #0ea5e9;
            padding: 15px;
            margin-top: 20px;
            border-radius: 6px;
        }
        .info-box p {
            margin: 0;
            color: #2c3e50;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">💳</div>
            <h1>Alerte Dépense Importante</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Bonjour <?= htmlspecialchars($user_name) ?>,
            </div>

            <div class="alert-message">
                <p>
                    <strong>Notification :</strong> Une dépense importante vient d'être enregistrée dans votre budget.
                    Cette dépense dépasse le seuil d'alerte de <strong><?= number_format($threshold, 0, ',', ' ') ?> FCFA</strong>.
                </p>
            </div>

            <div class="amount-highlight">
                <div class="label">Montant de la dépense</div>
                <div class="value"><?= number_format($expense_amount, 0, ',', ' ') ?> FCFA</div>
            </div>

            <div class="expense-details">
                <div class="detail-row">
                    <span class="detail-label">📅 Date</span>
                    <span class="detail-value"><?= date('d/m/Y', strtotime($expense_date)) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">📝 Description</span>
                    <span class="detail-value"><?= htmlspecialchars($expense_description) ?></span>
                </div>
                <?php if (isset($expense_category) && $expense_category): ?>
                <div class="detail-row">
                    <span class="detail-label">🏷️ Catégorie</span>
                    <span class="detail-value"><?= htmlspecialchars($expense_category) ?></span>
                </div>
                <?php endif; ?>
                <?php if (isset($budget_remaining)): ?>
                <div class="detail-row">
                    <span class="detail-label">💰 Budget restant</span>
                    <span class="detail-value"><?= number_format($budget_remaining, 0, ',', ' ') ?> FCFA</span>
                </div>
                <?php endif; ?>
            </div>

            <div class="info-box">
                <p>
                    💡 <strong>Conseil :</strong> Vérifiez régulièrement vos dépenses pour maintenir un bon contrôle de votre budget.
                    Vous pouvez ajuster le seuil d'alerte dans les paramètres de notifications.
                </p>
            </div>

            <center>
                <a href="<?= $_ENV['APP_URL'] ?? 'http://localhost:8090' ?>/expenses" class="cta-button">
                    Voir mes dépenses
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
