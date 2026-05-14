<?php

require_once 'Repository.php';

class FeatureRepository extends Repository {
    public function getAllFeatures(): array {
        $stmt = $this->database->connect()->prepare('SELECT * FROM features ORDER BY name ASC');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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

    public function isFeatureInUse(int $id): bool {
        $stmt = $this->database->connect()->prepare('SELECT COUNT(*) FROM desk_features WHERE feature_id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    public function deleteFeature(int $id): bool {
        $stmt = $this->database->connect()->prepare('DELETE FROM features WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
}
