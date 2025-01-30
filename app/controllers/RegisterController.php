<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Utils\Csrf;
use App\Validators\RegisterValidator;

class RegisterController extends Controller {
    public function showRegisterForm() {
        if (!isset($_SESSION)) {
            session_start();
        }
        $csrfToken= Csrf::generateToken();
        // Affiche la vue d'inscription
        $this->view('auth/register', [
            'title' => 'Inscription - KitiSmart',
            'csrfToken' => $csrfToken,
        ]);
    }

    public function register() {
        if(!$this->isPostRequest())
        {
            error_log("Erreur de requête" .$_SERVER['REQUEST_METHOD']);
            return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        error_log("Données d'inscription: " . print_r($data, true));

        if(!Csrf::validatetoken($data['csrf_token'] ?? '')){
            error_log("Erreur de securité" . $_SERVER['REMOTE_ADDR']);
            return $this->jsonResponse(['success' => false, 'message' => 'Erreur de securite.'], 403);
            
        }

       $validator= new RegisterValidator($data);
       
       if(!$validator->validate()){
            error_log("Erreurs de validation" . print_r($validator->errors(), true));

           $this->jsonResponse([
            'success' => false, 
            'message' => 'Des erreurs ont été détectées.',
            'errors' => $validator->errors()], 422);
           return;
         }
         
         error_log("Inscription réussie");
        $this->jsonResponse(
            ['success' => true, 
            'message' => 'Inscription réussie.'], 
            200);
    }
}