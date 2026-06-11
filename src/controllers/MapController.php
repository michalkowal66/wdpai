<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';
require_once __DIR__.'/../repositories/FloorRepository.php';
require_once __DIR__.'/../repositories/DeskRepository.php';
require_once __DIR__.'/../repositories/BookingRepository.php';

class MapController extends AppController {

    /**
     * Renders the main floor map view with available desks for a selected date.
     *
     * @return void
     */
    public function index() {
        $floorLevel = isset($_GET['floor']) ? (int)$_GET['floor'] : 1;
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'); // Default to today

        $floorRepository = new FloorRepository();
        $deskRepository = new DeskRepository();

        $allFloors = $floorRepository->getFloors();
        $currentFloor = $floorRepository->getFloorByLevel($floorLevel);

        if (!$currentFloor && count($allFloors) > 0) {
            $currentFloor = $allFloors[0];
        }

        $desks = $currentFloor ? $deskRepository->getDesksByFloor($currentFloor->getId(), $date) : [];

        // Check if user has an active booking for the selected date
        $hasBookingToday = false;
        $bookedDeskId = null;
        if (isset($_SESSION['user'])) {
            $bookingRepo = new BookingRepository();
            $userBookings = $bookingRepo->getUserBookings($_SESSION['user']->getId());
            foreach ($userBookings as $b) {
                if ($b->getBookingDate() === $date && in_array($b->getStatus(), ['ACTIVE', 'CHECKED_IN'])) {
                    $hasBookingToday = true;
                    $bookedDeskId = $b->getBooking()->getDeskId();
                    break;
                }
            }
        }

        return $this->render("map", [
            "title" => "HotDesk - Floor Map",
            "allFloors" => $allFloors,
            "currentFloor" => $currentFloor,
            "desks" => $desks,
            "selectedDate" => $date,
            "hasBookingToday" => $hasBookingToday,
            "bookedDeskId" => $bookedDeskId
        ]);
    }

    /**
     * Renders the user's bookings view (upcoming, history, or cancelled).
     *
     * @return void
     */
    public function bookings() {
        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        $userId = $_SESSION['user']->getId();
        $bookingRepo = new BookingRepository();
        $allBookings = $bookingRepo->getUserBookings($userId);

        $today = date('Y-m-d');
        $upcoming = [];
        $history = [];
        $cancelled = [];

        foreach ($allBookings as $b) {
            $isPast = $b->getBookingDate() < $today;
            $status = $b->getStatus();
            
            if ($status === 'CANCELLED' || $status === 'NO_SHOW' || ($isPast && $status === 'ACTIVE')) {
                $cancelled[] = $b;
            } elseif ($isPast && $status === 'CHECKED_IN') {
                $history[] = $b;
            } else {
                $upcoming[] = $b;
            }
        }

        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        
        $sourceArray = [];
        if ($tab === 'history') {
            $sourceArray = $history;
        } elseif ($tab === 'cancelled') {
            $sourceArray = $cancelled;
        } else {
            $sourceArray = $upcoming;
        }

        $totalCount = count($sourceArray);
        $displayBookings = array_slice($sourceArray, 0, $limit);
        $remainingCount = max(0, $totalCount - $limit);

        return $this->render("bookings", [
            "title" => "HotDesk - My Bookings",
            "tab" => $tab,
            "displayBookings" => $displayBookings,
            "limit" => $limit,
            "remainingCount" => $remainingCount,
            "totalCount" => $totalCount
        ]);
    }

    /**
     * Retrieves the paginated booking data for the current user as JSON.
     *
     * @return void
     */
    public function getBookingsData() {
        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $userId = $_SESSION['user']->getId();
        $bookingRepo = new BookingRepository();
        $allBookings = $bookingRepo->getUserBookings($userId);

        $today = date('Y-m-d');
        $upcoming = [];
        $history = [];
        $cancelled = [];

        foreach ($allBookings as $b) {
            $isPast = $b->getBookingDate() < $today;
            $status = $b->getStatus();
            
            if ($status === 'CANCELLED' || $status === 'NO_SHOW' || ($isPast && $status === 'ACTIVE')) {
                $cancelled[] = $b;
            } elseif ($isPast && $status === 'CHECKED_IN') {
                $history[] = $b;
            } else {
                $upcoming[] = $b;
            }
        }

        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        $sourceArray = [];
        if ($tab === 'history') {
            $sourceArray = $history;
        } elseif ($tab === 'cancelled') {
            $sourceArray = $cancelled;
        } else {
            $sourceArray = $upcoming;
        }

        $displayBookings = array_slice($sourceArray, $offset, $limit);
        $totalCount = count($sourceArray);
        $remainingCount = max(0, $totalCount - ($offset + $limit));

        header('Content-Type: application/json');
        echo json_encode([
            'bookings' => $displayBookings,
            'remainingCount' => $remainingCount,
            'today' => $today
        ]);
    }

    /**
     * Retrieves detailed information and features for a specific desk as JSON.
     *
     * @return void
     */
    public function deskDetails() {
        if (!$this->isGet() || !isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            return;
        }

        $id = (int)$_GET['id'];
        $deskRepository = new DeskRepository();
        $desk = $deskRepository->getDeskWithFeatures($id);
        if (!$desk) {
            http_response_code(404);
            echo json_encode(['error' => 'Desk not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($desk);
    }

    /**
     * Books a desk for the current user for a specific date.
     *
     * @return void
     */
    public function bookDesk() {
        if (!$this->isPost()) {
            return;
        }

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $deskId = isset($_POST['desk_id']) ? (int)$_POST['desk_id'] : null;
        $date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        $userId = $_SESSION['user']->getId();

        if (!$deskId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing desk ID']);
            return;
        }

        // Prevent booking in the past
        if ($date < date('Y-m-d')) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'You cannot book a desk in the past.']);
            return;
        }

        $bookingRepo = new BookingRepository();
        $result = $bookingRepo->bookDesk($userId, $deskId, $date);

        if ($result === true) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            $message = $result === 'user_limit' 
                ? 'You already have an active booking for this date.' 
                : 'This desk is already booked for the selected date.';
            echo json_encode(['status' => 'error', 'message' => $message]);
        }
    }

    /**
     * Cancels an active booking for the current user.
     *
     * @return void
     */
    public function cancelBooking() {
        if (!$this->isPost()) {
            return;
        }

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $userId = $_SESSION['user']->getId();

        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing booking ID']);
            return;
        }

        $bookingRepo = new BookingRepository();
        $success = $bookingRepo->cancelBooking($id, $userId);

        if ($success) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Failed to cancel. Booking may not be active or belongs to someone else.']);
        }
    }

    /**
     * Checks in the user for their booking on the current date.
     *
     * @return void
     */
    public function checkInBooking() {
        if (!$this->isPost()) {
            return;
        }

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $userId = $_SESSION['user']->getId();

        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing booking ID']);
            return;
        }

        $bookingRepo = new BookingRepository();
        $success = $bookingRepo->checkInBooking($id, $userId);

        if ($success) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Failed to check in. Check-in is only available on the booking date.']);
        }
    }
}
