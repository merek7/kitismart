<?php

namespace App\Models;

use RedBeanPHP\R as R;

class User{
    private $id;
    private $nom;
    private $email;
    private $password;
    private $status;
    private $created_at;
    private $updated_at;


    public static function create(array $data): ?int{
        $user = R::dispense('users');
        $user->nom = $data['nom'];
        $user->email = $data['email'];
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->status = 'active';
        $user->created_at = date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');
        return R::store($user);
    }

    public static function findByEmail(string $email): ?object{
        return R::findOne('vw_users', 'email = ?', [$email]);
    }

    public static function findById(int $id): ?object {
        return R::findOne('vw_users', 'id = ?', [$id]);
    }

    public static function updateStatus(int $id, string $status): bool {
        try {
            $user = R::load('users', $id);
            if ($user->id) {
                $user->status = $status;
                $user->updated_at = date('Y-m-d H:i:s');
                R::store($user);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            error_log("Erreur lors de la mise à jour du statut: " . $e->getMessage());
            return false;
        }
    }
}