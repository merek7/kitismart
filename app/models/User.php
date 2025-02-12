<?php

namespace App\Models;

use App\Exceptions\UserAlreadyExistsException;
use RedBeanPHP\R as R;

class User{
    private $id;
    private $nom;
    private $email;
    private $password;
    private $status;
    private $created_at;
    private $updated_at;
    private $confirmation_token;
    private $confirmation_expires;


    public static function create(array $data): ?int{
        error_log("Vérification de l'existence de l'utilisateur pour l'email: " . $data['email']);
        $existingUser = self::findByEmail($data['email']);
        if ($existingUser) {
            error_log("Utilisateur trouvé : " . print_r($existingUser, true));
            return null;
        }
        error_log("Aucun utilisateur trouvé, création en cours.");

        $user = R::dispense('users');
        $user->nom = $data['nom'];
        $user->email = $data['email'];
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->status = 'inactif';
        $user->confirmation_token = bin2hex(random_bytes(32));
        $user->confirmation_expires = date('Y-m-d H:i:s', strtotime('+20 minutes'));
        $user->created_at = date('Y-m-d H:i:s');
        
        return R::store($user) ? $user->id : null;
    }

    public static function findByEmail(string $email): ?object{
        return R::findOne('users', 'email = ?', [$email]);
    }

    public static function findById(int $id): ?object {
        return R::findOne('vw_users', 'id = ?', [$id]);
    }

    public static function confirmAccount(string $token): bool {
        try{
            $user = R::findOne('users', 'confirmation_token = ? AND confirmation_expires > ?', 
            [$token, date('Y-m-d H:i:s')]);

            if(!$user){
                throw new \Exception("Token invalide ou expiré.");
            }
                $user->status = 'active';
                $user->confirmation_token = null;
                $user->confirmation_expires = null;
                $user->updated_at = date('Y-m-d H:i:s');
                R::store($user);
                return true;
            }
            catch(\Exception $e){
                error_log("Erreur lors de la confirmation du compte: " . $e->getMessage());
                return false;
            }
        }

    public static function updateStatus($id, $status) {
        try {
            $user = R::load('users', $id);
            if (!$user->id) {
                error_log("Utilisateur non trouvé pour l'ID: " . $id);
                return false;
            }
            
            error_log("Mise à jour du statut pour l'utilisateur " . $id . " vers " . $status);
            
            $user->status = $status;
            $user->confirmation_token = null;  // On efface le token après confirmation
            $user->confirmation_expires = null;
            $user->updated_at = date('Y-m-d H:i:s');
            
            return R::store($user) ? true : false;
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la mise à jour du statut: " . $e->getMessage());
            return false;
        }
    }

    public static function getByConfirmationToken(string $token): ?object {
        error_log("Recherche token dans la base: " . $token);
        $user = R::findOne('users', 'confirmation_token = ?', [$token]);
        error_log("Résultat de la recherche: " . ($user ? "utilisateur trouvé" : "aucun utilisateur"));
        return $user;
    }

    public static function isActive(int $id): bool {
        $user = R::load('users', $id);
        return $user->status === 'active';
    }

    public static function update($user) {
        $bean = R::load('users', $user->id);
        $bean->import($user->export());
        return R::store($bean);
    }

    public static function findByResetToken(string $token): ?object {
        return R::findOne('users', 'reset_token = ? AND reset_expires > ?', [
            $token,
            date('Y-m-d H:i:s')
        ]);
    }
}