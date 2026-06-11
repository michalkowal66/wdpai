<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Floor.php';

use Models\Floor;

class FloorRepository extends Repository {

    /**
     * Retrieves all floors sorted by their level.
     *
     * @return array An array of Floor objects.
     */
    public function getFloors(): array {
        $stmt = $this->database->connect()->prepare('SELECT * FROM floors ORDER BY level ASC');
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $floors = [];
        foreach ($results as $row) {
            $floors[] = new Floor($row['id'], $row['name'], $row['level'], $row['map_image_url']);
        }
        return $floors;
    }

    /**
     * Retrieves a specific floor by its level.
     *
     * @param int $level The level of the floor.
     * @return Floor|null A Floor object if found, null otherwise.
     */
    public function getFloorByLevel(int $level): ?Floor {
        $stmt = $this->database->connect()->prepare('SELECT * FROM floors WHERE level = :level');
        $stmt->bindParam(':level', $level, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return new Floor($row['id'], $row['name'], $row['level'], $row['map_image_url']);
    }

    /**
     * Retrieves a specific floor by its ID.
     *
     * @param int $id The ID of the floor.
     * @return Floor|null A Floor object if found, null otherwise.
     */
    public function getFloorById(int $id): ?Floor {
        $stmt = $this->database->connect()->prepare('SELECT * FROM floors WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return new Floor($row['id'], $row['name'], $row['level'], $row['map_image_url']);
    }

    /**
     * Creates a new floor.
     *
     * @param string $name The name of the floor.
     * @param int $level The level of the floor.
     * @param string $mapImageUrl The URL of the floor's map image.
     * @return bool True if the floor was created successfully, false otherwise.
     */
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

    /**
     * Updates an existing floor.
     *
     * @param int $id The ID of the floor to update.
     * @param string $name The new name of the floor.
     * @param int $level The new level of the floor.
     * @param string|null $mapImageUrl The new URL of the floor's map image, or null to keep the existing one.
     * @return bool True if the floor was updated successfully, false otherwise.
     */
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

    /**
     * Gets the number of desks located on a specific floor.
     *
     * @param int $floorId The ID of the floor.
     * @return int The total count of desks on the floor.
     */
    public function getDeskCountOnFloor(int $floorId): int {
        $stmt = $this->database->connect()->prepare('SELECT COUNT(*) FROM desks WHERE floor_id = :floorId');
        $stmt->bindParam(':floorId', $floorId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Deletes a floor from the database.
     * Will fail if there are any desks assigned to this floor.
     *
     * @param int $id The ID of the floor to delete.
     * @return bool True if the floor was successfully deleted, false otherwise.
     */
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
