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

            if (!$budget) {
                throw new BudgetNotFoundException("Budget non trouvé ou inactif");
            }

            // Créer ou récupérer la catégorie
            $categorie = R::findOne(
                'categorie',
                'type = ? AND budget_id = ?',
                [$data['category_type'], $data['budget_id']]
            );

            if (!$categorie) {
                $categorie = Categorie::create([
                    'type' => $data['category_type'],
                    'name' => $data['name'] ?? $data['category_type'],
                    'budget_id' => $data['budget_id'],
                    'description' => $data['description'] ?? null
                ]);
            }

            $expense = R::dispense('expense');
            $expense->import([
                'budget_id' => $data['budget_id'],
                'categorie_id' => $categorie->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'description' => $data['description'],
                'is_fixed' => $categorie->type === Categorie::TYPE_FIXE,
                'status' => $data['status'] ?? self::STATUS_PENDING,
                'created_at' => date('Y-m-d H:i:s'),
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

            $expense->status = self::STATUS_PAID;
            $expense->paid_at = date('Y-m-d H:i:s');
            $expense->update_at = date('Y-m-d H:i:s');

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

    public static function findById($id)
    {
        return R::load('expense', $id);
    }

    public static function update($id, array $data)
    {
        $expense = self::findById($id);
        if (!$expense) {
            throw new ExpenseNotFoundException();
        }

        $expense->import($data);
        R::store($expense);
        return $expense;
    }

    public static function delete($id)
    {
        $expense = self::findById($id);
        if (!$expense) {
            throw new ExpenseNotFoundException();
        }

        R::trash($expense);
    }
}
