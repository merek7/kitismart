<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmez votre compte KitiSmart</title>
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
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .message {
            color: #6b7280;
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 30px;
        }
        .highlight-box {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .highlight-box p {
            margin: 0;
            color: #2c3e50;
            font-size: 15px;
            line-height: 1.6;
        }
        .cta-section {
            text-align: center;
            margin: 35px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: #ffffff !important;
            padding: 16px 45px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            box-shadow: 0 6px 20px rgba(13, 148, 136, 0.4);
        }
        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 6px;
        }
        .warning-box p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
            line-height: 1.5;
        }
        .features {
            margin: 30px 0;
            padding: 25px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        .features h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .feature-item:last-child {
            margin-bottom: 0;
        }
        .feature-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
        }
        .feature-text {
            color: #4b5563;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 25px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #0d9488;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e5e7eb, transparent);
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">KitiSmart</div>
            <div class="icon">&#9989;</div>
            <h1>Bienvenue !</h1>
            <div class="subtitle">Confirmez votre adresse email pour commencer</div>
        </div>

        <div class="content">
            <div class="greeting">
                Bonjour <?= htmlspecialchars($name) ?>,
            </div>

            <p class="message">
                Merci de vous etre inscrit sur <strong>KitiSmart</strong>, votre assistant de gestion budgetaire intelligent !
                Pour activer votre compte et commencer a gerer vos finances, veuillez confirmer votre adresse email.
            </p>

            <div class="cta-section">
                <a href="<?= $confirmationLink ?>" class="cta-button">
                    Confirmer mon compte
                </a>
            </div>

            <div class="warning-box">
                <p>
                    <strong>Important :</strong> Ce lien est valable pendant <strong>20 minutes</strong>.
                    Si vous n'avez pas cree de compte sur KitiSmart, veuillez ignorer cet email.
                </p>
            </div>

            <div class="divider"></div>

            <div class="features">
                <h3>Ce que vous pourrez faire avec KitiSmart :</h3>
                <div class="feature-item">
                    <div class="feature-icon">&#128176;</div>
                    <span class="feature-text">Creer et gerer vos budgets mensuels</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">&#128200;</div>
                    <span class="feature-text">Suivre vos depenses en temps reel</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">&#128276;</div>
                    <span class="feature-text">Recevoir des alertes personnalisees</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">&#128101;</div>
                    <span class="feature-text">Partager vos budgets en famille</span>
                </div>
            </div>

            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin-top: 25px;">
                Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
                <a href="<?= $confirmationLink ?>" style="color: #0d9488; word-break: break-all;"><?= $confirmationLink ?></a>
            </p>
        </div>

        <div class="footer">
            <p><strong>KitiSmart</strong> - Votre assistant de gestion budgetaire</p>
            <p>Cet email a ete envoye automatiquement suite a votre inscription.</p>
            <p style="margin-top: 15px;">
                &copy; <?= date('Y') ?> KitiSmart. Tous droits reserves.
            </p>
        </div>
    </div>
</body>
</html>
