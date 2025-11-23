<?php
namespace App\Core;

use Dotenv\Dotenv;

class Config {
    public static function init() {
        $envPath = __DIR__.'/../../';

        // Use safeLoad to avoid errors if .env doesn't exist
        // Environment variables can come from Docker/Coolify at runtime
        $dotenv = Dotenv::createImmutable($envPath);

        // Only load .env file if it exists, don't fail otherwise
        // This allows environment variables to be set by the container
        if (file_exists($envPath . '.env')) {
            $dotenv->safeLoad();
        }
    }

    public static function get($key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}