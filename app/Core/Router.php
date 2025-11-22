<?php 
namespace App\Core;

use AltoRouter;

class Router
{
    private $router;
    private $controllerNamespace = 'App\Controllers\\';

    public function __construct()
    {
        $this->router = new AltoRouter();
       
    }

    public function addRoutes(array $routes)
    {
        foreach ($routes as $route) {
            $this->router->map(
                $route[0], // method
                $route[1], // route
                $route[2], // target
                $route[3] ?? null // name
            );
            error_log("Route added: " . json_encode($route));
        }
    }

    public function run()
{
    $match = $this->router->match();
    error_log("Current URL: " . $_SERVER['REQUEST_URI']);
    error_log("Match: " . json_encode($match));

    if ($match) {
        if (is_string($match['target'])) {
            list($controller, $action) = explode('#', $match['target']);
            $controller = $this->controllerNamespace . $controller;

            error_log("Controller: " . $controller);
            error_log("Action: " . $action);

            if (class_exists($controller)) {
                $controller = new $controller();
                if (method_exists($controller, $action)) {
                    call_user_func_array([$controller, $action], $match['params']);
                    return;
                }
                error_log("Method {$action} not found in {$controller}");
            } else {
                error_log("Controller not found: " . $controller);
            }
        }
    } else {
        error_log("No route matched for URL: " . $_SERVER['REQUEST_URI']);
    }

    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    echo "404: Page not found";
}

    public function getRoutes()
    {
        return $this->router->getRoutes();
    }

    public function setBasePath(string $basePath)
    {
        $this->router->setBasePath($basePath);
    }
}