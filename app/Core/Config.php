<?php
namespace App\Core;

use Dotenv\Dotenv;

class Config {
    // Version des assets - incrémentez à chaque déploiement pour invalider le cache
    public const ASSETS_VERSION = '1.1.0';

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

    /**
     * Retourne l'URL d'un asset avec version pour cache busting
     * @param string $path Chemin de l'asset (ex: /assets/css/style.css)
     * @return string URL avec paramètre de version
     */
    public static function asset(string $path): string {
        return $path . '?v=' . self::ASSETS_VERSION;
    }
}