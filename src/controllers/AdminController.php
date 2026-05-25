<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';
require_once __DIR__.'/../repositories/StatisticsRepository.php';

class AdminController extends AppController {

    public function dashboard() {
        $statsRepo = new StatisticsRepository();
        
        $utilization = $statsRepo->getTodayUtilization();
        $topDesks = $statsRepo->getTopDesks(5);
        $topUsers = $statsRepo->getUserLeaderboard(5);
        $topFeatures = $statsRepo->getFeaturePopularity();
        $globalStats = $statsRepo->getGlobalStats();

        // Calculate No-Show Rate
        $noShowRate = 0;
        if ($globalStats['total_reservations'] > 0) {
            $noShowRate = round(($globalStats['total_no_shows'] / $globalStats['total_reservations']) * 100, 1);
        }

        // Calculate Utilization Rate
        $utilizationRate = 0;
        if ($utilization['total_desks'] > 0) {
            $utilizationRate = round(($utilization['booked_desks'] / $utilization['total_desks']) * 100, 1);
        }

        return $this->render("dashboard", [
            "title" => "HotDesk - Admin Dashboard",
            "utilizationRate" => $utilizationRate,
            "bookedDesks" => $utilization['booked_desks'],
            "totalDesks" => $utilization['total_desks'],
            "noShowRate" => $noShowRate,
            "totalNoShows" => $globalStats['total_no_shows'],
            "topDesks" => $topDesks,
            "topUsers" => $topUsers,
            "topFeatures" => $topFeatures
        ]);
    }

    public function users() {
        $title = "HotDesk - User Management";
        $usersRepository = UsersRepository::getInstance();
        
        $limit = 5;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $totalUsers = $usersRepository->getUsersCount();
        $usersList = $usersRepository->getUsers($limit, $offset);
        $totalPages = ceil($totalUsers / $limit);

        return $this->render("users", [
            "title" => $title,
            "users" => $usersList,
            "currentPage" => $page,
            "totalPages" => $totalPages,
            "totalUsers" => $totalUsers,
            "limit" => $limit,
            "offset" => $offset
        ]);
    }

    public function updateUser() {
        if (!$this->isPost()) {
            return;
        }

        $id = (int)$_POST['id'];
        $fullName = $_POST['full_name'];
        $jobTitle = $_POST['job_title'];
        $role = $_POST['role'];
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] === 'true';

        $usersRepository = UsersRepository::getInstance();
        $user = $usersRepository->getUserById($id);

        // Security check: Don't allow demoting/deactivating the last admin
        if ($user && $user->getRole() === 'ADMIN' && ($role !== 'ADMIN' || !$isActive)) {
            if ($usersRepository->getAdminCount() <= 1) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Cannot demote or deactivate the last administrator.']);
                return;
            }
        }

        $usersRepository->updateUser($id, $fullName, $jobTitle, $role, $isActive);

        http_response_code(200);
        echo json_encode(['status' => 'success']);
    }

    public function deleteUser() {
        if (!$this->isPost()) {
            return;
        }

        $id = (int)$_POST['id'];

        $usersRepository = UsersRepository::getInstance();
        $user = $usersRepository->getUserById($id);

        // Security check: Don't allow deleting the last admin
        if ($user && $user->getRole() === 'ADMIN') {
            if ($usersRepository->getAdminCount() <= 1) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete the last administrator.']);
                return;
            }
        }

        $usersRepository->deleteUser($id);

        http_response_code(200);
        echo json_encode(['status' => 'success']);
    }

    public function desks() {
        $title = "HotDesk - Desk Management";
        require_once __DIR__.'/../repositories/DeskRepository.php';
        $deskRepository = new DeskRepository();
        
        $limit = 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;

        $totalDesks = $deskRepository->getDesksCount();
        $desksList = $deskRepository->getAllDesks($limit, $offset);
        $totalPages = ceil($totalDesks / $limit);

        return $this->render("desks", [
            "title" => $title,
            "desks" => $desksList,
            "currentPage" => $page,
            "totalPages" => $totalPages,
            "totalDesks" => $totalDesks,
            "limit" => $limit,
            "offset" => $offset
        ]);
    }

    public function setMaintenance() {
        if (!$this->isPost()) {
            return;
        }

        $deskId = isset($_POST['desk_id']) ? (int)$_POST['desk_id'] : null;
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;
        $reason = $_POST['reason'] ?? '';

        if (!$deskId || !$startDate || !$endDate) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
            return;
        }

        if ($startDate > $endDate) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'End date cannot be before start date.']);
            return;
        }

        require_once __DIR__.'/../repositories/DeskRepository.php';
        $deskRepository = new DeskRepository();
        
        $success = $deskRepository->setMaintenance($deskId, $startDate, $endDate, $reason);

        if ($success) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Failed to set maintenance. Date range might be invalid or conflict exists.']);
        }
    }

    public function editor() {
        require_once __DIR__.'/../repositories/FloorRepository.php';
        require_once __DIR__.'/../repositories/DeskRepository.php';
        require_once __DIR__.'/../repositories/FeatureRepository.php';

        $floorLevel = isset($_GET['floor']) ? (int)$_GET['floor'] : 1;

        $floorRepository = new FloorRepository();
        $deskRepository = new DeskRepository();
        $featureRepo = new FeatureRepository();

        $allFloors = $floorRepository->getFloors();
        $currentFloor = $floorRepository->getFloorByLevel($floorLevel);

        if (!$currentFloor && count($allFloors) > 0) {
            $currentFloor = $allFloors[0];
        }

        // For the editor, we fetch all desks including deactivated ones.
        $desks = [];
        if ($currentFloor) {
            $desks = $deskRepository->getAllDesksByFloor($currentFloor->getId());
        }
        
        $features = $featureRepo->getAllFeatures();

        return $this->render("editor", [
            "title" => "HotDesk - Visual Desk Editor",
            "allFloors" => $allFloors,
            "currentFloor" => $currentFloor,
            "desks" => $desks,
            "features" => $features
        ]);
    }

    public function saveDesk() {
        if (!$this->isPost()) return;

        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $floorId = (int)($_POST['floor_id'] ?? 0);
        $identifier = trim($_POST['identifier'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $posX = (float)($_POST['pos_x'] ?? 0);
        $posY = (float)($_POST['pos_y'] ?? 0);
        
        // Handle features array
        $features = [];
        if (isset($_POST['features']) && trim($_POST['features']) !== '') {
            $features = explode(',', trim($_POST['features']));
        }

        if (!$floorId || empty($identifier) || $posX <= 0 || $posY <= 0) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing required fields.']);
            return;
        }

        require_once __DIR__.'/../repositories/DeskRepository.php';
        $deskRepo = new DeskRepository();

        if ($deskRepo->saveDesk($id, $floorId, $identifier, $description, $posX, $posY, $features)) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Failed to save desk. Identifier might not be unique on this floor.']);
        }
    }

    public function deactivateDesk() {
        if (!$this->isPost()) return;

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            return;
        }

        require_once __DIR__.'/../repositories/DeskRepository.php';
        $deskRepo = new DeskRepository();

        if ($deskRepo->deactivateDesk($id)) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Failed to deactivate desk.']);
        }
    }

    public function reactivateDesk() {
        if (!$this->isPost()) return;

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            return;
        }

        require_once __DIR__.'/../repositories/DeskRepository.php';
        $deskRepo = new DeskRepository();

        if ($deskRepo->reactivateDesk($id)) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Failed to reactivate desk.']);
        }
    }

    public function hardDeleteDesk() {
        if (!$this->isPost()) return;

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            return;
        }

        require_once __DIR__.'/../repositories/DeskRepository.php';
        $deskRepo = new DeskRepository();

        if ($deskRepo->hardDeleteDesk($id)) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Cannot delete this desk because it has booking history. Please use Deactivate instead.']);
        }
    }
}