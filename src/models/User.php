<?php

namespace Models;

/**
 * User Domain Model.
 * 
 * Represents an authenticated user in the system, managing credentials, 
 * roles (ADMIN, EMPLOYEE), and account status.
 * 
 * @package Models
 */
class User implements \JsonSerializable {
    private int $id;
    private string $email;
    private string $password;
    private string $fullName;
    private ?string $jobTitle;
    private string $role;
    private bool $isActive;

    /**
     * Constructs a new User instance.
     *
     * @param int $id The user ID.
     * @param string $email The user's email address.
     * @param string $password The user's hashed password.
     * @param string $fullName The user's full name.
     * @param string|null $jobTitle The user's job title.
     * @param string $role The user's role (e.g., ADMIN, EMPLOYEE).
     * @param bool $isActive Indicates if the user account is active.
     */
    public function __construct(int $id, string $email, string $password, string $fullName, ?string $jobTitle, string $role, bool $isActive) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->fullName = $fullName;
        $this->jobTitle = $jobTitle;
        $this->role = $role;
        $this->isActive = $isActive;
    }

    /**
     * Gets the user ID.
     *
     * @return int The user ID.
     */
    public function getId(): int { return $this->id; }

    /**
     * Gets the user's email address.
     *
     * @return string The email address.
     */
    public function getEmail(): string { return $this->email; }

    /**
     * Gets the user's hashed password.
     *
     * @return string The hashed password.
     */
    public function getPassword(): string { return $this->password; }

    /**
     * Gets the user's full name.
     *
     * @return string The full name.
     */
    public function getFullName(): string { return $this->fullName; }

    /**
     * Gets the user's job title.
     *
     * @return string|null The job title, or null if not set.
     */
    public function getJobTitle(): ?string { return $this->jobTitle; }

    /**
     * Gets the user's role.
     *
     * @return string The user role.
     */
    public function getRole(): string { return $this->role; }

    /**
     * Checks if the user account is active.
     *
     * @return bool True if the account is active, false otherwise.
     */
    public function isActive(): bool { return $this->isActive; }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return mixed Returns data which can be serialized by json_encode.
     */
    public function jsonSerialize(): mixed {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'full_name' => $this->fullName,
            'job_title' => $this->jobTitle,
            'role' => $this->role,
            'is_active' => $this->isActive
            // Password intentionally omitted from serialization for security
        ];
    }
}