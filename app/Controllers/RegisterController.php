<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Exceptions\DataBaseException;
use App\Exceptions\TokenInvalidOrExpiredException;
use App\Exceptions\TooManyAttemptsException;
use App\Exceptions\UserAlreadyExistsException;
use App\Models\User;
use App\Models\UserAudit;
use App\Utils\Csrf;
use App\Utils\RateLimiter;
use App\Validators\RegisterValidator;
use App\Utils\Mailer;

class RegisterController extends Controller {
    public function showRegisterForm() {
        $csrfToken= Csrf::generateToken();
        // Affiche la vue d'inscription
        $this->view('auth/register', [
            'title' => 'Inscription - KitiSmart',
            'csrfToken' => $csrfToken,
        ]);
    }

    public function register() {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            // Rate limiting: Max 3 inscriptions / 1 heure
            $maxAttempts = 3;
            $timeWindow = 3600; // 1 heure en secondes

            if (!RateLimiter::check('register', $maxAttempts, $timeWindow)) {
                $retryAfter = RateLimiter::getRetryAfter('register', $timeWindow);
                $minutes = ceil($retryAfter / 60);

                return $this->jsonResponse([
                    'success' => false,
                    'message' => "Trop de tentatives d'inscription. Veuillez réessayer dans {$minutes} minute(s).",
                    'retry_after' => $retryAfter
                ], 429);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            $existingUser = User::findByEmail($data['email']);

            if ($existingUser) {
                if ($existingUser->status === 'inactif') {
                    // Mettre à jour l'utilisateur existant au lieu d'en créer un nouveau
                    $existingUser->nom = $data['name'];
                    $existingUser->password = password_hash($data['password'], PASSWORD_DEFAULT);
                    $existingUser->confirmation_token = bin2hex(random_bytes(32));
                    $existingUser->confirmation_expires = date('Y-m-d H:i:s', strtotime('+20 minutes'));
                    User::update($existingUser);

                    // Envoyer un nouveau mail de confirmation
                    $this->sendConfirmationEmail($existingUser);

                    // Réinitialiser le compteur car action légitime
                    RateLimiter::reset('register');

                    return $this->jsonResponse([
                        'success' => true,
                        'message' => 'Un nouveau lien de confirmation a été envoyé à votre adresse email'
                    ]);
                }

                // Enregistrer la tentative échouée
                RateLimiter::hit('register');

                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Cette adresse email est déjà utilisée'
                ], 400);
            }

            error_log("Données d'inscription: " . print_r($data, true));

            $validator = new RegisterValidator($data);
            if (!$validator->validate()) {
                error_log("Erreurs de validation: " . print_r($validator->errors(), true));

                // Enregistrer la tentative échouée
                RateLimiter::hit('register');

                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Des erreurs ont été détectées.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // Essai de création de l'utilisateur.
            // Si l'utilisateur existe déjà, le modèle lance une UserAlreadyExistsException.
            $userId = User::create([
                'nom'      => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password']
            ]);

            // Vérification que l'utilisateur a bien été créé (si null, on peut lever une exception de base de données)
            if ($userId === null) {
                throw new UserAlreadyExistsException();
            }

            // Récupérer l'utilisateur complet pour l'envoi d'email
            $user = User::findById($userId);
            if (!$user) {
                throw new \Exception("Erreur lors de la récupération de l'utilisateur créé");
            }

            // Envoyer l'email de confirmation
            $emailSent = $this->sendConfirmationEmail($user);
            if (!$emailSent) {
                error_log("Erreur: Email de confirmation non envoyé pour " . $user->email);
            }

            // Inscription réussie - Réinitialiser le compteur
            RateLimiter::reset('register');

            // Log de l'audit utilisateur
            $audit = UserAudit::log(
                $user->id,
                'register',
                [
                    'source'     => 'web',
                    'ip'         => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ]
            );

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Inscription réussie. Un email de confirmation vous a été envoyé.'
            ], 200);

        }catch (TooManyAttemptsException $e) {
            error_log("TooManyAttemptsException: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());

        } catch (UserAlreadyExistsException $e) {
            error_log("UserAlreadyExistsException: " . $e->getMessage());

            // Enregistrer la tentative échouée
            RateLimiter::hit('register');

            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());

        } catch (TokenInvalidOrExpiredException $e) {
            error_log("TokenInvalidOrExpiredException: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());

        } catch (DataBaseException $e) {
            error_log("DataBaseException: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());

        } catch (\Exception $e) {
            error_log("Exception générale: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue.'
            ], 500);
        }
    }

    private function sendConfirmationEmail($user) {
        $mailer = new Mailer();
        return $mailer->sendConfirmationEmail(
            $user->email,
            $user->nom,
            $user->confirmation_token
        );
    }
}