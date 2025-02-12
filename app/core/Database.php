<?php
namespace App\Core;
use PDO;
use PDOException;
use RedBeanPHP\R;
use App\Core\DataBaseException;
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                'mysql:host=' . $_ENV['DB_HOST'] .
                ';dbname=' . $_ENV['DB_NAME'] .
                ';charset=' . $_ENV['DB_CHARSET'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false
                ]
            );
        } catch (PDOException $e) {
            error_log('[SECURITY] Database connection attempt failed: ' . $e->getMessage());
            throw new \RuntimeException('Service temporairement indisponible');
        }
    }
    public static function getInstance(): self {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function getConnection(): PDO {
        return $this->connection;
    }
    // Méthode helper pour exécuter les requêtes
    public function query(string $sql, array $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur SQL: " . $e->getMessage());
            throw new DataBaseException();
        }
    }
    public static function initRedBean() {
       
            R::setup(
                'mysql:host=' . $_ENV['DB_HOST'] .
                ';dbname=' . $_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASS']
            );
           
            R::useFeatureSet('novice/latest');
            R::freeze(false); // En développement seulement
       
    }
}