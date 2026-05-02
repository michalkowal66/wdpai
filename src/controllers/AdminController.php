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
}