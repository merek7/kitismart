<?php

namespace App\Models;

use RedBeanPHP\R as R;

class UserAudit {
    public static function log(int $userId, string $action, array $details = []): bool{
        
        try{
            $audit = R::dispense('useraudit');
            $audit->user_id = $userId;
            $audit->action = $action;
            $audit->details = json_encode($details);
            $audit->ip_address = $_SERVER['REMOTE_ADDR'];
            $audit->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Inconnu';
            $audit->created_at = date('Y-m-d H:i:s');

            R::store($audit);
            return true;
        }catch(\Exception $e){
            error_log("Erreur lors de l'audit: " . $e->getMessage());
            return false;
        }
    }

    public static function getUserHistory(int $userId): array{
        return R::find('user_audit', 'user_id = ? ORDER BY created_at DESC', [$userId]);
}
}
