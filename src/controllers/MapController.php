<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';

class MapController extends AppController {

    public function index() {
        $title = "HotDesk - Floor Map";
        // $usersRepository = new UsersRepository();
        // $users = $usersRepository->getUsers();

        return $this->render("map", ["title" => $title]);
    }

    public function bookings() {
        $title = "HotDesk - My Bookings";

        return $this->render("bookings", ["title" => $title]);
    }
}