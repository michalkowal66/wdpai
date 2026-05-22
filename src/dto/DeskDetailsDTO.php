<?php

namespace DTO;

use Models\Desk;

class DeskDetailsDTO implements \JsonSerializable {
    private Desk $desk;
    private ?string $currentStatus;
    private array $features;
    private bool $hasBookings;
    private ?string $floorName;

    public function __construct(Desk $desk, ?string $currentStatus = null, array $features = [], bool $hasBookings = false, ?string $floorName = null) {
        $this->desk = $desk;
        $this->currentStatus = $currentStatus;
        $this->features = $features;
        $this->hasBookings = $hasBookings;
        $this->floorName = $floorName;
    }

    public function getDesk(): Desk { return $this->desk; }
    public function getCurrentStatus(): ?string { return $this->currentStatus; }
    public function getFeatures(): array { return $this->features; }
    public function hasBookings(): bool { return $this->hasBookings; }
    public function getFloorName(): ?string { return $this->floorName; }
    
    // Helper accessors for views
    public function getId(): int { return $this->desk->getId(); }
    public function getIdentifier(): string { return $this->desk->getIdentifier(); }
    public function getDescription(): ?string { return $this->desk->getDescription(); }
    public function getPosX(): float { return $this->desk->getPosX(); }
    public function getPosY(): float { return $this->desk->getPosY(); }
    public function isActive(): bool { return $this->desk->isActive(); }

    public function jsonSerialize(): mixed {
        return [
            'id' => $this->getId(),
            'identifier' => $this->getIdentifier(),
            'description' => $this->getDescription(),
            'pos_x' => $this->getPosX(),
            'pos_y' => $this->getPosY(),
            'is_active' => $this->isActive(),
            'current_status' => $this->currentStatus,
            'has_bookings' => $this->hasBookings,
            'floor_name' => $this->floorName,
            'features' => $this->features
        ];
    }
}