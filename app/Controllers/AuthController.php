<?php
namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller {
    public function logout() {
        // Destruction de la session
        session_destroy();
        
        // Redirection vers la page de login
        return $this->redirect('/login');
    }
} 