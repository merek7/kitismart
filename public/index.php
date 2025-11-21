<?php
declare(strict_types=1);
session_start();
session_regenerate_id(true); 

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Exceptions/Exception.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\Router;
$_SERVER['BASE_URI'] = '';
Config::init();

if($_ENV['APP_ENV'] === 'dev') {
    
    $whoops = new Whoops\Run;
    $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler);
    $whoops->register();
} else{
    error_reporting(0);
    ini_set('display_errors', '0');
}

try {
    $db = Database::getInstance()->getConnection();

    Database::initRedBean();
} catch (PDOException $e) {
    die("<h2>❌ Erreur de connexion</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>");
}


$router = new Router();
$router->addRoutes(require __DIR__ . '/../app/routes.php');
$router->run();