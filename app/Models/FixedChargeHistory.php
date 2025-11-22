<?php

namespace App\Models;

use RedBeanPHP\R as R;

class FixedChargeHistory {
    public static function create($userId, $originalExpenseId, $clonedExpenseId, $executionDate) {
        $history = R::dispense('fixed_charge_history');
        $history->user_id = $userId;
        $history->original_expense_id = $originalExpenseId;
        $history->cloned_expense_id = $clonedExpenseId;
        $history->execution_date = $executionDate;

        return R::store($history);
    }
} 