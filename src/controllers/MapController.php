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
        $title = "HotDesk - My Bookings";

        return $this->render("bookings", ["title" => $title]);
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
}
