<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';

class DashboardController extends AppController {

    public function index() {
        $title = "HotDesk - Floor Map";
        // $usersRepository = new UsersRepository();
        // $users = $usersRepository->getUsers();

        return $this->render("dashboard", ["title" => $title]);
    }
}