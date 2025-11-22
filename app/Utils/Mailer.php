<?php
namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['SMTP_HOST'] ?? 'sandbox.smtp.mailtrap.io';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['SMTP_USERNAME'] ?? '';
        $this->mailer->Password = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->mailer->Port = (int)($_ENV['SMTP_PORT'] ?? 2525);

        // Timeout de connexion (en secondes)
        $this->mailer->Timeout = 10;
        $this->mailer->SMTPKeepAlive = false;

        // Configuration optionnelle de l'encryption (tls, ssl)
        if (!empty($_ENV['SMTP_ENCRYPTION'])) {
            $this->mailer->SMTPSecure = $_ENV['SMTP_ENCRYPTION'];
        } else {
            // Désactiver la vérification SSL auto si pas d'encryption spécifiée
            $this->mailer->SMTPAutoTLS = false;
        }

        // Debug en mode dev (0 = off, 2 = verbose)
        if (($_ENV['APP_ENV'] ?? 'prod') === 'dev') {
            $this->mailer->SMTPDebug = 0; // Mettre à 2 pour debug verbose
            $this->mailer->Debugoutput = 'error_log';
        }

        $this->mailer->CharSet = 'UTF-8';
    }

    /**
     * Réinitialiser le mailer pour un nouvel envoi
     */
    private function resetMailer(): void
    {
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
    }

    /**
     * Échapper les données utilisateur pour éviter XSS dans les emails HTML
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function sendConfirmationEmail($email, $name, $token): bool {
        try {
            $this->resetMailer();
            $this->mailer->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
            $this->mailer->addAddress($email, $name);
            $this->mailer->isHTML(true);

            $confirmationLink = $_ENV['APP_URL'] . "/confirmation/" . urlencode($token);

            $this->mailer->Subject = 'Confirmez votre compte KitiSmart';

            // Générer le contenu depuis le template
            ob_start();
            include dirname(__DIR__) . '/views/emails/confirmation.php';
            $this->mailer->Body = ob_get_clean();

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email confirmation: " . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordResetEmail(string $email, string $name, string $token): bool {
        try {
            $this->resetMailer();
            $this->mailer->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
            $this->mailer->addAddress($email, $name);
            $this->mailer->isHTML(true);

            $resetLink = $_ENV['APP_URL'] . "/reset-password/" . urlencode($token);

            $this->mailer->Subject = "Reinitialisation de votre mot de passe - KitiSmart";

            // Générer le contenu depuis le template
            ob_start();
            include dirname(__DIR__) . '/views/emails/reset-password.php';
            $this->mailer->Body = ob_get_clean();

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email reset-password: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer une alerte de budget (80% ou dépassement)
     */
    public function sendBudgetAlertEmail(string $email, string $name, array $data): bool {
        try {
            $this->resetMailer();
            $this->mailer->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
            $this->mailer->addAddress($email, $name);
            $this->mailer->isHTML(true);

            // Variables pour le template
            $user_name = $this->escape($name);
            $percentage = $data['percentage'] ?? 0;
            $is_over_budget = $data['is_over_budget'] ?? false;
            $budget_initial = number_format($data['budget_initial'] ?? 0, 0, ',', ' ');
            $budget_spent = number_format($data['budget_spent'] ?? 0, 0, ',', ' ');
            $budget_remaining = number_format($data['budget_remaining'] ?? 0, 0, ',', ' ');

            $this->mailer->Subject = $is_over_budget
                ? "Budget dépassé - KitiSmart"
                : "Alerte budget à {$percentage}% - KitiSmart";

            // Générer le contenu depuis le template
            ob_start();
            include dirname(__DIR__) . '/views/emails/budget_alert.php';
            $this->mailer->Body = ob_get_clean();

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email budget_alert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer une alerte de dépense importante
     */
    public function sendExpenseAlertEmail(string $email, string $name, array $data): bool {
        try {
            $this->resetMailer();
            $this->mailer->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
            $this->mailer->addAddress($email, $name);
            $this->mailer->isHTML(true);

            // Variables pour le template
            $user_name = $this->escape($name);
            $expense_amount = $data['expense_amount'] ?? 0;
            $expense_description = $this->escape($data['expense_description'] ?? '');
            $expense_date = $data['expense_date'] ?? date('Y-m-d');
            $expense_category = $this->escape($data['expense_category'] ?? '');
            $threshold = $data['threshold'] ?? 0;
            $budget_remaining = $data['budget_remaining'] ?? null;

            $this->mailer->Subject = "Alerte: Dépense de " . number_format($expense_amount, 0, ',', ' ') . " FCFA - KitiSmart";

            // Générer le contenu depuis le template
            ob_start();
            include dirname(__DIR__) . '/views/emails/expense_alert.php';
            $this->mailer->Body = ob_get_clean();

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email expense_alert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer le récapitulatif mensuel
     */
    public function sendMonthlySummaryEmail(string $email, string $name, array $data): bool {
        try {
            $this->resetMailer();
            $this->mailer->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
            $this->mailer->addAddress($email, $name);
            $this->mailer->isHTML(true);

            // Variables pour le template
            $user_name = $this->escape($name);
            $period = $this->escape($data['period'] ?? date('F Y'));
            $budget_initial = $data['budget_initial'] ?? 0;
            $budget_remaining = $data['budget_remaining'] ?? 0;
            $total_spent = $data['total_spent'] ?? 0;
            $expense_count = $data['expense_count'] ?? 0;
            $usage_percentage = $data['usage_percentage'] ?? 0;
            $categories = $data['categories'] ?? [];
            $top_expenses = $data['top_expenses'] ?? [];
            $insights = $data['insights'] ?? [];

            $this->mailer->Subject = "Récapitulatif mensuel - {$period} - KitiSmart";

            // Générer le contenu depuis le template
            ob_start();
            include dirname(__DIR__) . '/views/emails/monthly_summary.php';
            $this->mailer->Body = ob_get_clean();

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email monthly_summary: " . $e->getMessage());
            return false;
        }
    }
} 