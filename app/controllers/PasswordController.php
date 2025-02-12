<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Utils\Csrf;
use App\Utils\Mailer;
use App\Exceptions\TokenInvalidOrExpiredException;

class PasswordController extends Controller {
    public function showForgotForm() {
        $csrfToken = Csrf::generateToken();
        return $this->view('auth/forgot-password', [
            'title' => 'Mot de passe oublié - KitiSmart',
            'csrfToken' => $csrfToken
        ]);
    }

    public function sendResetLink() {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            $user = User::findByEmail($data['email']);
            
            if (!$user) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Si votre email existe dans notre base, vous recevrez un lien de réinitialisation.'
                ]);
            }

            // Générer un token de réinitialisation
            $user->reset_token = bin2hex(random_bytes(32));
            $user->reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            User::update($user);

            // Envoyer l'email
            $mailer = new Mailer();
            $mailer->sendPasswordResetEmail($user->email, $user->nom, $user->reset_token);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Si votre email existe dans notre base, vous recevrez un lien de réinitialisation.'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur reset password: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue'
            ], 500);
        }
    }

    public function showResetForm($token) {
        $user = User::findByResetToken($token);
        
        if (!$user || strtotime($user->reset_expires) < time()) {
            return $this->view('auth/reset-password', [
                'title' => 'Réinitialisation - KitiSmart',
                'error' => 'Ce lien de réinitialisation est invalide ou a expiré.'
            ]);
        }

        $csrfToken = Csrf::generateToken();
        return $this->view('auth/reset-password', [
            'title' => 'Réinitialisation - KitiSmart',
            'csrfToken' => $csrfToken,
            'resetToken' => $token
        ]);
    }

    public function reset() {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            $user = User::findByResetToken($data['reset_token']);
            
            if (!$user || strtotime($user->reset_expires) < time()) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Ce lien de réinitialisation est invalide ou a expiré.'
                ], 400);
            }

            // Mettre à jour le mot de passe
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
            $user->reset_token = null;
            $user->reset_expires = null;
            User::update($user);

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Votre mot de passe a été réinitialisé avec succès',
                'redirect' => '/login'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur reset password: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue'
            ], 500);
        }
    }
} 