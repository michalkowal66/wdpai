<?php

namespace DTO;

class DeskPopularityDTO {
    private string $identifier;
    private string $floorName;
    private int $successfulBookings;

    public function __construct(string $identifier, string $floorName, int $successfulBookings) {
        $this->identifier = $identifier;
        $this->floorName = $floorName;
        $this->successfulBookings = $successfulBookings;
    }

    public function getIdentifier(): string { return $this->identifier; }
    public function getFloorName(): string { return $this->floorName; }
    public function getSuccessfulBookings(): int { return $this->successfulBookings; }
}