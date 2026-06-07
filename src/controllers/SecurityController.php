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

        // Remember Me functionality
        if (isset($_POST['remember-me'])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                session_id(), 
                time() + 86400 * 30, // 30 days
                $params['path'], 
                $params['domain'], 
                $params['secure'], 
                $params['httponly']
            );
        }

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

    public function changePassword()
    {
        if (!$this->isPost()) {
            http_response_code(405);
            return;
        }

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
            return;
        }

        if (strlen($newPassword) < 8 || strlen($newPassword) > 255) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'New password must be between 8 and 255 characters.']);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
            return;
        }

        $usersRepository = UsersRepository::getInstance();
        $user = $usersRepository->getUserById($_SESSION['user']->getId());

        if (!$user || !password_verify($currentPassword, $user->getPassword())) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
            return;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $usersRepository->updateUserPassword($user->getId(), $hashedPassword);
        
        // Update user object in session
        $_SESSION['user'] = $usersRepository->getUserById($user->getId());

        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
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