<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';
require_once __DIR__.'/../repositories/FloorRepository.php';
require_once __DIR__.'/../repositories/DeskRepository.php';
require_once __DIR__.'/../repositories/BookingRepository.php';

class MapController extends AppController {

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

        $desks = $currentFloor ? $deskRepository->getDesksByFloor($currentFloor['id'], $date) : [];

        return $this->render("map", [
            "title" => "HotDesk - Floor Map",
            "allFloors" => $allFloors,
            "currentFloor" => $currentFloor,
            "desks" => $desks,
            "selectedDate" => $date
        ]);
    }

    public function bookings() {
        if (!isset($_SESSION['user'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/login");
            exit();
        }

        $userId = $_SESSION['user']['id'];
        $bookingRepo = new BookingRepository();
        $allBookings = $bookingRepo->getUserBookings($userId);

        $today = date('Y-m-d');
        $upcoming = [];
        $history = [];
        $cancelled = [];

        foreach ($allBookings as $b) {
            $isPast = $b['booking_date'] < $today;
            
            if ($b['status'] === 'CANCELLED' || $b['status'] === 'NO_SHOW' || ($isPast && $b['status'] === 'ACTIVE')) {
                $cancelled[] = $b;
            } elseif ($isPast && $b['status'] === 'CHECKED_IN') {
                $history[] = $b;
            } else {
                $upcoming[] = $b;
            }
        }

        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';
        $displayBookings = [];
        if ($tab === 'history') {
            $displayBookings = $history;
        } elseif ($tab === 'cancelled') {
            $displayBookings = $cancelled;
        } else {
            $displayBookings = $upcoming;
        }

        return $this->render("bookings", [
            "title" => "HotDesk - My Bookings",
            "tab" => $tab,
            "displayBookings" => $displayBookings
        ]);
    }

    public function deskDetails() {
        if (!$this->isGet() || !isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            return;
        }

        $deskId = (int)$_GET['id'];
        $deskRepository = new DeskRepository();
        $desk = $deskRepository->getDeskWithFeatures($deskId);

        if (!$desk) {
            http_response_code(404);
            echo json_encode(['error' => 'Desk not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($desk);
    }

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
        $userId = $_SESSION['user']['id'];

        if (!$deskId) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing desk ID']);
            return;
        }

        $bookingRepo = new BookingRepository();
        $success = $bookingRepo->bookDesk($userId, $deskId, $date);

        if ($success) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'This desk is already booked for the selected date.']);
        }
    }

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
        $userId = $_SESSION['user']['id'];

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
        $userId = $_SESSION['user']['id'];

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
