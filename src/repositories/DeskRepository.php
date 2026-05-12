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

    public function getAllDesks(int $limit = 10, int $offset = 0): array {
        $stmt = $this->database->connect()->prepare('
            SELECT d.*, f.name as floor_name
            FROM desks d
            JOIN floors f ON d.floor_id = f.id
            ORDER BY f.level ASC, d.identifier ASC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDesksCount(): int {
        $stmt = $this->database->connect()->prepare('SELECT COUNT(*) FROM desks');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function setMaintenance(int $deskId, string $startDate, string $endDate, string $reason): bool {
        $conn = $this->database->connect();
        try {
            $conn->beginTransaction();

            // 1. Insert the maintenance block
            $stmt = $conn->prepare('
                INSERT INTO desk_maintenances (desk_id, start_date, end_date, reason)
                VALUES (:desk_id, :start_date, :end_date, :reason)
            ');
            $stmt->bindParam(':desk_id', $deskId, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->bindParam(':reason', $reason);
            $stmt->execute();

            // 2. Automatically cancel any ACTIVE bookings within this date range
            $cancelStmt = $conn->prepare("
                UPDATE bookings 
                SET status = 'CANCELLED' 
                WHERE desk_id = :desk_id 
                  AND booking_date >= :start_date 
                  AND booking_date <= :end_date
                  AND status = 'ACTIVE'
            ");
            $cancelStmt->bindParam(':desk_id', $deskId, PDO::PARAM_INT);
            $cancelStmt->bindParam(':start_date', $startDate);
            $cancelStmt->bindParam(':end_date', $endDate);
            $cancelStmt->execute();

            $conn->commit();
            return true;
        } catch (PDOException $e) {
            $conn->rollBack();
            return false;
        }
    }
}
