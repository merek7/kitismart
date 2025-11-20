<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Utils\Csrf;
use App\Utils\RateLimiter;
use App\Models\User;
use App\Models\UserAudit;
use App\Exceptions\TokenInvalidOrExpiredException;

class LoginController extends Controller {
    public function showLoginForm() {
        if (isset($_SESSION['user_id'])) {
            return $this->redirect('/dashboard');
        }
        
        $csrfToken = Csrf::generateToken();
        $this->view('auth/login', [
            'title' => 'Connexion - KitiSmart',
            'csrfToken' => $csrfToken,
        ]);
    }

    public function login() {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            // Rate limiting: Max 5 tentatives / 15 minutes
            $maxAttempts = 5;
            $timeWindow = 900; // 15 minutes en secondes

            if (!RateLimiter::check('login', $maxAttempts, $timeWindow)) {
                $retryAfter = RateLimiter::getRetryAfter('login', $timeWindow);
                $minutes = ceil($retryAfter / 60);

                return $this->jsonResponse([
                    'success' => false,
                    'message' => "Trop de tentatives de connexion. Veuillez réessayer dans {$minutes} minute(s).",
                    'retry_after' => $retryAfter
                ], 429);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            error_log("Données du login: " . print_r($data, true));
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            $user = User::findByEmail($data['email']);

            if (!$user || !password_verify($data['password'], $user->password)) {
                // Enregistrer la tentative échouée
                RateLimiter::hit('login');

                $remaining = RateLimiter::getRemainingAttempts('login', $maxAttempts, $timeWindow);

                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect',
                    'remaining_attempts' => $remaining
                ], 401);
            }

            if ($user->status !== 'active') {
                // Enregistrer la tentative échouée
                RateLimiter::hit('login');

                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Veuillez confirmer votre compte par email avant de vous connecter'
                ], 403);
            }

            // Connexion réussie - Réinitialiser le compteur
            RateLimiter::reset('login');

            // Création de la session
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->nom;

            // Log de la connexion
            UserAudit::log($user->id, 'login', [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ]);

            return $this->jsonResponse([
                'success' => true,
                'redirect' => '/dashboard'
            ]);

        } catch (TokenInvalidOrExpiredException $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            error_log("Erreur de login: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue'
            ], 500);
        }
    }
}