<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Budget;
use App\Models\UserOnboarding;
use App\Utils\Csrf;
use App\Exceptions\TokenInvalidOrExpiredException;

class BudgetController extends Controller {

    public function __construct()
    {
        $this->requireAuth();
    }

    public function showCreateBudgetForm() {
        $userId = $_SESSION['user_id'];
        $csrfToken = Csrf::generateToken();

        try{
            $activeBudget = Budget::getActiveBudget($userId);
            $depense= $activeBudget ? Budget::getBudgetSummary($activeBudget->id) : null;
            $previousBudgets = Budget::getPreviousBudgets($userId, 50);

            // Préparer les données d'onboarding
            $onboarding = [
                'stepsToShow' => UserOnboarding::getStepsForPage((int)$userId, 'budget_create')
            ];

            $this->view('dashboard/budget_create', [
                'title' => 'Créer un budget',
                'currentPage' => 'budget',
                'layout' => 'dashboard',
                'csrfToken' => $csrfToken,
                'activeBudget' => $activeBudget,
                'expensesSummary' => $depense,
                'previousBudgets' => $previousBudgets,
                'onboarding' => $onboarding
            ]);
        } catch (\Exception $e) {
            error_log("Erreur lors de la création du formulaire de budget: " . $e->getMessage());
            $this->view('dashboard/budget_create', [
                'title' => 'Créer un budget',
                'currentPage' => 'budget',
                'layout' => 'dashboard',
                'csrfToken' => $csrfToken,
                'error' => $e->getMessage(),
                'previousBudgets' => [], // Liste vide en cas d'erreur
            ]);
        }
    }

    public function create() {
        try {
            // Vérifier l'authentification
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            if (!$this->isPostRequest()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Méthode non autorisée'], 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $data['user_id'] = $_SESSION['user_id'];

            // Mapper 'amount' vers 'initial_amount' si nécessaire
            if (isset($data['amount']) && !isset($data['initial_amount'])) {
                $data['initial_amount'] = $data['amount'];
            }

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

    /**
     * Récupère le budget actif de l'utilisateur (API)
     */
    public function getActiveBudget() {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $activeBudget = Budget::getActiveBudget($_SESSION['user_id']);

            if (!$activeBudget) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Aucun budget actif'
                ], 404);
            }

            return $this->jsonResponse([
                'success' => true,
                'budget' => $activeBudget
            ]);
        } catch (\Exception $e) {
            error_log("Erreur getActiveBudget: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération du budget'
            ], 500);
        }
    }

    /**
     * Récupère le résumé d'un budget (API)
     */
    public function getBudgetSummary($id) {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $summary = Budget::getBudgetSummary($id);

            if (!$summary) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Budget non trouvé'
                ], 404);
            }

            return $this->jsonResponse([
                'success' => true,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            error_log("Erreur getBudgetSummary: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la récupération du résumé'
            ], 500);
        }
    }
} 