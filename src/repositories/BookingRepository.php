<?php

require_once 'Repository.php';

class BookingRepository extends Repository {
    public function bookDesk(int $userId, int $deskId, string $date): bool {
        try {
            $stmt = $this->database->connect()->prepare('
                INSERT INTO bookings (user_id, desk_id, booking_date)
                VALUES (:user_id, :desk_id, :booking_date)
            ');
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':desk_id', $deskId, PDO::PARAM_INT);
            $stmt->bindParam(':booking_date', $date);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Check if it's a unique constraint violation (code 23505)
            if ($e->getCode() === '23505') {
                return false;
            }
            throw $e;
        }
    }

    public function getUserBookings(int $userId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT b.*, 
                   d.identifier as desk_identifier, 
                   d.description as desk_description, 
                   f.name as floor_name, 
                   f.level as floor_level,
                   f.map_image_url as floor_map_url
            FROM bookings b
            JOIN desks d ON b.desk_id = d.id
            JOIN floors f ON d.floor_id = f.id
            WHERE b.user_id = :user_id
            ORDER BY b.booking_date DESC, b.created_at DESC
        ');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cancelBooking(int $id, int $userId): bool {
        $stmt = $this->database->connect()->prepare('
            UPDATE bookings 
            SET status = \'CANCELLED\' 
            WHERE id = :id AND user_id = :user_id AND status = \'ACTIVE\'
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    public function checkInBooking(int $id, int $userId): bool {
        $stmt = $this->database->connect()->prepare('
            UPDATE bookings 
            SET status = \'CHECKED_IN\', checked_in_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND user_id = :user_id AND status = \'ACTIVE\' AND booking_date = CURRENT_DATE
        ');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
}
