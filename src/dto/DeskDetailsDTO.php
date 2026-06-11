<?php

namespace DTO;

use Models\Desk;

/**
 * Data Transfer Object for Desk Details.
 * 
 * Combines base Desk properties with its associated Features and 
 * booking history status for detailed UI rendering in panels.
 * 
 * @package DTO
 */
class DeskDetailsDTO implements \JsonSerializable {
    private Desk $desk;
    private ?string $currentStatus;
    private array $features;
    private bool $hasBookings;
    private ?string $floorName;

    /**
     * Constructs a new DeskDetailsDTO instance.
     *
     * @param Desk $desk The base desk entity.
     * @param string|null $currentStatus The current status of the desk.
     * @param array $features An array of features associated with the desk.
     * @param bool $hasBookings Indicates if the desk has active bookings.
     * @param string|null $floorName The name of the floor where the desk is located.
     */
    public function __construct(Desk $desk, ?string $currentStatus = null, array $features = [], bool $hasBookings = false, ?string $floorName = null) {
        $this->desk = $desk;
        $this->currentStatus = $currentStatus;
        $this->features = $features;
        $this->hasBookings = $hasBookings;
        $this->floorName = $floorName;
    }

    /**
     * Gets the base desk entity.
     *
     * @return Desk The base desk entity.
     */
    public function getDesk(): Desk { return $this->desk; }

    /**
     * Gets the current status of the desk.
     *
     * @return string|null The current status, or null if not set.
     */
    public function getCurrentStatus(): ?string { return $this->currentStatus; }

    /**
     * Gets the features associated with the desk.
     *
     * @return array An array of features.
     */
    public function getFeatures(): array { return $this->features; }

    /**
     * Checks if the desk has active bookings.
     *
     * @return bool True if the desk has bookings, false otherwise.
     */
    public function hasBookings(): bool { return $this->hasBookings; }

    /**
     * Gets the floor name where the desk is located.
     *
     * @return string|null The floor name, or null if not set.
     */
    public function getFloorName(): ?string { return $this->floorName; }
    
    // Helper accessors for views

    /**
     * Gets the desk ID.
     *
     * @return int The desk ID.
     */
    public function getId(): int { return $this->desk->getId(); }

    /**
     * Gets the desk identifier.
     *
     * @return string The desk identifier.
     */
    public function getIdentifier(): string { return $this->desk->getIdentifier(); }

    /**
     * Gets the desk description.
     *
     * @return string|null The desk description, or null if not set.
     */
    public function getDescription(): ?string { return $this->desk->getDescription(); }

    /**
     * Gets the desk X-coordinate on the map.
     *
     * @return float The X-coordinate.
     */
    public function getPosX(): float { return $this->desk->getPosX(); }

    /**
     * Gets the desk Y-coordinate on the map.
     *
     * @return float The Y-coordinate.
     */
    public function getPosY(): float { return $this->desk->getPosY(); }

    /**
     * Checks if the desk is active.
     *
     * @return bool True if active, false otherwise.
     */
    public function isActive(): bool { return $this->desk->isActive(); }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return mixed Returns data which can be serialized by json_encode.
     */
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