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

    public function createFloor(string $name, int $level, string $mapImageUrl): bool {
        try {
            $stmt = $this->database->connect()->prepare('
                INSERT INTO floors (name, level, map_image_url) 
                VALUES (:name, :level, :map_image_url)
            ');
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':level', $level, PDO::PARAM_INT);
            $stmt->bindParam(':map_image_url', $mapImageUrl);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateFloor(int $id, string $name, int $level, ?string $mapImageUrl = null): bool {
        try {
            if ($mapImageUrl) {
                $stmt = $this->database->connect()->prepare('
                    UPDATE floors 
                    SET name = :name, level = :level, map_image_url = :map_image_url 
                    WHERE id = :id
                ');
                $stmt->bindParam(':map_image_url', $mapImageUrl);
            } else {
                $stmt = $this->database->connect()->prepare('
                    UPDATE floors 
                    SET name = :name, level = :level 
                    WHERE id = :id
                ');
            }
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':level', $level, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getDeskCountOnFloor(int $floorId): int {
        $stmt = $this->database->connect()->prepare('SELECT COUNT(*) FROM desks WHERE floor_id = :floorId');
        $stmt->bindParam(':floorId', $floorId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function deleteFloor(int $id): bool {
        if ($this->getDeskCountOnFloor($id) > 0) {
            return false;
        }

        try {
            $stmt = $this->database->connect()->prepare('DELETE FROM floors WHERE id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
    }
