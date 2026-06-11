<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Feature.php';

use Models\Feature;

class FeatureRepository extends Repository {

    /**
     * Retrieves all features.
     *
     * @return array An array of Feature objects.
     */
    public function getAllFeatures(): array {
        $stmt = $this->database->connect()->prepare('SELECT * FROM features ORDER BY name ASC');
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $features = [];
        foreach ($results as $row) {
            $features[] = new Feature($row['id'], $row['name'], $row['icon_name']);
        }
        return $features;
    }

    /**
     * Adds a new feature to the database.
     *
     * @param string $name The name of the feature.
     * @param string $iconName The name of the icon associated with the feature.
     * @return bool True if the feature was added successfully, false otherwise.
     */
    public function addFeature(string $name, string $iconName): bool {
        try {
            $stmt = $this->database->connect()->prepare('
                INSERT INTO features (name, icon_name)
                VALUES (:name, :icon_name)
            ');
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':icon_name', $iconName);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Checks if a feature is currently assigned to any desk.
     *
     * @param int $id The ID of the feature.
     * @return bool True if the feature is in use, false otherwise.
     */
    public function isFeatureInUse(int $id): bool {
        $stmt = $this->database->connect()->prepare('SELECT COUNT(*) FROM desk_features WHERE feature_id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Deletes a feature from the database.
     *
     * @param int $id The ID of the feature to delete.
     * @return bool True if the feature was deleted successfully, false otherwise.
     */
    public function deleteFeature(int $id): bool {
        $stmt = $this->database->connect()->prepare('DELETE FROM features WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
}
