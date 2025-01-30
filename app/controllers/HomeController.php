<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
     if(isset($_SESSION['user_id'])){
        return $this->view('/dashboard');
    }
    var_dump("coucou");
    $data = [
        'title' => 'KitiSmart - Gérez vos dépenses intelligement',
    ];
    return $this->view('home/index', $data);
    }

    public function dashboard()
    {
        if(!isset($_SESSION['user_id'])){
            return $this->redirect('/login');
        }
        return $this->view('home/dashboard');
    }
}