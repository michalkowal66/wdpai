<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Desk.php';
require_once __DIR__.'/../models/Feature.php';
require_once __DIR__.'/../dto/DeskDetailsDTO.php';

use Models\Desk;
use Models\Feature;
use DTO\DeskDetailsDTO;

class DeskRepository extends Repository {
    /**
     * Retrieves desks by floor and date, including their current status.
     *
     * @param int $floorId The ID of the floor.
     * @param string $date The date to check for desk availability (Y-m-d).
     * @return array An array of DeskDetailsDTO objects.
     */
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
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dtos = [];
        foreach ($results as $row) {
            $desk = new Desk($row['id'], $row['identifier'], $row['description'], $row['floor_id'], $row['pos_x'], $row['pos_y'], $row['is_active']);
            $dtos[] = new DeskDetailsDTO($desk, $row['current_status']);
        }
        return $dtos;
    }

    /**
     * Retrieves all desks on a specific floor, regardless of status.
     *
     * @param int $floorId The ID of the floor.
     * @return array An array of Desk objects.
     */
    public function getAllDesksByFloor(int $floorId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT d.* 
            FROM desks d
            WHERE d.floor_id = :floorId
        ');
        $stmt->bindParam(':floorId', $floorId, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $desks = [];
        foreach ($results as $row) {
            $desks[] = new Desk($row['id'], $row['identifier'], $row['description'], $row['floor_id'], $row['pos_x'], $row['pos_y'], $row['is_active']);
        }
        return $desks;
    }

    /**
     * Retrieves a specific desk along with its associated features.
     *
     * @param int $deskId The ID of the desk.
     * @return DeskDetailsDTO|null A DeskDetailsDTO object if found, null otherwise.
     */
    public function getDeskWithFeatures(int $deskId): ?DeskDetailsDTO {
        $stmt = $this->database->connect()->prepare('
            SELECT d.*, f.id as feature_id, f.name as feature_name, f.icon_name
            FROM desks d
            LEFT JOIN desk_features df ON d.id = df.desk_id
            LEFT JOIN features f ON df.feature_id = f.id
            WHERE d.id = :deskId
        ');
        $stmt->bindParam(':deskId', $deskId, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$results) return null;

        $deskRow = $results[0];
        $desk = new Desk($deskRow['id'], $deskRow['identifier'], $deskRow['description'], $deskRow['floor_id'], $deskRow['pos_x'], $deskRow['pos_y'], $deskRow['is_active']);
        
        $features = [];
        foreach ($results as $row) {
            if ($row['feature_name']) {
                $features[] = new Feature($row['feature_id'], $row['feature_name'], $row['icon_name']);
            }
        }
        
        $hasBookings = $this->hasBookingHistory($deskId);
        
        return new DeskDetailsDTO($desk, null, $features, $hasBookings);
    }

    /**
     * Retrieves a paginated list of all desks.
     *
     * @param int $limit The maximum number of records to return.
     * @param int $offset The number of records to skip.
     * @return array An array of DeskDetailsDTO objects.
     */
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
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dtos = [];
        foreach ($results as $row) {
            $desk = new Desk($row['id'], $row['identifier'], $row['description'], $row['floor_id'], $row['pos_x'], $row['pos_y'], $row['is_active']);
            $dtos[] = new DeskDetailsDTO($desk, null, [], false, $row['floor_name']);
        }
        return $dtos;
    }

    /**
     * Gets the total count of all desks.
     *
     * @return int The total number of desks.
     */
    public function getDesksCount(): int {
        $stmt = $this->database->connect()->prepare('SELECT COUNT(*) FROM desks');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Sets a maintenance period for a specific desk and cancels overlapping bookings.
     *
     * @param int $deskId The ID of the desk.
     * @param string $startDate The start date of the maintenance (Y-m-d).
     * @param string $endDate The end date of the maintenance (Y-m-d).
     * @param string $reason The reason for maintenance.
     * @return bool True if successful, false otherwise.
     */
    public function setMaintenance(int $deskId, string $startDate, string $endDate, string $reason): bool {
        $conn = $this->database->connect();
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare('
                INSERT INTO desk_maintenances (desk_id, start_date, end_date, reason)
                VALUES (:desk_id, :start_date, :end_date, :reason)
            ');
            $stmt->bindParam(':desk_id', $deskId, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->bindParam(':reason', $reason);
            $stmt->execute();

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

    /**
     * Saves a new desk or updates an existing one, along with its features.
     *
     * @param int|null $id The ID of the desk, or null if creating a new one.
     * @param int $floorId The ID of the floor where the desk is located.
     * @param string $identifier The unique identifier for the desk.
     * @param string $description The description of the desk.
     * @param float $posX The X-coordinate position of the desk.
     * @param float $posY The Y-coordinate position of the desk.
     * @param array $features An array of feature IDs associated with the desk.
     * @return bool True if the desk was saved successfully, false otherwise.
     */
    public function saveDesk(?int $id, int $floorId, string $identifier, string $description, float $posX, float $posY, array $features): bool {
        $conn = $this->database->connect();
        try {
            $conn->beginTransaction();

            if ($id) {
                $stmt = $conn->prepare('
                    UPDATE desks 
                    SET identifier = :identifier, description = :description, floor_id = :floor_id, pos_x = :pos_x, pos_y = :pos_y 
                    WHERE id = :id
                ');
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            } else {
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

            $delStmt = $conn->prepare('DELETE FROM desk_features WHERE desk_id = :desk_id');
            $delStmt->bindParam(':desk_id', $id, PDO::PARAM_INT);
            $delStmt->execute();

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

    /**
     * Deactivates a desk and cancels its future bookings.
     *
     * @param int $id The ID of the desk to deactivate.
     * @return bool True if successful, false otherwise.
     */
    public function deactivateDesk(int $id): bool {
        $conn = $this->database->connect();
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare('UPDATE desks SET is_active = FALSE WHERE id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

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

    /**
     * Reactivates a deactivated desk.
     *
     * @param int $id The ID of the desk to reactivate.
     * @return bool True if successful, false otherwise.
     */
    public function reactivateDesk(int $id): bool {
        try {
            $stmt = $this->database->connect()->prepare('UPDATE desks SET is_active = TRUE WHERE id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Checks whether a desk has any booking history.
     *
     * @param int $deskId The ID of the desk.
     * @return bool True if the desk has been booked at least once, false otherwise.
     */
    public function hasBookingHistory(int $deskId): bool {
        $stmt = $this->database->connect()->prepare('SELECT COUNT(*) FROM bookings WHERE desk_id = :deskId');
        $stmt->bindParam(':deskId', $deskId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Permanently deletes a desk from the database.
     * Will fail if the desk has booking history.
     *
     * @param int $id The ID of the desk to delete.
     * @return bool True if the desk was successfully deleted, false otherwise.
     */
    public function hardDeleteDesk(int $id): bool {
        if ($this->hasBookingHistory($id)) {
            return false;
        }
        
        try {
            $stmt = $this->database->connect()->prepare('DELETE FROM desks WHERE id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
