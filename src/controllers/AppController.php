<?php

class AppController {

    public function __construct()
    {
        // Protected routes check
        $protectedRoutes = ['dashboard', 'bookings', 'users'];
        $path = trim($_SERVER["REQUEST_URI"], '/');
        $path = parse_url($path, PHP_URL_PATH) ?: '';

        if (in_array($path, $protectedRoutes) && !isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }
    }

    protected function isGet(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    protected function isPost(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'POST';
    }
 
    protected function render(string $template = null, array $variables = [])
    {
        $templatePath = 'public/views/'. $template.'.html';
        $templatePath404 = 'public/views/404.html';
        $output = "";

        // Provide user context to all views if logged in
        if (isset($_SESSION['user']) && !isset($variables['user'])) {
            $variables['user'] = $_SESSION['user'];
        }
                 
        if(file_exists($templatePath)){
            extract($variables);
            
            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        } else {
            ob_start();
            include $templatePath404;
            $output = ob_get_clean();
        }
        echo $output;
    }

}