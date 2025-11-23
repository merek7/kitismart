<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use RedBeanPHP\R as R;

/**
 * GoogleAuthController - Gestion de l'authentification Google OAuth 2.0
 */
class GoogleAuthController extends Controller
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
    private string $tokenUrl = 'https://oauth2.googleapis.com/token';
    private string $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';

    public function __construct()
    {
        $this->clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
        $this->clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
        $this->redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? '';
    }

    /**
     * Rediriger vers Google pour l'authentification
     */
    public function redirectToGoogle()
    {
        if (empty($this->clientId)) {
            $_SESSION['error'] = "La connexion Google n'est pas configuree.";
            return $this->redirectTo('/login');
        }

        // Generer un state pour la securite CSRF
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account'
        ];

        $authUrl = $this->authUrl . '?' . http_build_query($params);

        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Callback apres authentification Google
     */
    public function callback()
    {
        try {
            // Verifier le state pour la securite CSRF
            if (!isset($_GET['state']) || $_GET['state'] !== ($_SESSION['google_oauth_state'] ?? '')) {
                throw new \Exception('State invalide. Possible attaque CSRF.');
            }
            unset($_SESSION['google_oauth_state']);

            // Verifier s'il y a une erreur
            if (isset($_GET['error'])) {
                throw new \Exception('Erreur Google: ' . ($_GET['error_description'] ?? $_GET['error']));
            }

            // Verifier le code d'autorisation
            if (!isset($_GET['code'])) {
                throw new \Exception('Code d\'autorisation manquant.');
            }

            // Echanger le code contre un token
            $tokens = $this->getTokens($_GET['code']);

            if (!isset($tokens['access_token'])) {
                throw new \Exception('Token d\'acces non recu.');
            }

            // Recuperer les informations utilisateur
            $googleUser = $this->getUserInfo($tokens['access_token']);

            if (!isset($googleUser['id']) || !isset($googleUser['email'])) {
                throw new \Exception('Informations utilisateur incompletes.');
            }

            // Trouver ou creer l'utilisateur
            $user = $this->findOrCreateUser($googleUser);

            // Connecter l'utilisateur
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->nom;
            $_SESSION['user_email'] = $user->email;

            $_SESSION['success'] = 'Connexion reussie avec Google !';

            // Rediriger vers le dashboard
            $this->redirectTo('/dashboard');

        } catch (\Exception $e) {
            error_log("Erreur Google OAuth: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            $this->redirectTo('/login');
        }
    }

    /**
     * Echanger le code d'autorisation contre des tokens
     */
    private function getTokens(string $code): array
    {
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri
        ];

        $ch = curl_init($this->tokenUrl);

        // En dev sur Windows, desactiver la verification SSL si necessaire
        $sslVerify = ($_ENV['APP_ENV'] ?? 'prod') !== 'dev';

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Debug en dev
        if (($_ENV['APP_ENV'] ?? 'prod') === 'dev') {
            error_log("Google Token Exchange - HTTP Code: " . $httpCode);
            error_log("Google Token Exchange - Response: " . $response);
            if ($curlError) {
                error_log("Google Token Exchange - cURL Error: " . $curlError);
            }
        }

        if ($curlError) {
            throw new \Exception('Erreur cURL: ' . $curlError);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error_description'] ?? $errorData['error'] ?? $response;
            throw new \Exception('Erreur Google: ' . $errorMsg);
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Recuperer les informations utilisateur depuis Google
     */
    private function getUserInfo(string $accessToken): array
    {
        $ch = curl_init($this->userInfoUrl);

        // En dev sur Windows, desactiver la verification SSL si necessaire
        $sslVerify = ($_ENV['APP_ENV'] ?? 'prod') !== 'dev';

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('Erreur cURL: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new \Exception('Erreur lors de la recuperation du profil: ' . $response);
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Trouver un utilisateur existant ou en creer un nouveau
     */
    private function findOrCreateUser(array $googleUser)
    {
        $googleId = $googleUser['id'];
        $email = $googleUser['email'];
        $name = $googleUser['name'] ?? ($googleUser['given_name'] ?? 'Utilisateur');
        $picture = $googleUser['picture'] ?? null;

        // Chercher par google_id
        $user = R::findOne('users', 'google_id = ?', [$googleId]);

        if ($user) {
            // Mettre a jour les infos si necessaire
            $user->last_login = date('Y-m-d H:i:s');
            R::store($user);
            return $user;
        }

        // Chercher par email
        $user = R::findOne('users', 'email = ?', [$email]);

        if ($user) {
            // Lier le compte Google a l'utilisateur existant
            $user->google_id = $googleId;
            $user->last_login = date('Y-m-d H:i:s');
            if ($picture && empty($user->avatar)) {
                $user->avatar = $picture;
            }
            R::store($user);
            return $user;
        }

        // Creer un nouvel utilisateur
        $user = R::dispense('users');
        $user->nom = $name;
        $user->email = $email;
        $user->google_id = $googleId;
        $user->avatar = $picture;
        $user->password = null; // Pas de mot de passe pour les comptes Google
        $user->email_verified = true; // Email deja verifie par Google
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->onboarding_completed = false;
        $user->created_at = date('Y-m-d H:i:s');
        $user->last_login = date('Y-m-d H:i:s');

        R::store($user);

        return $user;
    }

    /**
     * Helper pour la redirection
     */
    private function redirectTo(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
