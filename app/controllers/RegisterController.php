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
use App\Validators\RegisterValidator;
use App\Utils\Mailer;

class RegisterController extends Controller {
    public function showRegisterForm() {
        if (!isset($_SESSION['register_attemps'])) {
            $_SESSION['register_attemps'] = 0;
        }
        
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
                    
                    return $this->jsonResponse([
                        'success' => true,
                        'message' => 'Un nouveau lien de confirmation a été envoyé à votre adresse email'
                    ]);
                }
                
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Cette adresse email est déjà utilisée'
                ], 400);
            }

            // Si trop de tentatives, on lève une exception dédiée
            if ($_SESSION['register_attemps'] > 5) {
                throw new TooManyAttemptsException();
            }

            error_log("Données d'inscription: " . print_r($data, true));

            $validator = new RegisterValidator($data);
            if (!$validator->validate()) {
                error_log("Erreurs de validation: " . print_r($validator->errors(), true));
                return $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Des erreurs ont été détectées.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // Essai de création de l'utilisateur.
            // Si l'utilisateur existe déjà, le modèle lance une UserAlreadyExistsException.
            $user = User::create([
                'nom'      => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password']
            ]);

            // Vérification que l'utilisateur a bien été créé (si null, on peut lever une exception de base de données)
            if ($user === null) {
                throw new UserAlreadyExistsException();
            }

            // Log de l'audit utilisateur
            $audit = UserAudit::log(
                $user,
                'register', 
                [
                    'source'     => 'web', 
                    'ip'         => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ]
            );

            return $this->jsonResponse([
                'success' => true, 
                'message' => 'Inscription réussie.'
            ], 200);

        }catch (TooManyAttemptsException $e) {
            error_log("TooManyAttemptsException: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false, 
                'message' => $e->getMessage()
            ], $e->getCode());
        
        } catch (UserAlreadyExistsException $e) {
            error_log("UserAlreadyExistsException: " . $e->getMessage());
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