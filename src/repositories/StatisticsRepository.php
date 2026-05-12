<?php

require_once 'Repository.php';

class StatisticsRepository extends Repository {
    public function getTodayUtilization(): array {
        // Oblicza zajętość z dzisiejszego dnia (ile biurek z puli wszystkich jest zajętych/zameldowanych)
        $query = $this->database->connect()->prepare("
            SELECT 
                (SELECT COUNT(*) FROM desks WHERE is_active = true) as total_desks,
                (SELECT COUNT(*) FROM bookings WHERE booking_date = CURRENT_DATE AND status IN ('ACTIVE', 'CHECKED_IN')) as booked_desks
        ");
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getTopDesks(int $limit = 5): array {
        // Korzysta z przygotowanego widoku bazodanowego
        $query = $this->database->connect()->prepare("
            SELECT * FROM view_desk_popularity LIMIT :limit
        ");
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserLeaderboard(int $limit = 5): array {
        // Korzysta z przygotowanego widoku bazodanowego
        $query = $this->database->connect()->prepare("
            SELECT * FROM view_user_attendance LIMIT :limit
        ");
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFeaturePopularity(): array {
        // Korzysta z przygotowanego widoku bazodanowego
        $query = $this->database->connect()->prepare("
            SELECT * FROM view_feature_popularity LIMIT 6
        ");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGlobalStats(): array {
        $query = $this->database->connect()->prepare("
            SELECT 
                COUNT(*) as total_reservations,
                COUNT(CASE WHEN status = 'NO_SHOW' THEN 1 END) as total_no_shows
            FROM bookings
        ");
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}
