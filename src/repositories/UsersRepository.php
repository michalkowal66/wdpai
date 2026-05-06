<?php

require_once 'Repository.php';

class UsersRepository extends Repository {
    public function getUsers(): ?array 
    {
        $query = $this->database->connect()->prepare(
            "
            SELECT * FROM users;
            "
        );
        $query->execute();

        $users = $query->fetchAll(PDO::FETCH_ASSOC);
        return $users;
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
