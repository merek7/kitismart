<?php
namespace App\Utils;

use App\Exceptions\TokenInvalidOrExpiredException;

class Csrf {
    public static function generateToken(): string {
        if(!isset($_SESSION)) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateToken($token) {
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new TokenInvalidOrExpiredException();
        }
        return true;
    }

    /**
     * Vérifie le token sans lancer d'exception (retourne un booléen)
     */
    public static function verifyToken($token): bool {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function destroyToken() {
        unset($_SESSION['csrf_token']);
        return self::generateToken();
    }
}