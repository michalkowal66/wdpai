<?php

namespace Models;

class User implements \JsonSerializable {
    private int $id;
    private string $email;
    private string $password;
    private string $fullName;
    private ?string $jobTitle;
    private string $role;
    private bool $isActive;

    public function __construct(int $id, string $email, string $password, string $fullName, ?string $jobTitle, string $role, bool $isActive) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->fullName = $fullName;
        $this->jobTitle = $jobTitle;
        $this->role = $role;
        $this->isActive = $isActive;
    }

    public function getId(): int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getFullName(): string { return $this->fullName; }
    public function getJobTitle(): ?string { return $this->jobTitle; }
    public function getRole(): string { return $this->role; }
    public function isActive(): bool { return $this->isActive; }

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