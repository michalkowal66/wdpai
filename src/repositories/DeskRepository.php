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

    public function getAllDesksByFloor(int $floorId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT d.* 
            FROM desks d
            WHERE d.floor_id = :floorId
        ');
        $stmt->bindParam(':floorId', $floorId, PDO::PARAM_INT);
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

    public function saveDesk(?int $id, int $floorId, string $identifier, string $description, float $posX, float $posY, array $features): bool {
        $conn = $this->database->connect();
        try {
            $conn->beginTransaction();

            if ($id) {
                // Update existing desk
                $stmt = $conn->prepare('
                    UPDATE desks 
                    SET identifier = :identifier, description = :description, floor_id = :floor_id, pos_x = :pos_x, pos_y = :pos_y 
                    WHERE id = :id
                ');
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            } else {
                // Insert new desk
                $stmt = $conn->prepare('
                    INSERT INTO desks (identifier, description, floor_id, pos_x, pos_y) 
                    VALUES (:identifier, :description, :floor_id, :pos_x, :pos_y) 
                    RETURNING id
                ');
            }

            $stmt->bindParam(':identifier', $identifier);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':floor_id', $floorId, PDO::PARAM_INT);
            $stmt->bindParam(':pos_x', $posX);
            $stmt->bindParam(':pos_y', $posY);
            $stmt->execute();

            if (!$id) {
                $id = (int)$stmt->fetchColumn();
            }

            // Clear old features
            $delStmt = $conn->prepare('DELETE FROM desk_features WHERE desk_id = :desk_id');
            $delStmt->bindParam(':desk_id', $id, PDO::PARAM_INT);
            $delStmt->execute();

            // Insert new features
            if (!empty($features)) {
                $featStmt = $conn->prepare('INSERT INTO desk_features (desk_id, feature_id) VALUES (:desk_id, :feature_id)');
                foreach ($features as $fId) {
                    $featStmt->bindValue(':desk_id', $id, PDO::PARAM_INT);
                    $featStmt->bindValue(':feature_id', (int)$fId, PDO::PARAM_INT);
                    $featStmt->execute();
                }
            }

            $conn->commit();
            return true;
        } catch (PDOException $e) {
            $conn->rollBack();
            return false;
        }
    }

    public function deactivateDesk(int $id): bool {
        $conn = $this->database->connect();
        try {
            $conn->beginTransaction();

            // Soft delete the desk
            $stmt = $conn->prepare('UPDATE desks SET is_active = FALSE WHERE id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Cancel any future active bookings for this desk
            $cancelStmt = $conn->prepare("
                UPDATE bookings 
                SET status = 'CANCELLED' 
                WHERE desk_id = :desk_id AND booking_date >= CURRENT_DATE AND status = 'ACTIVE'
            ");
            $cancelStmt->bindParam(':desk_id', $id, PDO::PARAM_INT);
            $cancelStmt->execute();

            $conn->commit();
            return true;
        } catch (PDOException $e) {
            $conn->rollBack();
            return false;
        }
    }

    public function reactivateDesk(int $id): bool {
        try {
            $stmt = $this->database->connect()->prepare('UPDATE desks SET is_active = TRUE WHERE id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
