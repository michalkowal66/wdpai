<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';
require_once __DIR__.'/../dto/UserAttendanceDTO.php';
require_once __DIR__.'/../dto/DeskPopularityDTO.php';
require_once __DIR__.'/../dto/FeaturePopularityDTO.php';

use Models\User;
use DTO\UserAttendanceDTO;
use DTO\DeskPopularityDTO;
use DTO\FeaturePopularityDTO;

class StatisticsRepository extends Repository {
    /**
     * Gets the total number of desks and how many are booked today.
     *
     * @return array An associative array with keys 'total_desks' and 'booked_desks'.
     */
    public function getTodayUtilization(): array {
        $query = $this->database->connect()->prepare("
            SELECT 
                (SELECT COUNT(*) FROM desks WHERE is_active = true) as total_desks,
                (SELECT COUNT(*) FROM bookings WHERE booking_date = CURRENT_DATE AND status IN ('ACTIVE', 'CHECKED_IN')) as booked_desks
        ");
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Gets the most popular desks based on the number of successful bookings.
     *
     * @param int $limit The maximum number of desks to return.
     * @return array An array of DeskPopularityDTO objects.
     */
    public function getTopDesks(int $limit = 5): array {
        $query = $this->database->connect()->prepare("
            SELECT * FROM view_desk_popularity LIMIT :limit
        ");
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $dtos = [];
        foreach ($results as $row) {
            $dtos[] = new DeskPopularityDTO($row['identifier'], $row['floor_name'], $row['successful_bookings']);
        }
        return $dtos;
    }

    /**
     * Gets the most active users based on their check-in count and reliability.
     *
     * @param int $limit The maximum number of users to return.
     * @return array An array of UserAttendanceDTO objects.
     */
    public function getUserLeaderboard(int $limit = 5): array {
        $query = $this->database->connect()->prepare("
            SELECT * FROM view_user_attendance LIMIT :limit
        ");
        $query->bindParam(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $dtos = [];
        foreach ($results as $row) {
            $user = new User($row['user_id'], '', '', $row['full_name'], $row['job_title'], 'EMPLOYEE', true);
            $dtos[] = new UserAttendanceDTO($user, $row['check_ins'], $row['reliability_score']);
        }
        return $dtos;
    }

    /**
     * Gets the most popular features based on total bookings.
     *
     * @return array An array of FeaturePopularityDTO objects.
     */
    public function getFeaturePopularity(): array {
        $query = $this->database->connect()->prepare("
            SELECT * FROM view_feature_popularity LIMIT 6
        ");
        $query->execute();
        
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $dtos = [];
        foreach ($results as $row) {
            $dtos[] = new FeaturePopularityDTO($row['feature_name'], $row['icon_name'], $row['total_bookings']);
        }
        return $dtos;
    }

    /**
     * Gets global statistics regarding all bookings.
     *
     * @return array An associative array with keys 'total_reservations' and 'total_no_shows'.
     */
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