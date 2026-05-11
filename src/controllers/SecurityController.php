<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';

class SecurityController extends AppController {

    public function login()
    {
        if (!$this->isPost()) {
            return $this->render('login');
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        if (empty($email) || empty($password)) {
            return $this->render('login', ['messages' => 'Invalid email or password']);
        }

        $usersRepository = new UsersRepository();
        $user = $usersRepository->getUserByEmail($email);
      
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->render('login', ['messages' => 'Invalid email or password']);
        }

        if (!$user['is_active']) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/inactive");
            exit();
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'job_title' => $user['job_title'],
            'role' => $user['role']
        ];

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/map");
        return;
    }

    public function logout()
    {
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
            return $this->render('register');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['confirm-password'] ?? '';
        $fullName = trim($_POST['full-name'] ?? '');

        $errors = [];

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
            return $this->render('register', ['errors' => $errors]);
        }

        $usersRepository = new UsersRepository();
        $user = $usersRepository->getUserByEmail($email);
        if ($user) {
            return $this->render('register', ['errors' => ['email' => 'Email already in use']]);
        }
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $usersRepository->createUser($email, $hashedPassword, $fullName);

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        return;
    }

}