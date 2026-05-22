<?php

namespace Models;

class Feature implements \JsonSerializable {
    private int $id;
    private string $name;
    private string $iconName;

    public function __construct(int $id, string $name, string $iconName) {
        $this->id = $id;
        $this->name = $name;
        $this->iconName = $iconName;
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getIconName(): string { return $this->iconName; }

    public function jsonSerialize(): mixed {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon_name' => $this->iconName
        ];
    }
}