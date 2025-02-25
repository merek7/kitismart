<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Exceptions\TokenInvalidOrExpiredException;
use App\Utils\Csrf;

Class ParametrageController extends Controller {

    public function showCreateParametrageForm() {
        $this->view('dashboard/parametrage_create', [
            'title' => 'Créer un parametrage',
            'currentPage' => 'parametrage',
            'layout' => 'dashboard',
        ]);
    }

    public function create() {
        try {
            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $data['user_id'] = $_SESSION['user_id'];

            // Validation CSRF
            if (!Csrf::validateToken($data['csrf_token'] ?? '')) {
                throw new TokenInvalidOrExpiredException();
            }

            //$parametrage = Parametrage::create($data);
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Parametrage créé avec succès',
                'parametrage' => 'parametrage'
            ], 200);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}