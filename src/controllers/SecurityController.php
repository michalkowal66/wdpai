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
            return $this->render('login', ['messages' => 'Fill all fields']);
        }

        $usersRepository = new UsersRepository();
        $user = $usersRepository->getUserByEmail($email);
      
        if (!$user) {
            return $this->render('login', ['messages' => 'User not found']);
        }

        if (!password_verify($password, $user['password'])) {
            return $this->render('login', ['messages' => 'Wrong password']);
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
        header("Location: {$url}/dashboard");
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
        $fullName = $_POST['full-name'] ?? '';

        if (empty($email) || empty($password) || empty($password2) || empty($fullName)) {
            return $this->render('register', ['messages' => 'Fill all fields']);
        }

        if ($password !== $password2) {
            return $this->render('register', ['messages' => 'Passwords don\'t match']);
        }

        $usersRepository = new UsersRepository();
        $user = $usersRepository->getUserByEmail($email);
        if ($user) {
            return $this->render('register', ['messages' => 'Email already in use.']);
        }
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $usersRepository->createUser($email, $hashedPassword, $fullName);

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        return;
    }

}