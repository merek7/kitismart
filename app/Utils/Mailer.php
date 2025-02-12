<?php
namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'sandbox.smtp.mailtrap.io';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'bd3a4818da4ef4';
        $this->mailer->Password = 'af26d38e9f242b';
        $this->mailer->Port = 2525;
        $this->mailer->CharSet = 'UTF-8';
    }

    public function sendConfirmationEmail($email, $name, $token) {
        try {
            $this->mailer->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
            $this->mailer->addAddress($email, $name);
            $this->mailer->isHTML(true);
            
            $confirmationLink = $_ENV['APP_URL'] . "/confirmation/" . $token;
            
            $this->mailer->Subject = 'Confirmez votre compte KitiSmart';
            $this->mailer->Body = "
                <h2>Bonjour {$name},</h2>
                <p>Merci de vous être inscrit sur KitiSmart. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
                <p><a href='{$confirmationLink}'>Confirmer mon compte</a></p>
                <p>Ce lien est valable pendant 20 minutes.</p>
                <p>Si vous n'avez pas créé de compte, ignorez cet email.</p>
            ";

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email: " . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordResetEmail(string $email, string $name, string $token): bool {
        try {
            $this->mailer->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
            $this->mailer->addAddress($email, $name);
            $this->mailer->isHTML(true);
            
            $resetLink = $_ENV['APP_URL'] . "/reset-password/" . $token;
            
            $this->mailer->Subject = "Réinitialisation de votre mot de passe - KitiSmart";
            $this->mailer->Body = "
                <h2>Bonjour {$name},</h2>
                <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                <p>Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe :</p>
                <p><a href='{$resetLink}'>{$resetLink}</a></p>
                <p>Ce lien expirera dans 1 heure.</p>
            ";

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email: " . $e->getMessage());
            return false;
        }
    }
} 