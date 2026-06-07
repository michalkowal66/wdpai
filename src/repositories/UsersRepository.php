<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';

use Models\User;

class UsersRepository extends Repository {
    private static ?UsersRepository $instance = null;

    private function __construct() {
        parent::__construct();
    }

    public static function getInstance(): UsersRepository {
        if (self::$instance === null) {
            self::$instance = new UsersRepository();
        }
        return self::$instance;
    }

    public function getUsers(int $limit = 10, int $offset = 0): ?array 
    {
        $query = $this->database->connect()->prepare(
            "
            SELECT id, email, password, full_name, job_title, role, is_active FROM users 
            ORDER BY id ASC
            LIMIT :limit OFFSET :offset;
            "
        );
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->bindParam(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($results as $row) {
            $users[] = new User($row['id'], $row['email'], $row['password'], $row['full_name'], $row['job_title'], $row['role'], $row['is_active']);
        }
        return $users;
    }

    public function getUsersCount(): int 
    {
        $query = $this->database->connect()->prepare(
            "SELECT COUNT(*) FROM users;"
        );
        $query->execute();
        return (int)$query->fetchColumn();
    }

    public function getUserByEmail(string $email): ?User {
        $query = $this->database->connect()->prepare(
            "
            SELECT id, email, password, full_name, job_title, role, is_active FROM users WHERE email = :email
            "
        );
        $query->bindParam(':email', $email);
        $query->execute();

        $row = $query->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return new User($row['id'], $row['email'], $row['password'], $row['full_name'], $row['job_title'], $row['role'], $row['is_active']);
    }

    public function getUserById(int $id): ?User {
        $query = $this->database->connect()->prepare(
            "
            SELECT id, email, password, full_name, job_title, role, is_active FROM users WHERE id = :id
            "
        );
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();

        $row = $query->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return new User($row['id'], $row['email'], $row['password'], $row['full_name'], $row['job_title'], $row['role'], $row['is_active']);
    }

    public function getAdminCount(): int {
        $query = $this->database->connect()->prepare(
            "
            SELECT count(*) FROM users WHERE role = 'ADMIN' AND is_active = true
            "
        );
        $query->execute();
        return (int)$query->fetchColumn();
    }

    public function createUser(
        string $email,
        string $hashedPassword,
        string $fullName
    ) {
        $query = $this->database->connect()->prepare(
            "
            INSERT INTO users (full_name, email, password)
            VALUES (?, ?, ?);
            "
        );
        $query->execute([
            $fullName,
            $email, 
            $hashedPassword
        ]);
    }

    public function updateUser(int $id, string $fullName, string $jobTitle, string $role, bool $isActive)
    {
        $query = $this->database->connect()->prepare(
            "
            UPDATE users 
            SET full_name = :full_name, job_title = :job_title, role = :role::user_role, is_active = :is_active
            WHERE id = :id
            "
        );
        $query->bindParam(':full_name', $fullName);
        $query->bindParam(':job_title', $jobTitle);
        $query->bindParam(':role', $role);
        $query->bindParam(':is_active', $isActive, PDO::PARAM_BOOL);
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
    }

    public function updateUserPassword(int $id, string $hashedPassword)
    {
        $query = $this->database->connect()->prepare(
            "UPDATE users SET password = :password WHERE id = :id"
        );
        $query->bindParam(':password', $hashedPassword);
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
    }

    public function deleteUser(int $id)
    {
        $query = $this->database->connect()->prepare(
            "
            DELETE FROM users WHERE id = :id
            "
        );
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
    }

    public function logFailedLogin(string $ipAddress, string $email): void {
        $query = $this->database->connect()->prepare(
            "INSERT INTO login_attempts (ip_address, email) VALUES (:ip, :email)"
        );
        $query->bindParam(':ip', $ipAddress);
        $query->bindParam(':email', $email);
        $query->execute();
    }

    public function getFailedLoginCount(string $ipAddress, int $minutes = 15): int {
        $query = $this->database->connect()->prepare(
            "SELECT COUNT(*) FROM login_attempts 
             WHERE ip_address = :ip 
             AND attempted_at >= NOW() - INTERVAL '$minutes minutes'"
        );
        $query->bindParam(':ip', $ipAddress);
        $query->execute();
        return (int)$query->fetchColumn();
    }

    public function clearFailedLogins(string $ipAddress): void {
        $query = $this->database->connect()->prepare(
            "DELETE FROM login_attempts WHERE ip_address = :ip"
        );
        $query->bindParam(':ip', $ipAddress);
        $query->execute();
    }
}
