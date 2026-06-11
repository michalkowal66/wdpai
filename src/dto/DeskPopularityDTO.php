<?php

namespace DTO;

class DeskPopularityDTO {
    private string $identifier;
    private string $floorName;
    private int $successfulBookings;

    /**
     * Constructs a new DeskPopularityDTO instance.
     *
     * @param string $identifier The desk identifier.
     * @param string $floorName The floor name where the desk is located.
     * @param int $successfulBookings The number of successful bookings for the desk.
     */
    public function __construct(string $identifier, string $floorName, int $successfulBookings) {
        $this->identifier = $identifier;
        $this->floorName = $floorName;
        $this->successfulBookings = $successfulBookings;
    }

    /**
     * Gets the desk identifier.
     *
     * @return string The desk identifier.
     */
    public function getIdentifier(): string { return $this->identifier; }

    /**
     * Gets the floor name.
     *
     * @return string The floor name.
     */
    public function getFloorName(): string { return $this->floorName; }

    /**
     * Gets the number of successful bookings.
     *
     * @return int The number of successful bookings.
     */
    public function getSuccessfulBookings(): int { return $this->successfulBookings; }
}