<?php

namespace App\Models;

use RedBeanPHP\R;


class ExpenseAudit {
    // creons l'audit de la demande

    public static function log($action , $data){
        R::begin();
        try{
            $audit = R::dispense('expense_audit');
            $audit->action = $action;
            $audit->data = json_encode($data);
            $audit->created_at = date('Y-m-d H:i:s');
            R::store($audit);
            R::commit();
        }catch(\Exception $e){
            R::rollback();
        }
    }
}
