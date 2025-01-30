<?php
namespace App\Utils;

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

    public static function validatetoken($token) {
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new \Exception('Token CSRF invalide');
        }
        return true;
    }

    public static function destroyToken() {
        unset($_SESSION['csrf_token']);
        return self::generateToken();
    }
}