<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Budget;

class BudgetSwitchController extends Controller
{
    /**
     * Récupérer tous les budgets actifs pour le sélecteur
     */
    public function getActiveBudgets()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $userId = (int)$_SESSION['user_id'];
            $budgets = Budget::getAllActiveBudgets($userId);
            $currentBudgetId = $_SESSION['current_budget_id'] ?? null;

            $budgetList = [];
            foreach ($budgets as $budget) {
                $budgetList[] = [
                    'id' => $budget->id,
                    'name' => $budget->name,
                    'color' => $budget->color ?? '#0d9488',
                    'type' => $budget->type ?? Budget::TYPE_PRIMARY,
                    'initial_amount' => (float)$budget->initial_amount,
                    'remaining_amount' => (float)$budget->remaining_amount,
                    'is_current' => ($currentBudgetId !== null && $budget->id == $currentBudgetId) ||
                                   ($currentBudgetId === null && ($budget->type === Budget::TYPE_PRIMARY || $budget->type === null))
                ];
            }

            return $this->jsonResponse([
                'success' => true,
                'budgets' => $budgetList,
                'current_budget_id' => $currentBudgetId
            ]);

        } catch (\Exception $e) {
            error_log("Erreur getActiveBudgets: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Switcher vers un autre budget
     */
    public function switchBudget()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $userId = (int)$_SESSION['user_id'];
            $data = json_decode(file_get_contents('php://input'), true);
            $budgetId = (int)($data['budget_id'] ?? 0);

            if ($budgetId <= 0) {
                return $this->jsonResponse(['success' => false, 'message' => 'ID de budget invalide'], 400);
            }

            // Vérifier que le budget appartient à l'utilisateur et est actif
            $budget = Budget::getById($budgetId, $userId);
            if (!$budget || $budget->status !== Budget::STATUS_ACTIVE) {
                return $this->jsonResponse(['success' => false, 'message' => 'Budget non trouvé ou inactif'], 404);
            }

            // Stocker le budget actif en session
            $_SESSION['current_budget_id'] = $budgetId;

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Budget changé avec succès',
                'budget' => [
                    'id' => $budget->id,
                    'name' => $budget->name,
                    'color' => $budget->color ?? '#0d9488',
                    'type' => $budget->type ?? Budget::TYPE_PRIMARY,
                    'initial_amount' => (float)$budget->initial_amount,
                    'remaining_amount' => (float)$budget->remaining_amount
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Erreur switchBudget: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Clôturer un budget secondaire
     */
    public function closeBudget()
    {
        try {
            if (!isset($_SESSION['user_id'])) {
                return $this->jsonResponse(['success' => false, 'message' => 'Non authentifié'], 401);
            }

            $userId = (int)$_SESSION['user_id'];
            $data = json_decode(file_get_contents('php://input'), true);
            $budgetId = (int)($data['budget_id'] ?? 0);

            if ($budgetId <= 0) {
                return $this->jsonResponse(['success' => false, 'message' => 'ID de budget invalide'], 400);
            }

            $budget = Budget::closeSecondaryBudget($budgetId, $userId);

            // Si c'était le budget actif en session, revenir au principal
            if (isset($_SESSION['current_budget_id']) && $_SESSION['current_budget_id'] == $budgetId) {
                unset($_SESSION['current_budget_id']);
            }

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Budget clôturé avec succès'
            ]);

        } catch (\Exception $e) {
            error_log("Erreur closeBudget: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
