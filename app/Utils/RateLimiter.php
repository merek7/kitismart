<?php

namespace App\Utils;

/**
 * RateLimiter - Limitation du taux de requêtes
 *
 * Protège contre les attaques brute force et le spam
 * Stocke les tentatives en session avec timestamp
 */
class RateLimiter
{
    /**
     * Vérifier si une action est limitée
     *
     * @param string $action Identifiant de l'action (login, register, etc.)
     * @param int $maxAttempts Nombre maximum de tentatives
     * @param int $timeWindow Fenêtre de temps en secondes
     * @return bool True si l'action est autorisée, False si limitée
     */
    public static function check(string $action, int $maxAttempts, int $timeWindow): bool
    {
        $key = self::getKey($action);
        $attempts = self::getAttempts($key);

        // Nettoyer les tentatives expirées
        $attempts = self::cleanExpiredAttempts($attempts, $timeWindow);

        // Vérifier si le nombre de tentatives est dépassé
        if (count($attempts) >= $maxAttempts) {
            self::log("Rate limit exceeded for {$action}");
            return false;
        }

        return true;
    }

    /**
     * Enregistrer une tentative
     *
     * @param string $action Identifiant de l'action
     */
    public static function hit(string $action): void
    {
        $key = self::getKey($action);
        $attempts = self::getAttempts($key);

        // Ajouter la nouvelle tentative avec timestamp
        $attempts[] = time();

        // Sauvegarder en session
        $_SESSION['rate_limiter'][$key] = $attempts;
    }

    /**
     * Réinitialiser le compteur pour une action
     *
     * @param string $action Identifiant de l'action
     */
    public static function reset(string $action): void
    {
        $key = self::getKey($action);

        if (isset($_SESSION['rate_limiter'][$key])) {
            unset($_SESSION['rate_limiter'][$key]);
        }
    }

    /**
     * Obtenir le temps restant avant déblocage
     *
     * @param string $action Identifiant de l'action
     * @param int $timeWindow Fenêtre de temps en secondes
     * @return int Secondes restantes (0 si débloqué)
     */
    public static function getRetryAfter(string $action, int $timeWindow): int
    {
        $key = self::getKey($action);
        $attempts = self::getAttempts($key);

        if (empty($attempts)) {
            return 0;
        }

        // Trouver la tentative la plus ancienne
        $oldestAttempt = min($attempts);
        $unlockTime = $oldestAttempt + $timeWindow;
        $now = time();

        return max(0, $unlockTime - $now);
    }

    /**
     * Obtenir le nombre de tentatives restantes
     *
     * @param string $action Identifiant de l'action
     * @param int $maxAttempts Nombre maximum de tentatives
     * @param int $timeWindow Fenêtre de temps en secondes
     * @return int Nombre de tentatives restantes
     */
    public static function getRemainingAttempts(string $action, int $maxAttempts, int $timeWindow): int
    {
        $key = self::getKey($action);
        $attempts = self::getAttempts($key);
        $attempts = self::cleanExpiredAttempts($attempts, $timeWindow);

        return max(0, $maxAttempts - count($attempts));
    }

    /**
     * Générer la clé unique pour l'action et l'IP
     *
     * @param string $action Identifiant de l'action
     * @return string Clé unique
     */
    private static function getKey(string $action): string
    {
        $ip = self::getClientIp();
        return md5($action . '_' . $ip);
    }

    /**
     * Récupérer les tentatives pour une clé
     *
     * @param string $key Clé unique
     * @return array Tableau des timestamps
     */
    private static function getAttempts(string $key): array
    {
        if (!isset($_SESSION['rate_limiter'])) {
            $_SESSION['rate_limiter'] = [];
        }

        return $_SESSION['rate_limiter'][$key] ?? [];
    }

    /**
     * Nettoyer les tentatives expirées
     *
     * @param array $attempts Tableau des timestamps
     * @param int $timeWindow Fenêtre de temps en secondes
     * @return array Tentatives valides
     */
    private static function cleanExpiredAttempts(array $attempts, int $timeWindow): array
    {
        $now = time();

        return array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
    }

    /**
     * Obtenir l'adresse IP du client
     *
     * @return string Adresse IP
     */
    private static function getClientIp(): string
    {
        // Vérifier les headers de proxy
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxy standard
            'HTTP_X_REAL_IP',        // Nginx
            'REMOTE_ADDR'            // Direct
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header]) && !empty($_SERVER[$header])) {
                // Prendre la première IP en cas de liste
                $ip = explode(',', $_SERVER[$header])[0];
                $ip = trim($ip);

                // Valider l'IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Logger les événements de rate limiting
     *
     * @param string $message Message à logger
     */
    private static function log(string $message): void
    {
        $ip = self::getClientIp();
        $timestamp = date('Y-m-d H:i:s');
        error_log("[{$timestamp}] [RateLimiter] {$message} - IP: {$ip}");
    }

    /**
     * Nettoyer toutes les données de rate limiting (pour maintenance)
     */
    public static function clearAll(): void
    {
        if (isset($_SESSION['rate_limiter'])) {
            unset($_SESSION['rate_limiter']);
        }

        self::log("All rate limiter data cleared");
    }
}
