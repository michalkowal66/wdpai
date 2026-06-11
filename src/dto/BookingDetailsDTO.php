<?php

namespace DTO;

use Models\Booking;

/**
 * Data Transfer Object for Booking Details.
 * 
 * Aggregates booking information with related desk and floor details
 * for display in the user's booking history and upcoming reservations.
 * 
 * @package DTO
 */
class BookingDetailsDTO {
    private Booking $booking;
    private string $deskIdentifier;
    private string $floorName;
    private ?string $floorMapUrl;
    private int $floorLevel;

    /**
     * Constructs a new BookingDetailsDTO instance.
     *
     * @param Booking $booking The booking entity.
     * @param string $deskIdentifier The identifier of the booked desk.
     * @param string $floorName The name of the floor where the desk is located.
     * @param string|null $floorMapUrl The URL to the floor map image.
     * @param int $floorLevel The level of the floor.
     */
    public function __construct(Booking $booking, string $deskIdentifier, string $floorName, ?string $floorMapUrl, int $floorLevel) {
        $this->booking = $booking;
        $this->deskIdentifier = $deskIdentifier;
        $this->floorName = $floorName;
        $this->floorMapUrl = $floorMapUrl;
        $this->floorLevel = $floorLevel;
    }

    /**
     * Gets the booking entity.
     *
     * @return Booking The booking entity.
     */
    public function getBooking(): Booking { return $this->booking; }

    /**
     * Gets the desk identifier.
     *
     * @return string The desk identifier.
     */
    public function getDeskIdentifier(): string { return $this->deskIdentifier; }

    /**
     * Gets the floor name.
     *
     * @return string The floor name.
     */
    public function getFloorName(): string { return $this->floorName; }

    /**
     * Gets the floor map URL.
     *
     * @return string|null The floor map URL, or null if not set.
     */
    public function getFloorMapUrl(): ?string { return $this->floorMapUrl; }

    /**
     * Gets the floor level.
     *
     * @return int The floor level.
     */
    public function getFloorLevel(): int { return $this->floorLevel; }
    
    // Helper accessors for views

    /**
     * Gets the booking ID.
     *
     * @return int The booking ID.
     */
    public function getId(): int { return $this->booking->getId(); }

    /**
     * Gets the booking date.
     *
     * @return string The booking date.
     */
    public function getBookingDate(): string { return $this->booking->getBookingDate(); }

    /**
     * Gets the booking status.
     *
     * @return string The booking status.
     */
    public function getStatus(): string { return $this->booking->getStatus(); }
}