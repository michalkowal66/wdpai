<?php

namespace Models;

/**
 * Feature Domain Model.
 * 
 * Represents an amenity or attribute (e.g., dual monitors, standing desk) 
 * that can be assigned to a specific desk.
 * 
 * @package Models
 */
class Feature implements \JsonSerializable {
    private int $id;
    private string $name;
    private string $iconName;

    /**
     * Constructs a new Feature instance.
     *
     * @param int $id The feature ID.
     * @param string $name The name of the feature.
     * @param string $iconName The name of the icon representing the feature.
     */
    public function __construct(int $id, string $name, string $iconName) {
        $this->id = $id;
        $this->name = $name;
        $this->iconName = $iconName;
    }

    /**
     * Gets the feature ID.
     *
     * @return int The feature ID.
     */
    public function getId(): int { return $this->id; }

    /**
     * Gets the feature name.
     *
     * @return string The feature name.
     */
    public function getName(): string { return $this->name; }

    /**
     * Gets the icon name.
     *
     * @return string The icon name.
     */
    public function getIconName(): string { return $this->iconName; }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return mixed Returns data which can be serialized by json_encode.
     */
    public function jsonSerialize(): mixed {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon_name' => $this->iconName
        ];
    }
}