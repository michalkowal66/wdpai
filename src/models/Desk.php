<?php

namespace Models;

class Desk implements \JsonSerializable {
    private int $id;
    private string $identifier;
    private ?string $description;
    private int $floorId;
    private float $posX;
    private float $posY;
    private bool $isActive;

    public function __construct(int $id, string $identifier, ?string $description, int $floorId, float $posX, float $posY, bool $isActive) {
        $this->id = $id;
        $this->identifier = $identifier;
        $this->description = $description;
        $this->floorId = $floorId;
        $this->posX = $posX;
        $this->posY = $posY;
        $this->isActive = $isActive;
    }

    public function getId(): int { return $this->id; }
    public function getIdentifier(): string { return $this->identifier; }
    public function getDescription(): ?string { return $this->description; }
    public function getFloorId(): int { return $this->floorId; }
    public function getPosX(): float { return $this->posX; }
    public function getPosY(): float { return $this->posY; }
    public function isActive(): bool { return $this->isActive; }

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