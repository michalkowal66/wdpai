<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';

class AdminController extends AppController {

    public function users() {
        $title = "HotDesk - User Management";
        $usersRepository = new UsersRepository();
        $usersList = $usersRepository->getUsers();

        return $this->render("users", [
            "title" => $title,
            "users" => $usersList
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

        $usersRepository = new UsersRepository();
        $user = $usersRepository->getUserById($id);

        // Security check: Don't allow demoting/deactivating the last admin
        if ($user['role'] === 'ADMIN' && ($role !== 'ADMIN' || !$isActive)) {
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

        $usersRepository = new UsersRepository();
        $user = $usersRepository->getUserById($id);

        // Security check: Don't allow deleting the last admin
        if ($user['role'] === 'ADMIN') {
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
}