<?php

namespace App\Models;

use RedBeanPHP\R;


class ExpenseAudit {
    public static function log($action , $data){
        $audit = R::dispense('expense_audit');
        $audit->action = $action;
        $audit->data = json_encode($data);
        $audit->created_at = date('Y-m-d H:i:s');
        return R::store($audit);
    }
}
