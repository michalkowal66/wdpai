<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';

use Models\User;

class UsersRepository extends Repository {
    private static ?UsersRepository $instance = null;

    /**
     * Private constructor to enforce Singleton pattern.
     */
    private function __construct() {
        parent::__construct();
    }

    /**
     * Gets the singleton instance of the UsersRepository.
     *
     * @return UsersRepository The singleton instance.
     */
    public static function getInstance(): UsersRepository {
        if (self::$instance === null) {
            self::$instance = new UsersRepository();
        }
        return self::$instance;
    }

    /**
     * Retrieves a paginated list of users.
     *
     * @param int $limit The maximum number of users to return.
     * @param int $offset The number of users to skip.
     * @return array|null An array of User objects, or null if empty.
     */
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

    /**
     * Gets the total count of users.
     *
     * @return int The total number of users.
     */
    public function getUsersCount(): int 
    {
        $query = $this->database->connect()->prepare(
            "SELECT COUNT(*) FROM users;"
        );
        $query->execute();
        return (int)$query->fetchColumn();
    }

    /**
     * Retrieves a user by their email address.
     *
     * @param string $email The email address to search for.
     * @return User|null A User object if found, null otherwise.
     */
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

    /**
     * Retrieves a user by their ID.
     *
     * @param int $id The user's ID.
     * @return User|null A User object if found, null otherwise.
     */
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

    /**
     * Gets the total count of active administrators.
     *
     * @return int The total number of active admins.
     */
    public function getAdminCount(): int {
        $query = $this->database->connect()->prepare(
            "
            SELECT count(*) FROM users WHERE role = 'ADMIN' AND is_active = true
            "
        );
        $query->execute();
        return (int)$query->fetchColumn();
    }

    /**
     * Creates a new user in the database.
     *
     * @param string $email The user's email address.
     * @param string $hashedPassword The hashed password for the user.
     * @param string $fullName The user's full name.
     * @return void
     */
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

    /**
     * Updates an existing user's details.
     *
     * @param int $id The ID of the user to update.
     * @param string $fullName The user's new full name.
     * @param string $jobTitle The user's new job title.
     * @param string $role The user's new role (e.g., 'ADMIN', 'EMPLOYEE').
     * @param bool $isActive Whether the user is active.
     * @return void
     */
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

    /**
     * Updates a user's password.
     *
     * @param int $id The ID of the user.
     * @param string $hashedPassword The new hashed password.
     * @return void
     */
    public function updateUserPassword(int $id, string $hashedPassword)
    {
        $query = $this->database->connect()->prepare(
            "UPDATE users SET password = :password WHERE id = :id"
        );
        $query->bindParam(':password', $hashedPassword);
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();
    }

    /**
     * Deletes a user from the database.
     *
     * @param int $id The ID of the user to delete.
     * @return void
     */
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

    /**
     * Logs a failed login attempt for an IP address and email.
     *
     * @param string $ipAddress The IP address making the attempt.
     * @param string $email The email address being attempted.
     * @return void
     */
    public function logFailedLogin(string $ipAddress, string $email): void {
        $query = $this->database->connect()->prepare(
            "INSERT INTO login_attempts (ip_address, email) VALUES (:ip, :email)"
        );
        $query->bindParam(':ip', $ipAddress);
        $query->bindParam(':email', $email);
        $query->execute();
    }

    /**
     * Gets the number of failed login attempts from a specific IP within a given timeframe.
     *
     * @param string $ipAddress The IP address to check.
     * @param int $minutes The timeframe in minutes to look back (default 15).
     * @return int The number of failed attempts.
     */
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

    /**
     * Clears all failed login attempts associated with a specific IP address.
     *
     * @param string $ipAddress The IP address to clear attempts for.
     * @return void
     */
    public function clearFailedLogins(string $ipAddress): void {
        $query = $this->database->connect()->prepare(
            "DELETE FROM login_attempts WHERE ip_address = :ip"
        );
        $query->bindParam(':ip', $ipAddress);
        $query->execute();
    }
}
