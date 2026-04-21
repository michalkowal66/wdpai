<?php

require_once 'AppController.php';

class AdminController extends AppController {

    public function users() {
        $title = "HotDesk - User Management";

        return $this->render("users", ["title" => $title]);
    }
}
