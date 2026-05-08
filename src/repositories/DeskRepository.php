<?php

require_once 'Repository.php';

class DeskRepository extends Repository {
    public function getDesksByFloor(int $floorId, string $date): array {
        $stmt = $this->database->connect()->prepare('
            SELECT 
                d.*,
                CASE 
                    WHEN m.id IS NOT NULL THEN \'maintenance\'
                    WHEN b.id IS NOT NULL THEN \'occupied\'
                    ELSE \'available\'
                END as current_status
            FROM desks d
            LEFT JOIN desk_maintenances m ON d.id = m.desk_id 
                AND :date BETWEEN m.start_date AND m.end_date
            LEFT JOIN bookings b ON d.id = b.desk_id 
                AND b.booking_date = :date 
                AND b.status IN (\'ACTIVE\', \'CHECKED_IN\')
            WHERE d.floor_id = :floorId AND d.is_active = true
        ');
        $stmt->bindParam(':floorId', $floorId, PDO::PARAM_INT);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDeskWithFeatures(int $deskId): ?array {
        $stmt = $this->database->connect()->prepare('
            SELECT d.*, f.name as feature_name, f.icon_name
            FROM desks d
            LEFT JOIN desk_features df ON d.id = df.desk_id
            LEFT JOIN features f ON df.feature_id = f.id
            WHERE d.id = :deskId
        ');
        $stmt->bindParam(':deskId', $deskId, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$results) return null;

        $desk = $results[0];
        $desk['features'] = [];
        foreach ($results as $row) {
            if ($row['feature_name']) {
                $desk['features'][] = [
                    'name' => $row['feature_name'],
                    'icon' => $row['icon_name']
                ];
            }
        }
        return $desk;
    }
}
