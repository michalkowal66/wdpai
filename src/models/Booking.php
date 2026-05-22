<?php

namespace Models;

class Booking {
    private int $id;
    private int $userId;
    private int $deskId;
    private string $bookingDate;
    private string $status;
    private string $createdAt;

    public function __construct(int $id, int $userId, int $deskId, string $bookingDate, string $status, string $createdAt) {
        $this->id = $id;
        $this->userId = $userId;
        $this->deskId = $deskId;
        $this->bookingDate = $bookingDate;
        $this->status = $status;
        $this->createdAt = $createdAt;
    }

    public function getId(): int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getDeskId(): int { return $this->deskId; }
    public function getBookingDate(): string { return $this->bookingDate; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }
}