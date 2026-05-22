<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Booking.php';
require_once __DIR__.'/../dto/BookingDetailsDTO.php';

use Models\Booking;
use DTO\BookingDetailsDTO;

class BookingRepository extends Repository {
    public function bookDesk(int $userId, int $deskId, string $date): string|bool {
        try {
            $stmt = $this->database->connect()->prepare('
                INSERT INTO bookings (user_id, desk_id, booking_date)
                VALUES (:user_id, :desk_id, :booking_date)
            ');
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':desk_id', $deskId, PDO::PARAM_INT);
            $stmt->bindParam(':booking_date', $date);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                if (strpos($e->getMessage(), 'unique_active_user_booking') !== false) {
                    return 'user_limit';
                }
                return 'desk_taken';
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
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dtos = [];
        foreach ($results as $row) {
            $booking = new Booking($row['id'], $row['user_id'], $row['desk_id'], $row['booking_date'], $row['status'], $row['created_at']);
            $dtos[] = new BookingDetailsDTO($booking, $row['desk_identifier'], $row['floor_name'], $row['floor_map_url'], $row['floor_level']);
        }
        return $dtos;
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

    public function autoCancelNoShows(): int {
        $stmt = $this->database->connect()->prepare("
            UPDATE bookings 
            SET status = 'NO_SHOW' 
            WHERE status = 'ACTIVE' 
            AND (
                -- Przeterminowane z poprzednich dni
                booking_date < CURRENT_DATE
                OR
                -- Przeterminowane dzisiejsze (15 min po 8:00 ALBO 15 min po utworzeniu)
                (
                    booking_date = CURRENT_DATE 
                    AND CURRENT_TIMESTAMP > GREATEST(
                        created_at, 
                        CURRENT_DATE + TIME '08:00:00'
                    ) + INTERVAL '15 minutes'
                )
            )
        ");
        $stmt->execute();
        return $stmt->rowCount();
    }
}
