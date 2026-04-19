<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';

// TODO musimy zapewnic, ze utworzony 
// obiekt kontrollera ma tylko jedna instancję - SINGLETON

// TODO 2 /dashboard -- wszystkie dnae
// /dashboard/12234 -- wyciagnie nam jakis element o wskaznaym ID 12234
// REGEX
class Routing {

    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
        "dashboard" => [
            "controller" => "DashboardController",
            "action" => "index"
        ],
        "register" => [
            "controller" => "SecurityController",
            "action" => "register"
        ],
        "" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
    ];

    public static function run(string $path) {
        if (array_key_exists($path, Routing::$routes)) {
            $controller = Routing::$routes[$path]["controller"];
            $action = Routing::$routes[$path]["action"];

            $controllerObj = new $controller;
            $id = null;

            $controllerObj->$action($id);
        }
        else {
            include 'public/views/404.html';
        }
    }
}