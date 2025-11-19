<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Exceptions\BudgetNotFoundException;
use App\Models\Budget;
use App\Models\Expense;

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

        try {
            // Vérifier si l'utilisateur a un budget actif
            
            $activeBudget = Budget::getActiveBudget($_SESSION['user_id']);
            
            // Si pas de budget actif, rediriger vers la création de budget
            if (!$activeBudget) {
                return $this->redirect('/budget/create');
            }
            
            // Récupérer le résumé du budget pour l'affichage
            $budgetSummary = Budget::getBudgetSummary($activeBudget->id);
            $depenseEnAttente = Expense::getPendingExpensesByUser($activeBudget->id, $_SESSION['user_id']);
            error_log("Budget summary: " . print_r($budgetSummary, true));
            
            return $this->view('dashboard/index', [
                'title' => 'Dashboard - KitiSmart',
                'userName' => $_SESSION['user_name'] ?? 'Utilisateur',
                'currentPage' => 'dashboard',
                'depenseEnAttente' => $depenseEnAttente,
                'layout' => 'dashboard',
                'activeBudget' => $activeBudget,
                'budgetSummary' => $budgetSummary
            ]);

        } catch (BudgetNotFoundException $e) {
            // Si pas de budget trouvé, rediriger vers la création
            return $this->redirect('/budget/create');
        } catch (\Exception $e) {
            // Log l'erreur et afficher un message générique
            error_log("Erreur dashboard: " . $e->getMessage());
            return $this->view('dashboard/index', [
                'title' => 'Dashboard - KitiSmart',
                'userName' => $_SESSION['user_name'] ?? 'Utilisateur',
                'currentPage' => 'dashboard',
                'layout' => 'dashboard',
                'error' => 'Une erreur est survenue lors du chargement du dashboard'
            ]);
        }
    }
}