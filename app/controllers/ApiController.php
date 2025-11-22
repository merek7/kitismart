<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Utils\Csrf;

/**
 * Contrôleur pour les endpoints API utilitaires
 */
class ApiController extends Controller
{
    /**
     * Retourne un nouveau token CSRF
     * Utilisé par le sync-manager pour synchroniser les données hors ligne
     */
    public function getCsrfToken()
    {
        // Vérifier que l'utilisateur est authentifié
        if (!isset($_SESSION['user_id'])) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        // Générer un nouveau token CSRF
        $token = Csrf::generateToken();

        return $this->jsonResponse([
            'success' => true,
            'csrf_token' => $token
        ]);
    }
}
