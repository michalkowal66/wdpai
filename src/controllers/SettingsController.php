<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/FeatureRepository.php';
require_once __DIR__.'/../repositories/FloorRepository.php';

class SettingsController extends AppController {
    
    public function settings() {
        $featureRepo = new FeatureRepository();
        $floorRepo = new FloorRepository();

        return $this->render('settings', [
            'title' => 'Settings - HotDesk',
            'features' => $featureRepo->getAllFeatures(),
            'floors' => $floorRepo->getFloors()
        ]);
    }

    public function addFeature() {
        if (!$this->isPost()) return;

        $name = $_POST['name'] ?? '';
        $icon = $_POST['icon'] ?? '';

        if (empty($name) || empty($icon)) {
            http_response_code(400);
            echo json_encode(['message' => 'Missing fields']);
            return;
        }

        $featureRepo = new FeatureRepository();
        if ($featureRepo->addFeature($name, $icon)) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Failed to add feature. Name might be duplicated.']);
        }
    }

    public function deleteFeature() {
        if (!$this->isPost()) return;

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            return;
        }

        $featureRepo = new FeatureRepository();
        
        if ($featureRepo->isFeatureInUse($id)) {
            http_response_code(400);
            echo json_encode(['message' => 'Cannot delete: Feature is currently assigned to one or more desks.']);
            return;
        }

        if ($featureRepo->deleteFeature($id)) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Failed to delete.']);
        }
    }

    public function addFloor() {
        if (!$this->isPost()) return;

        $name = $_POST['name'] ?? '';
        $level = (int)($_POST['level'] ?? 0);

        if (empty($name) || $level <= 0 || !isset($_FILES['map_image']) || $_FILES['map_image']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid form data or file upload error.']);
            return;
        }

        $file = $_FILES['map_image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['message' => 'Unsupported file type. Only JPG, PNG and WEBP are allowed.']);
            return;
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            http_response_code(400);
            echo json_encode(['message' => 'File is too large (max 5MB).']);
            return;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('map_') . '.' . $extension;
        $uploadDir = __DIR__ . '/../../public/uploads/maps/';
        $destination = $uploadDir . $filename;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $floorRepo = new FloorRepository();
            $publicPath = '/public/uploads/maps/' . $filename;
            
            if ($floorRepo->createFloor($name, $level, $publicPath)) {
                http_response_code(200);
                echo json_encode(['status' => 'success']);
                return;
            } else {
                unlink($destination); // Cleanup if DB insert fails
                http_response_code(400);
                echo json_encode(['message' => 'Database error. Floor level might already exist.']);
                return;
            }
        }

        http_response_code(500);
        echo json_encode(['message' => 'Failed to move uploaded file.']);
    }

    public function deleteFloor() {
        if (!$this->isPost()) return;

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            return;
        }

        $floorRepo = new FloorRepository();
        
        if ($floorRepo->getDeskCountOnFloor($id) > 0) {
            http_response_code(400);
            echo json_encode(['message' => 'Cannot delete floor: it contains existing desks. Remove all desks from this floor first.']);
            return;
        }

        if ($floorRepo->deleteFloor($id)) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Failed to delete floor.']);
        }
    }

    public function updateFloor() {
        if (!$this->isPost()) return;

        $id = (int)($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $level = (int)($_POST['level'] ?? 0);

        if (!$id || empty($name) || $level <= 0) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid form data.']);
            return;
        }

        $floorRepo = new FloorRepository();
        $publicPath = null;

        // Handle optional image upload
        if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['map_image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                http_response_code(400);
                echo json_encode(['message' => 'Unsupported file type. Only JPG, PNG and WEBP are allowed.']);
                return;
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB
                http_response_code(400);
                echo json_encode(['message' => 'File is too large (max 5MB).']);
                return;
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('map_') . '.' . $extension;
            $uploadDir = __DIR__ . '/../../public/uploads/maps/';
            $destination = $uploadDir . $filename;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $publicPath = '/public/uploads/maps/' . $filename;
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to move uploaded file.']);
                return;
            }
        }

        if ($floorRepo->updateFloor($id, $name, $level, $publicPath)) {
            http_response_code(200);
            echo json_encode(['status' => 'success']);
        } else {
            if ($publicPath) {
                unlink(__DIR__ . '/../..' . $publicPath); // Cleanup
            }
            http_response_code(400);
            echo json_encode(['message' => 'Database error. Floor level might already exist.']);
        }
    }
}
