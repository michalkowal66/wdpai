<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';
require_once 'src/controllers/AdminController.php';

// TODO 2 /dashboard -- wszystkie dnae
// /dashboard/12234 -- wyciagnie nam jakis element o wskaznaym ID 12234
// REGEX
class Routing {

    private static $instances = [];

    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
        "dashboard" => [
            "controller" => "DashboardController",
            "action" => "index"
        ],
        "bookings" => [
            "controller" => "DashboardController",
            "action" => "bookings"
        ],
        "users" => [
            "controller" => "AdminController",
            "action" => "users"
        ],
        "updateUser" => [
            "controller" => "AdminController",
            "action" => "updateUser"
        ],
        "register" => [
            "controller" => "SecurityController",
            "action" => "register"
        ],
        "logout" => [
            "controller" => "SecurityController",
            "action" => "logout"
        ],
        "inactive" => [
            "controller" => "SecurityController",
            "action" => "inactive"
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

            if (!isset(self::$instances[$controller])) {
                self::$instances[$controller] = new $controller;
            }
            
            $controllerObj = self::$instances[$controller];
            $id = null;

            $controllerObj->$action($id);
        }
        else {
            include 'public/views/404.html';
        }
    }
}