<?php

/**
 * Base Application Controller.
 * 
 * Provides foundational methods for rendering views, extracting HTTP methods, 
 * and handling common request lifecycles (like auth redirects and HTTPS enforcement).
 * 
 * @package Controllers
 */
class AppController {

    /**
     * Initializes the application controller, checks environment, enforces HTTPS,
     * and handles authentication redirection for protected and admin routes.
     */
    public function __construct()
    {
        $host = parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST);
        $isLocalhost = in_array($host, ['localhost', '127.0.0.1', '::1']);

        if (!APP_DEBUG && !$isLocalhost && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off")) {
            header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit;
        }

        $path = trim($_SERVER["REQUEST_URI"], '/');
        $path = parse_url($path, PHP_URL_PATH) ?: '';

        // Protected routes check
        $protectedRoutes = ['map', 'bookings', 'changePassword'];

        if (in_array($path, $protectedRoutes) && !isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        // Admin routes check
        $adminRoutes = ['users', 'dashboard', 'settings', 'editor'];
        $adminApiRoutes = ['updateUser', 'deleteUser', 'resetPassword', 'setMaintenance', 'addFloor', 'updateFloor', 'deleteFloor', 'addFeature', 'deleteFeature', 'saveDesk', 'deactivateDesk', 'reactivateDesk', 'hardDeleteDesk'];
        
        if (in_array($path, $adminRoutes) && (!isset($_SESSION['user']) || $_SESSION['user']->getRole() !== 'ADMIN')) {
            http_response_code(403);
            include 'public/views/403.html';
            exit();
        }

        if (in_array($path, $adminApiRoutes) && (!isset($_SESSION['user']) || $_SESSION['user']->getRole() !== 'ADMIN')) {
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

    /**
     * Checks if the current HTTP request method is GET.
     *
     * @return bool True if the request method is GET, false otherwise.
     */
    protected function isGet(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    /**
     * Checks if the current HTTP request method is POST.
     *
     * @return bool True if the request method is POST, false otherwise.
     */
    protected function isPost(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'POST';
    }
 
    /**
     * Renders a specific view template with provided variables.
     *
     * @param string|null $template The name of the template file (without .html extension).
     * @param array $variables An associative array of variables to extract into the view.
     * @return void
     */
    protected function render(string $template = null, array $variables = [])
    {
        $templatePath = 'public/views/'. $template.'.html';
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
            http_response_code(404);
            ob_start();
            include 'public/views/404.html';
            $output = ob_get_clean();
        }
        echo $output;
    }

}