<?php

namespace App\Core;

use PDO;
use PDOException;
use RedBeanPHP\R;
use App\Core\DataBaseException;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            // Déterminer le driver à utiliser (par défaut pgsql)
            $driver = $_ENV['DB_DRIVER'] ?? 'pgsql';
            $port = $_ENV['DB_PORT'] ?? ($driver === 'pgsql' ? '5432' : '3306');

            if ($driver === 'pgsql') {
                $dsn = 'pgsql:host=' . $_ENV['DB_HOST'] .
                       ';port=' . $port .
                       ';dbname=' . $_ENV['DB_NAME'];
            } else {
                $dsn = 'mysql:host=' . $_ENV['DB_HOST'] .
                       ';port=' . $port .
                       ';dbname=' . $_ENV['DB_NAME'] .
                       ';charset=' . ($_ENV['DB_CHARSET'] ?? 'utf8mb4');
            }

            $this->connection = new PDO(
                $dsn,
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false
                ]
            );

            // Pour PostgreSQL, configurer l'encodage
            if ($driver === 'pgsql') {
                $this->connection->exec("SET NAMES 'UTF8'");
            }
        } catch (PDOException $e) {
            error_log('[SECURITY] Database connection attempt failed: ' . $e->getMessage());
            throw new \RuntimeException('Service temporairement indisponible');
        }
    }
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function getConnection(): PDO
    {
        return $this->connection;
    }
    // Méthode helper pour exécuter les requêtes
    public function query(string $sql, array $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage());
            throw new DataBaseException();
        }
    }
    public static function initRedBean()
    {
        // Déterminer le driver à utiliser (par défaut pgsql)
        $driver = $_ENV['DB_DRIVER'] ?? 'pgsql';
        $port = $_ENV['DB_PORT'] ?? ($driver === 'pgsql' ? '5432' : '3306');

        if ($driver === 'pgsql') {
            $dsn = 'pgsql:host=' . $_ENV['DB_HOST'] .
                   ';port=' . $port .
                   ';dbname=' . $_ENV['DB_NAME'];
        } else {
            $dsn = 'mysql:host=' . $_ENV['DB_HOST'] .
                   ';port=' . $port .
                   ';dbname=' . $_ENV['DB_NAME'];
        }

        R::setup(
            $dsn,
            $_ENV['DB_USER'],
            $_ENV['DB_PASS']
        );

        R::useFeatureSet('latest');

        // Mode fluid (dev) pour permettre les modifications automatiques du schéma
        $isProduction = ($_ENV['APP_ENV'] ?? 'prod') === 'prod';
        R::freeze(false);

        // Debug seulement en développement
        R::debug($isProduction ? false : true, 1);
    }

}
