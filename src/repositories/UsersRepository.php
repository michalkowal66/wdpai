<?php

require_once 'Repository.php';

class UsersRepository extends Repository {
    public function getUsers(int $limit = 10, int $offset = 0): ?array 
    {
        $query = $this->database->connect()->prepare(
            "
            SELECT * FROM users 
            ORDER BY id ASC
            LIMIT :limit OFFSET :offset;
            "
        );
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->bindParam(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUsersCount(): int 
    {
        $query = $this->database->connect()->prepare(
            "SELECT COUNT(*) FROM users;"
        );
        $query->execute();
        return (int)$query->fetchColumn();
    }

    public function getUserByEmail(string $email) {
        $query = $this->database->connect()->prepare(
            "
            SELECT * FROM users WHERE email = :email
            "
        );
        $query->bindParam(':email', $email);
        $query->execute();

        $user = $query->fetch(PDO::FETCH_ASSOC);
        return $user;
    }

    public function getUserById(int $id) {
        $query = $this->database->connect()->prepare(
            "
            SELECT * FROM users WHERE id = :id
            "
        );
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();

        $user = $query->fetch(PDO::FETCH_ASSOC);
        return $user;
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
}
