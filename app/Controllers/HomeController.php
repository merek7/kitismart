<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Exceptions\BudgetNotFoundException;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\ExpenseRecurrence;
use App\Models\UserOnboarding;

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
            $userId = (int)$_SESSION['user_id'];

            // Vérifier si un budget est sélectionné en session
            $activeBudget = null;
            if (isset($_SESSION['current_budget_id'])) {
                $activeBudget = Budget::getById($_SESSION['current_budget_id'], $userId);
                // Vérifier que le budget est toujours actif
                if ($activeBudget && $activeBudget->status !== Budget::STATUS_ACTIVE) {
                    unset($_SESSION['current_budget_id']);
                    $activeBudget = null;
                }
            }

            // Si pas de budget sélectionné, prendre le budget actif par défaut
            if (!$activeBudget) {
                $activeBudget = Budget::getCurrentBudget($userId);
            }
            
            // Si pas de budget actif, rediriger vers la création de budget
            if (!$activeBudget) {
                return $this->redirect('/budget/create');
            }
            
            // Récupérer le résumé du budget pour l'affichage
            $budgetSummary = Budget::getBudgetSummary($activeBudget->id);
            $depenseEnAttente = Expense::getPendingExpensesByUser($activeBudget->id, $_SESSION['user_id']);

            // Récupérer les 3 prochaines récurrences actives
            $recurrences = ExpenseRecurrence::getActiveByBudget($activeBudget->id);
            $upcomingRecurrences = array_slice($recurrences, 0, 3);

            error_log("Budget summary: " . print_r($budgetSummary, true));

            // Préparer les données d'onboarding
            $onboarding = [
                'stepsToShow' => UserOnboarding::getStepsForPage((int)$_SESSION['user_id'], 'dashboard')
            ];

            return $this->view('dashboard/index', [
                'title' => 'Dashboard - KitiSmart',
                'userName' => $_SESSION['user_name'] ?? 'Utilisateur',
                'currentPage' => 'dashboard',
                'depenseEnAttente' => $depenseEnAttente,
                'layout' => 'dashboard',
                'activeBudget' => $activeBudget,
                'budgetSummary' => $budgetSummary,
                'upcomingRecurrences' => $upcomingRecurrences,
                'onboarding' => $onboarding
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