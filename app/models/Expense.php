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
            $isFixed = $data['category_type'] === Categorie::TYPE_FIXE;

            $categorie = Categorie::findByType($data['category_type']);
            
            $expense = R::dispense('expense');
            $expense->import([
                'budget_id' => $data['budget_id'],
                'categorie_id' => $categorie->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'description' => $data['description'],
                'is_fixed' => $isFixed,
                'status' => $data['status'] ?? self::STATUS_PENDING,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => null,
                'paid_at' => null
            ]);

            $budget->remaining_amount -= $data['amount'];

            R::store($expense);
            R::store($budget);
            R::commit();
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

            if($expense->is_replicated == true){
                $budget->remaining_amount -= $expense->amount;
                R::store($budget);
            }


            $expense->status = self::STATUS_PAID;
            $expense->paid_at = R::isoDateTime();
            $expense->updated_at = R::isoDateTime();

            R::store($expense);
            R::commit();
            return $expense;
        } catch (\Exception $e) {
            R::rollback();
            throw new \Exception('Erreur lors de la mise à jour de la dépense: ' . $e->getMessage());
        }
    }

    public static function getExpensesByUser($budgetId, $userId)
    {
        return R::find(
            'expense',
            'budget_id = ? AND budget_id IN (SELECT id FROM budget WHERE user_id = ?)',
            [$budgetId, $userId]
        );
    }

    

    public static function getPaginatedExpensesByUser($budgetId, $userId, $page = 1)
    {
        $limit = 6;
        $offset = ($page - 1) * $limit;

        $expenses = R::find(
            'expense',
            'budget_id = ? AND budget_id IN (SELECT id FROM budget WHERE user_id = ?) ORDER BY created_at DESC LIMIT ? OFFSET ?',
            [$budgetId, $userId, $limit, $offset]
        );

        $total = R::count(
            'expense',
            'budget_id = ? AND budget_id IN (SELECT id FROM budget WHERE user_id = ?)',
            [$budgetId, $userId]
        );

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
        $expense = self::findById($id);
        if (!$expense) {
            throw new ExpenseNotFoundException();
        }

        R::trash($expense);
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
}
