<?php

require_once 'Repository.php';

class FloorRepository extends Repository {
    public function getFloors(): array {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM floors ORDER BY level ASC
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFloorByLevel(int $level): ?array {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM floors WHERE level = :level
        ');
        $stmt->bindParam(':level', $level, PDO::PARAM_INT);
        $stmt->execute();
        $floor = $stmt->fetch(PDO::FETCH_ASSOC);
        return $floor ?: null;
    }
}
