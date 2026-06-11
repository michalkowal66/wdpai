<?php

namespace Models;

/**
 * Floor Domain Model.
 * 
 * Represents a physical building floor and contains the URL to the 
 * background map image used in the UI for desk placement.
 * 
 * @package Models
 */
class Floor implements \JsonSerializable {
    private int $id;
    private string $name;
    private int $level;
    private ?string $mapImageUrl;

    /**
     * Constructs a new Floor instance.
     *
     * @param int $id The floor ID.
     * @param string $name The name of the floor.
     * @param int $level The level number of the floor.
     * @param string|null $mapImageUrl The URL to the background map image.
     */
    public function __construct(int $id, string $name, int $level, ?string $mapImageUrl) {
        $this->id = $id;
        $this->name = $name;
        $this->level = $level;
        $this->mapImageUrl = $mapImageUrl;
    }

    /**
     * Gets the floor ID.
     *
     * @return int The floor ID.
     */
    public function getId(): int { return $this->id; }

    /**
     * Gets the floor name.
     *
     * @return string The floor name.
     */
    public function getName(): string { return $this->name; }

    /**
     * Gets the floor level.
     *
     * @return int The floor level.
     */
    public function getLevel(): int { return $this->level; }

    /**
     * Gets the floor map image URL.
     *
     * @return string|null The floor map image URL, or null if not set.
     */
    public function getMapImageUrl(): ?string { return $this->mapImageUrl; }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return mixed Returns data which can be serialized by json_encode.
     */
    public function jsonSerialize(): mixed {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'map_image_url' => $this->mapImageUrl
        ];
    }
}