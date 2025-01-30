<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Utils\Csrf;

class LoginController extends Controller {
    public function showLoginForm() {
        // Affiche la vue de connexion
        $csrfToken = Csrf::generateToken();

        $this->view('auth/login', [
            'title' => 'Connexion - KitiSmart',
            'csrfToken' => $csrfToken,  
        ]);
    }

    public function login() {
        // Ici, vous ajouterez la logique de connexion plus tard
        // Pour l'instant, redirigez vers une autre page ou affichez un message
        $this->redirect('/dashboard');
    }
}