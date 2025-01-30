<?php
namespace App\Core;

class Controller {
    private $scripts = [];
    private $styles = [];

    protected function view(string $view, array $data = [], string $layout = 'main') {
        
        // chargement des scripts et styles a la vue
        $this->autoloadViewAssets($view);
        
        error_log("View: " . print_r($this->scripts, true));

        // Chemin vers la vue
        $viewPath = dirname(__DIR__) . "/views/{$view}.php";
        error_log("View path: " . $viewPath);

        // Vérifier si la vue existe
        if (!file_exists($viewPath)) {
            error_log("Vue non trouvée: {$viewPath}");
            throw new \Exception("Vue non trouvée: {$view}");
        }


        //$data['scripts'] = $this->scripts;
        $data['pageScripts'] = $this->scripts;
        $data['styles'] = $this->styles;

        // Extraire les données pour les rendre accessibles dans la vue
        extract($data);

        // Capturer le contenu de la vue
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Chemin vers le layout
        $layoutPath = dirname(__DIR__) . "/views/layouts/{$layout}.php";
        error_log("Layout path: " . $layoutPath);

        // Vérifier si le layout existe
        if (!file_exists($layoutPath)) {
            error_log("Layout non trouvé: {$layoutPath}");
            throw new \Exception("Layout non trouvé: {$layout}");
        }

        // Inclure le layout et injecter le contenu de la vue
        require $layoutPath;
    }

    protected function redirect(string $url) {
        header("Location: {$url}");
        exit;
    }

    protected function isPostRequest() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    private function autoloadViewAssets(string $view) {

        $jsFile= "{$view}.js";
        $cssFile= "{$view}.css";

        $fullJsPath= dirname(dirname(__DIR__)) . "/public/assets/js/{$jsFile}";
        $fullCssPath= dirname(dirname(__DIR__)) . "/public/assets/css/{$cssFile}";

        error_log("Recuperation du chemin complet du fichier css: " . $fullCssPath);
        error_log("Recuperation du chemin complet du fichier js: " . $fullJsPath);

        if(file_exists($fullJsPath)) {
            error_log("Fichier js trouvé: " . $jsFile);
            $this->scripts[] = $jsFile;
            error_log("Scripts: " . print_r($this->scripts, true)); 
            //$this->addScript($jsFile);
        }

        if(file_exists($fullCssPath)) { 
           $this->styles[] = $cssFile;
        }

}
    protected function jsonResponse(array $data, int $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }
    
}