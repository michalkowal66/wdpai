<?php

class AppController {

    public function __construct()
    {
        $path = trim($_SERVER["REQUEST_URI"], '/');
        $path = parse_url($path, PHP_URL_PATH) ?: '';

        // Protected routes check
        $protectedRoutes = ['map', 'bookings'];

        if (in_array($path, $protectedRoutes) && !isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        // Admin routes check
        $adminRoutes = ['users', 'dashboard', 'desks'];
        $adminApiRoutes = ['updateUser', 'deleteUser', 'setMaintenance'];
        
        if (in_array($path, $adminRoutes) && (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN')) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/map");
            exit();
        }

        if (in_array($path, $adminApiRoutes) && (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN')) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Forbidden: Admin access required']);
            exit();
        }

        // Redirect logged-in users away from public auth pages
        $publicPages = ['login', 'register', ''];
        if (in_array($path, $publicPages) && isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/map");
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