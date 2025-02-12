<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        if(isset($_SESSION['user_id'])){
            return $this->redirect('/dashboard');
        }
        
        return $this->view('home/index', [
            'title' => 'KitiSmart - Gérez vos dépenses intelligement',
        ]);
    }

    public function dashboard()
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect('/login');
        }
        
        return $this->view('dashboard/index', [
            'title' => 'Dashboard - KitiSmart',
            'userName' => $_SESSION['user_name'] ?? 'Utilisateur'
        ]);
    }
}