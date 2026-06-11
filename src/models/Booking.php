<?php

namespace Models;

/**
 * Booking Domain Model.
 * 
 * Represents a desk reservation entity within the system, encapsulating
 * its core properties and state.
 * 
 * @package Models
 */
class Booking {
    private int $id;
    private int $userId;
    private int $deskId;
    private string $bookingDate;
    private string $status;
    private string $createdAt;

    /**
     * Constructs a new Booking instance.
     *
     * @param int $id The booking ID.
     * @param int $userId The ID of the user who made the booking.
     * @param int $deskId The ID of the booked desk.
     * @param string $bookingDate The date of the booking.
     * @param string $status The status of the booking.
     * @param string $createdAt The timestamp when the booking was created.
     */
    public function __construct(int $id, int $userId, int $deskId, string $bookingDate, string $status, string $createdAt) {
        $this->id = $id;
        $this->userId = $userId;
        $this->deskId = $deskId;
        $this->bookingDate = $bookingDate;
        $this->status = $status;
        $this->createdAt = $createdAt;
    }

    /**
     * Gets the booking ID.
     *
     * @return int The booking ID.
     */
    public function getId(): int { return $this->id; }

    /**
     * Gets the user ID.
     *
     * @return int The user ID.
     */
    public function getUserId(): int { return $this->userId; }

    /**
     * Gets the desk ID.
     *
     * @return int The desk ID.
     */
    public function getDeskId(): int { return $this->deskId; }

    /**
     * Gets the booking date.
     *
     * @return string The booking date.
     */
    public function getBookingDate(): string { return $this->bookingDate; }

    /**
     * Gets the booking status.
     *
     * @return string The booking status.
     */
    public function getStatus(): string { return $this->status; }

    /**
     * Gets the creation timestamp.
     *
     * @return string The creation timestamp.
     */
    public function getCreatedAt(): string { return $this->createdAt; }
}