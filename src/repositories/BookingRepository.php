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
}
