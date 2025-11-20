<?php

namespace App\Models;

use DateInterval;
use DateTime;
use RedBeanPHP\R as R;
use App\Exceptions\BudgetNotFoundException;

class Budget {

    const STATUS_ACTIVE = 'actif';
    const STATUS_CLOSED = 'cloturer';

    public static function create(array $data) {

        self::validateBudgetData($data);


        R::begin();
        try {
            $previousBudget = self::getActiveBudget($data['user_id']);
            if($previousBudget) {
                self::closeBudget($previousBudget, $data['start_date']);
            }

            $budget = R::dispense('budget');
            $budget->user_id = $data['user_id'];
            $budget->start_date = $data['start_date'];
            $budget->end_date = null;
            $budget->initial_amount = $data['initial_amount'];
            $budget->remaining_amount = $data['initial_amount'];
            $budget->status = self::STATUS_ACTIVE;
            $budget->created_at = date('Y-m-d H:i:s');

            R::store($budget);

            // Call the static method
            if ($previousBudget) {
                self::replicateFixedCharges($previousBudget, $budget);
                ExpenseAudit::log(
                    'Budget',[
                        'ancien budget' => $previousBudget->id,
                        'nouveau budget' => $budget->id,
                    ],
                    'Budget clôturé et nouvelle période commencée'
                );
            }

            R::commit();
            return $budget;
        } catch (\Exception $e) {
            R::rollback();
            throw new \Exception('Erreur lors de la création du budget: ' . $e->getMessage());
        }
    }

    private static function validateBudgetData(array $data) {
        $required = ['user_id', 'start_date', 'initial_amount'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Le champ $field est requis");
            }
        }
        if (!is_numeric($data['initial_amount']) || $data['initial_amount'] <= 0) {
            throw new \Exception("Le montant initial doit être un nombre positif");
        }
    }

    public static function getActiveBudget($userId) {
        return R::findOne('budget', 'user_id = ? AND status = ? ORDER BY start_date DESC', [$userId, self::STATUS_ACTIVE]);
    }

    private static function closeBudget($budget, $endDate) {
        $budget->end_date = $endDate;
        $budget->status = self::STATUS_CLOSED;
        $budget->closed_at = date('Y-m-d H:i:s');
        R::store($budget);
    }

    private static function replicateFixedCharges($oldBudget, $newBudget) {
        $fixedCharges = R::find('expense', 'budget_id = ? AND is_fixed = TRUE', [$oldBudget->id]);

        foreach ($fixedCharges as $charge) {
            $newDate = self::calculateNewPaymentDate(
                $charge->payment_date,
                $oldBudget->start_date,
                $newBudget->start_date
            );

            $newCharge = R::dispense('expense');
            $newCharge->import([
                'budget_id' => $newBudget->id,
                'payment_date' => $newDate,
                'amount' => $charge->amount,
                'description' => $charge->description,
                'is_fixed' => true,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'is_replicated' => true
            ]);
            R::store($newCharge);
        }
    }

    private static function calculateNewPaymentDate($originalDate, $oldStartDate, $newStartDate) {
        try {
            $originalDateTime = new DateTime($originalDate);
            $oldStartDate = new DateTime($oldStartDate);
            $newStartDate = new DateTime($newStartDate);
            
            $daysDifference = $originalDateTime->diff($oldStartDate)->days;
            return $newStartDate->add(new DateInterval("P{$daysDifference}D"))->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception('Erreur lors du calcul de la nouvelle date de paiement: ' . $e->getMessage());
        }
    }

    public static function getBudgetSummary($budgetId) {
        $budget = R::load('budget', $budgetId);
        if(!$budget->id) {
            throw new \Exception('Budget non trouvé');
        }

       $expenses = R::getAll(
        'select category, SUM(amount) as total,
        count(*) as count
        FROM expense
        WHERE budget_id = ?
        GROUP BY category',
        [$budgetId]
       );

       return [
        'budget' => $budget,
        'expenses_categories' => $expenses,
        'montant_restant' => $budget->remaining_amount,
       ];
    }

    /**
     * Récupère les budgets précédents d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @param int $limit Nombre maximum de budgets à retourner (défaut: 10)
     * @return array Liste des budgets triés par date de début décroissante
     */
    public static function getPreviousBudgets($userId, $limit = 10) {
        return R::find('budget',
            'user_id = ? ORDER BY start_date DESC LIMIT ?',
            [$userId, $limit]
        );
    }
} 