<?php

namespace Models;

/**
 * Desk Domain Model.
 * 
 * Represents a physical desk available for booking on a specific floor map.
 * Implements JsonSerializable to easily pass map coordinates to the frontend.
 * 
 * @package Models
 */
class Desk implements \JsonSerializable {
    private int $id;
    private string $identifier;
    private ?string $description;
    private int $floorId;
    private float $posX;
    private float $posY;
    private bool $isActive;

    /**
     * Constructs a new Desk instance.
     *
     * @param int $id The desk ID.
     * @param string $identifier The unique desk identifier.
     * @param string|null $description The description of the desk.
     * @param int $floorId The ID of the floor where the desk is located.
     * @param float $posX The X-coordinate of the desk on the map.
     * @param float $posY The Y-coordinate of the desk on the map.
     * @param bool $isActive Indicates if the desk is currently active and bookable.
     */
    public function __construct(int $id, string $identifier, ?string $description, int $floorId, float $posX, float $posY, bool $isActive) {
        $this->id = $id;
        $this->identifier = $identifier;
        $this->description = $description;
        $this->floorId = $floorId;
        $this->posX = $posX;
        $this->posY = $posY;
        $this->isActive = $isActive;
    }

    /**
     * Gets the desk ID.
     *
     * @return int The desk ID.
     */
    public function getId(): int { return $this->id; }

    /**
     * Gets the desk identifier.
     *
     * @return string The desk identifier.
     */
    public function getIdentifier(): string { return $this->identifier; }

    /**
     * Gets the desk description.
     *
     * @return string|null The desk description, or null if not set.
     */
    public function getDescription(): ?string { return $this->description; }

    /**
     * Gets the floor ID.
     *
     * @return int The floor ID.
     */
    public function getFloorId(): int { return $this->floorId; }

    /**
     * Gets the X-coordinate.
     *
     * @return float The X-coordinate.
     */
    public function getPosX(): float { return $this->posX; }

    /**
     * Gets the Y-coordinate.
     *
     * @return float The Y-coordinate.
     */
    public function getPosY(): float { return $this->posY; }

    /**
     * Checks if the desk is active.
     *
     * @return bool True if active, false otherwise.
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
            'identifier' => $this->identifier,
            'description' => $this->description,
            'floor_id' => $this->floorId,
            'pos_x' => $this->posX,
            'pos_y' => $this->posY,
            'is_active' => $this->isActive
        ];
    }
}