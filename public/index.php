<?php
declare(strict_types=1);

// Détecter si l'utilisateur est sur mobile
function isMobileDevice(): bool {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i', $userAgent) === 1;
}

// Configurer la durée de session selon le device
if (isMobileDevice()) {
    // Mobile : session de 1 an (comportement app native)
    $sessionLifetime = 365 * 24 * 60 * 60; // 1 an en secondes
} else {
    // PC/Web : session de 24h
    $sessionLifetime = 24 * 60 * 60; // 24h en secondes
}

ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// Ne régénérer l'ID que périodiquement pour éviter les problèmes
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Stocker le type de device dans la session
$_SESSION['is_mobile'] = isMobileDevice();

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