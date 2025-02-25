<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Budget;
use App\Utils\Csrf;
use App\Exceptions\TokenInvalidOrExpiredException;

class BudgetController extends Controller {

    public function showCreateBudgetForm() {
        $csrfToken = Csrf::generateToken();
        try{
            $activeBudget = Budget::getActiveBudget($_SESSION['user_id']);
            $depense= $activeBudget ? Budget::getBudgetSummary($activeBudget->id) : null;
            $this->view('dashboard/budget_create', [
                'title' => 'Créer un budget',
                'currentPage' => 'budget',
                'layout' => 'dashboard',
                'csrfToken' => $csrfToken,
                'activeBudget' => $activeBudget,
                'expensesSummary' => $depense,
            ]);
        } catch (\Exception $e) {
            error_log("Erreur lors de la création du formulaire de budget: " . $e->getMessage());
            $this->view('dashboard/create_budget', [
                'title' => 'Créer un budget',
                'csrfToken' => $csrfToken,
                'error' => $e->getMessage(),
            ]);
        }
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

            $budget = Budget::create($data);
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Budget créé avec succès',
                'budget' => $budget
            ], 200);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 