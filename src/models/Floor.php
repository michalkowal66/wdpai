<?php

namespace Models;

class Floor implements \JsonSerializable {
    private int $id;
    private string $name;
    private int $level;
    private ?string $mapImageUrl;

    public function __construct(int $id, string $name, int $level, ?string $mapImageUrl) {
        $this->id = $id;
        $this->name = $name;
        $this->level = $level;
        $this->mapImageUrl = $mapImageUrl;
    }

    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getLevel(): int { return $this->level; }
    public function getMapImageUrl(): ?string { return $this->mapImageUrl; }

    public function jsonSerialize(): mixed {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'map_image_url' => $this->mapImageUrl
        ];
    }
}