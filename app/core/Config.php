<?php
namespace App\Core;

use Dotenv\Dotenv;

class Config {
    public static function init() {
        $dotenv = Dotenv::createImmutable(__DIR__.'/../../');
        $dotenv->load();
    }

    public static function get($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}