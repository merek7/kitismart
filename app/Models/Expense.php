<?php

namespace App\Models;

use App\Exceptions\BudgetNotFoundException;
use RedBeanPHP\R as R;
use App\Exceptions\InvalidExpenseDataException;
use App\Exceptions\ExpenseNotFoundException;

class Expense
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';

    public static function create(array $data)
    {

        self::validateExpenseData($data);
        R::begin();
        try {
            $budget = R::findOne(
                'budget',
                'id = ? AND user_id = ? AND status = ?',
                [$data['budget_id'], $data['user_id'], Budget::STATUS_ACTIVE]
            );
            error_log(print_r($budget, true));

            if (!$budget) {
                throw new BudgetNotFoundException("Budget non trouvé ou inactif");
            }

            // Vérifier si c'est une catégorie personnalisée
            $isCustomCategory = str_starts_with($data['category_type'], 'custom_');
            $customCategoryId = null;
            $categorieId = null;
            $isFixed = false;

            if ($isCustomCategory) {
                // Extraire l'ID de la catégorie personnalisée
                $customCategoryId = (int)str_replace('custom_', '', $data['category_type']);
                // Pour les catégories personnalisées, mettre categorie_id à null
                $categorieId = null;
            } else {
                // Catégorie par défaut
                $isFixed = $data['category_type'] === Categorie::TYPE_FIXE;
                $categorie = Categorie::findByType($data['category_type']);
                $categorieId = $categorie->id;
            }

            $expense = R::dispense('expense');
            $expense->import([
                'budget_id' => $data['budget_id'],
                'categorie_id' => $categorieId,
                'custom_category_id' => $customCategoryId,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'description' => $data['description'],
                'is_fixed' => $isFixed,
                'status' => $data['status'] ?? self::STATUS_PENDING,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => null,
                'paid_at' => null,
                // Champs pour les dépenses créées par un invité
                'guest_name' => $data['guest_name'] ?? null,
                'guest_share_id' => $data['guest_share_id'] ?? null,
                // Liaison avec un objectif d'épargne
                'savings_goal_id' => $data['savings_goal_id'] ?? null
            ]);

            $budget->remaining_amount -= $data['amount'];

            R::store($expense);
            R::store($budget);

            // Si la dépense est liée à un objectif d'épargne, mettre à jour l'objectif
            if (!empty($data['savings_goal_id'])) {
                self::updateSavingsGoal((int)$data['savings_goal_id'], (float)$data['amount'], $data['user_id']);
            }

            R::commit();

            // Déclencher les notifications après le commit
            try {
                // Notification pour dépense importante
                \App\Controllers\NotificationController::sendExpenseAlert($data['user_id'], $expense);

                // Notification pour seuil de budget (80% ou 100%)
                $usagePercentage = $budget->initial_amount > 0 ?
                    (($budget->initial_amount - $budget->remaining_amount) / $budget->initial_amount) * 100 : 0;

                if ($usagePercentage >= 80) {
                    \App\Controllers\NotificationController::sendBudgetAlert($data['user_id'], $usagePercentage, $budget);
                }
            } catch (\Exception $e) {
                error_log("Erreur lors de l'envoi des notifications: " . $e->getMessage());
                // Ne pas faire échouer la création de la dépense si les notifications échouent
            }

            return $expense;
        } catch (\Exception $e) {
            R::rollback();
            throw new \Exception('Erreur lors de la création de la dépense: ' . $e->getMessage());
        }
    } 



    public static function getExpensesByBudget($budgetId)
    {
        return R::find('expense', 'budget_id = ?', [$budgetId]);
    }

    private static function validateExpenseData(array $data)
    {
        $required = ['budget_id', 'category_type', 'amount', 'payment_date'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidExpenseDataException("Le champ $field est requis");
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new InvalidExpenseDataException("Le montant doit être un nombre positif");
        }
    }

    public static function markAsPaid($expenseId, $userId)
    {
        R::begin();
        try {
            $expense = R::findOne(
                'expense',
                'id = ? AND 
            budget_id IN (SELECT id FROM budget WHERE user_id = ?)',
                [$expenseId, $userId]
            );

            if (!$expense) {
                throw new ExpenseNotFoundException();
            }

            $budget = R::load('budget', $expense->budget_id);
            if (!$budget) {
                throw new BudgetNotFoundException();
            }

            $budgetChanged = false;
            if($expense->is_replicated == true){
                $budget->remaining_amount -= $expense->amount;
                R::store($budget);
                $budgetChanged = true;
            }


            $expense->status = self::STATUS_PAID;
            $expense->paid_at = R::isoDateTime();
            $expense->updated_at = R::isoDateTime();

            R::store($expense);
            R::commit();

            // Déclencher les notifications si le budget a changé
            if ($budgetChanged) {
                try {
                    // Notification pour seuil de budget (80% ou 100%)
                    $usagePercentage = $budget->initial_amount > 0 ?
                        (($budget->initial_amount - $budget->remaining_amount) / $budget->initial_amount) * 100 : 0;

                    if ($usagePercentage >= 80) {
                        \App\Controllers\NotificationController::sendBudgetAlert($userId, $usagePercentage, $budget);
                    }
                } catch (\Exception $e) {
                    error_log("Erreur lors de l'envoi des notifications: " . $e->getMessage());
                }
            }

            return $expense;
        } catch (\Exception $e) {
            R::rollback();
            throw new \Exception('Erreur lors de la mise à jour de la dépense: ' . $e->getMessage());
        }
    }

    public static function getExpensesByUser($budgetId, $userId = null)
    {
        // Si userId est null (accès invité), récupérer directement par budget_id
        if ($userId === null) {
            return R::find(
                'expense',
                'budget_id = ? ORDER BY payment_date DESC',
                [$budgetId]
            );
        }

        // Sinon, vérifier que le budget appartient à l'utilisateur
        return R::find(
            'expense',
            'budget_id = ? AND budget_id IN (SELECT id FROM budget WHERE user_id = ?) ORDER BY payment_date DESC',
            [$budgetId, $userId]
        );
    }

    

    public static function getPaginatedExpensesByUser($budgetId, $userId, $page = 1)
    {
        // LOG DE DÉBOGAGE
        error_log("=== getPaginatedExpensesByUser DEBUG ===");
        error_log("budgetId received: " . var_export($budgetId, true) . " (type: " . gettype($budgetId) . ")");
        error_log("userId received: " . var_export($userId, true) . " (type: " . gettype($userId) . ")");
        error_log("page received: " . var_export($page, true) . " (type: " . gettype($page) . ")");

        // Valider TOUS les paramètres
        $budgetId = (int)$budgetId;
        $userId = (int)$userId;
        $page = max(1, (int)$page);

        $limit = 6;
        $offset = ($page - 1) * $limit;

        error_log("After validation: budgetId=$budgetId, userId=$userId, page=$page, limit=$limit, offset=$offset");
        error_log("SQL params array: " . var_export([$budgetId, $userId, $limit, $offset], true));

        error_log("BEFORE R::find() query");
        $expenses = R::find(
            'expense',
            'budget_id = ? AND budget_id IN (SELECT id FROM budget WHERE user_id = ?) ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$budgetId, $userId, $limit, $offset]
        );
        error_log("AFTER R::find() query - Found " . count($expenses) . " expenses");

        error_log("BEFORE R::count() query");
        $total = R::count(
            'expense',
            'budget_id = ? AND budget_id IN (SELECT id FROM budget WHERE user_id = ?)',
            [$budgetId, $userId]
        );
        error_log("AFTER R::count() query - Total: $total");

        return [
            'expenses' => $expenses,
            'total' => $total,
            'current_page' => $page,
            'next_page' => $page + 1,
            'previous_page' => $page - 1,
            'per_page' => $limit,
            'last_page' => ceil($total / $limit)
        ];
    }

    public static function findById($id)
    {
        return R::load('expense', $id);
    }

    public static function update($id, array $data)
    {
        R::begin();
        try {
            $expense = self::findById($id);
            if(!$expense || $expense->id ==0){
                throw new ExpenseNotFoundException();
            }    

            $oldAmount = $expense->amount;

            $updateData = [];

            foreach(['amount','paid_at','description','status'] as $field){
                if(isset($data[$field])){
                    $updateData[$field] = $data[$field];
                }
            }
            $categorie = Categorie::findByType($data['category_type']);

            if(isset($data['category_type'])){
                $updateData['categorie_id'] = $categorie->id;
                $updateData['is_fixed'] = $data['category_type'] === Categorie::TYPE_FIXE;
            }

            $updateData['updated_at'] = R::isoDateTime();

            if(isset($data['status']) && $data['status'] === self::STATUS_PAID && $expense->status !== self::STATUS_PAID){
                $updateData['paid_at'] = R::isoDateTime();
            }

            $expense->import($updateData);

            if(isset($data['amount']) && $data['amount'] != $oldAmount && $expense->budget_id){
                $budget = R::load('budget', $expense->budget_id);
                if(!$budget || $budget->id != 0){
                $budget->remaining_amount += $oldAmount - $data['amount'];
                R::store($budget);
                }
            }

            R::store($expense);
            R::commit();
            return $expense;
        } catch (\Exception $e) {
            R::rollback();
            throw new \Exception('Erreur lors de la mise à jour de la dépense: ' . $e->getMessage());
        }
    }

    public static function delete($id)
    {
        R::begin();
        try {
            $expense = self::findById($id);
            if (!$expense) {
                throw new ExpenseNotFoundException();
            }

            // Si la dépense est liée à un objectif d'épargne, annuler la contribution
            if (!empty($expense->savings_goal_id)) {
                self::revertSavingsGoal((int)$id);
            }

            // Rembourser le budget si la dépense est déjà payée
            if ($expense->budget_id) {
                $budget = R::load('budget', $expense->budget_id);
                if ($budget && $budget->id) {
                    $budget->remaining_amount += $expense->amount;
                    R::store($budget);
                }
            }

            R::trash($expense);
            R::commit();
        } catch (\Exception $e) {
            R::rollback();
            throw new \Exception('Erreur lors de la suppression de la dépense: ' . $e->getMessage());
        }
    }

    public static function getTotalPendingExpensesByUser($budgetId, $userId)
    {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                   FROM expense 
                   WHERE budget_id = ? 
                   AND status = ? 
                   AND user_id = ?";
                   
            $row = R::getRow($sql, [$budgetId, self::STATUS_PENDING, $userId]);
            return floatval($row['total']);
        } catch (\Exception $e) {
            error_log("Erreur lors du calcul des dépenses en attente : " . $e->getMessage());
            return 0;
        }
    }

    /**
    * Calcule la somme des dépenses en attente pour un budget spécifique
    * 
    * @param int $budgetId L'identifiant du budget
    * @param int $userId L'identifiant de l'utilisateur 
    * @return float La somme des dépenses en attente
    */
   public static function getPendingExpensesByUser($budgetId, $userId) {
       try {
           $result = R::getCell(
            //SELECT COALESCE(SUM(amount), 0) 
            //FROM expense e
            //join budget b ON e.budget_id = b.id
            //WHERE e.status = 'pending' and b.user_id = 1 and e.budget_id= 4 

               'SELECT COALESCE(SUM(amount), 0) FROM expense e 
               join budget b ON e.budget_id = b.id
               WHERE e.status = ? and b.user_id = ? and e.budget_id= ?',
               ['pending', $userId, $budgetId]
           );
           
           return (float) $result;
       } catch (\Exception $e) {
           throw new \Exception('Erreur lors du calcul des dépenses en attente: ' . $e->getMessage());
       }
   }

    /**
     * Mettre à jour un objectif d'épargne lors de la création d'une dépense épargne
     */
    private static function updateSavingsGoal(int $goalId, float $amount, int $userId): void
    {
        try {
            $goal = R::findOne('savingsgoal', 'id = ? AND user_id = ? AND status = ?',
                [$goalId, $userId, SavingsGoal::STATUS_ACTIVE]);

            if (!$goal) {
                error_log("Objectif d'épargne non trouvé: $goalId pour user $userId");
                return;
            }

            // Enregistrer la contribution
            $contribution = R::dispense('savingscontribution');
            $contribution->import([
                'goal_id' => $goalId,
                'user_id' => $userId,
                'amount' => $amount,
                'note' => 'Dépense épargne automatique',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            R::store($contribution);

            // Mettre à jour le montant actuel de l'objectif
            $goal->current_amount = (float)$goal->current_amount + $amount;

            // Vérifier si l'objectif est atteint
            if ($goal->current_amount >= $goal->target_amount) {
                $goal->status = SavingsGoal::STATUS_COMPLETED;
                $goal->completed_at = date('Y-m-d H:i:s');
            }

            $goal->updated_at = date('Y-m-d H:i:s');
            R::store($goal);

        } catch (\Exception $e) {
            error_log("Erreur mise à jour objectif épargne: " . $e->getMessage());
            // Ne pas faire échouer la création de la dépense
        }
    }

    /**
     * Annuler la contribution à un objectif d'épargne (lors de suppression de dépense)
     */
    public static function revertSavingsGoal(int $expenseId): void
    {
        try {
            $expense = R::load('expense', $expenseId);
            if (!$expense || !$expense->savings_goal_id) {
                return;
            }

            $goal = R::load('savingsgoal', $expense->savings_goal_id);
            if (!$goal || !$goal->id) {
                return;
            }

            // Réduire le montant de l'objectif
            $goal->current_amount = max(0, (float)$goal->current_amount - (float)$expense->amount);

            // Si l'objectif était complété, le remettre actif
            if ($goal->status === SavingsGoal::STATUS_COMPLETED && $goal->current_amount < $goal->target_amount) {
                $goal->status = SavingsGoal::STATUS_ACTIVE;
                $goal->completed_at = null;
            }

            $goal->updated_at = date('Y-m-d H:i:s');
            R::store($goal);

            // Enregistrer le retrait dans l'historique
            $contribution = R::dispense('savingscontribution');
            $contribution->import([
                'goal_id' => $expense->savings_goal_id,
                'user_id' => $goal->user_id,
                'amount' => -(float)$expense->amount,
                'note' => 'Annulation dépense épargne',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            R::store($contribution);

        } catch (\Exception $e) {
            error_log("Erreur annulation objectif épargne: " . $e->getMessage());
        }
    }
}
