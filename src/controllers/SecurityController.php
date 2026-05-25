<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';

class SecurityController extends AppController {

    private function generateCsrfToken(): string {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    private function validateCsrfToken(?string $token): bool {
        return !empty($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
    }

    public function login()
    {
        if (!$this->isPost()) {
            return $this->render('login', ['csrf' => $this->generateCsrfToken()]);
        }

        $usersRepository = UsersRepository::getInstance();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Check Rate Limiting
        $failedAttempts = $usersRepository->getFailedLoginCount($ipAddress, 15);
        if ($failedAttempts >= 5) {
            http_response_code(429);
            return $this->render('login', ['messages' => 'Too many failed login attempts. Please try again in 15 minutes.', 'csrf' => $this->generateCsrfToken()]);
        }

        $csrf = $_POST["csrf"] ?? '';
        if (!$this->validateCsrfToken($csrf)) {
            die("CSRF detected");
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        if (strlen($email) > 100 || strlen($password) > 255) {
            return $this->render('login', ['messages' => 'Invalid input length.', 'csrf' => $this->generateCsrfToken()]);
        }

        if (empty($email) || empty($password)) {
            return $this->render('login', ['messages' => 'Invalid email or password', 'csrf' => $this->generateCsrfToken()]);
        }

        $user = $usersRepository->getUserByEmail($email);
      
        if (!$user || !password_verify($password, $user->getPassword())) {
            $usersRepository->logFailedLogin($ipAddress, $email);
            error_log("Failed login for email " . $email . " from IP " . $ipAddress);
            return $this->render('login', ['messages' => 'Invalid email or password.', 'csrf' => $this->generateCsrfToken()]);
        }

        if (!$user->isActive()) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/inactive");
            return;
        }

        session_regenerate_id(true); // Protect against session fixation (B3)
        $usersRepository->clearFailedLogins($ipAddress); // Reset attempts on success
        $_SESSION['user'] = $user;

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/map");
        return;
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        return;
    }

    public function inactive()
    {
        return $this->render('inactive');
    }

    public function register()
    {
        if (!$this->isPost()) {
            return $this->render('register', ['csrf' => $this->generateCsrfToken()]);
        }

        $csrf = $_POST["csrf"] ?? '';
        if (!$this->validateCsrfToken($csrf)) {
            die("CSRF detected");
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['confirm-password'] ?? '';
        $fullName = trim($_POST['full-name'] ?? '');

        $errors = [];

        if (strlen($email) > 100 || strlen($fullName) > 100 || strlen($password) > 255) {
            $errors['email'] = 'Invalid input length.';
        }

        if (empty($fullName)) {
            $errors['full-name'] = 'Full name is required';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }

        if (empty($password2)) {
            $errors['confirm-password'] = 'Please confirm your password';
        } elseif ($password !== $password2) {
            $errors['confirm-password'] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            return $this->render('register', ['errors' => $errors, 'csrf' => $this->generateCsrfToken()]);
        }

        $usersRepository = UsersRepository::getInstance();
        $user = $usersRepository->getUserByEmail($email);
        if ($user) {
            return $this->render('register', ['messages' => 'If the email is valid and not already taken, an account has been created. Please wait for admin approval.', 'csrf' => $this->generateCsrfToken()]);
        }
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);


        $usersRepository->createUser($email, $hashedPassword, $fullName);

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        return;
    }

}