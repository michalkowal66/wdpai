<?php

namespace DTO;

use Models\Booking;

class BookingDetailsDTO {
    private Booking $booking;
    private string $deskIdentifier;
    private string $floorName;
    private ?string $floorMapUrl;
    private int $floorLevel;

    public function __construct(Booking $booking, string $deskIdentifier, string $floorName, ?string $floorMapUrl, int $floorLevel) {
        $this->booking = $booking;
        $this->deskIdentifier = $deskIdentifier;
        $this->floorName = $floorName;
        $this->floorMapUrl = $floorMapUrl;
        $this->floorLevel = $floorLevel;
    }

    public function getBooking(): Booking { return $this->booking; }
    public function getDeskIdentifier(): string { return $this->deskIdentifier; }
    public function getFloorName(): string { return $this->floorName; }
    public function getFloorMapUrl(): ?string { return $this->floorMapUrl; }
    public function getFloorLevel(): int { return $this->floorLevel; }
    
    // Helper accessors for views
    public function getId(): int { return $this->booking->getId(); }
    public function getBookingDate(): string { return $this->booking->getBookingDate(); }
    public function getStatus(): string { return $this->booking->getStatus(); }
}