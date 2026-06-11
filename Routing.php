<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/MapController.php';
require_once 'src/controllers/AdminController.php';
require_once 'src/controllers/SettingsController.php';

/**
 * Application Router.
 * 
 * Maps incoming HTTP requests to specific Controller methods based on the URI.
 * Implements a basic Front Controller pattern for centralized request handling.
 * 
 * @package Core
 */
class Routing {

    private static $instances = [];

    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login"
        ],
        "map" => [
            "controller" => "MapController",
            "action" => "index"
        ],
        "dashboard" => [
            "controller" => "AdminController",
            "action" => "dashboard"
        ],
        "bookings" => [
            "controller" => "MapController",
            "action" => "bookings"
        ],
        "deskDetails" => [
            "controller" => "MapController",
            "action" => "deskDetails"
        ],
        "bookDesk" => [
            "controller" => "MapController",
            "action" => "bookDesk"
        ],
        "cancelBooking" => [
            "controller" => "MapController",
            "action" => "cancelBooking"
        ],
        "checkInBooking" => [
            "controller" => "MapController",
            "action" => "checkInBooking"
        ],
        "getBookingsData" => [
            "controller" => "MapController",
            "action" => "getBookingsData"
        ],
        "users" => [
            "controller" => "AdminController",
            "action" => "users"
        ],
        "updateUser" => [
            "controller" => "AdminController",
            "action" => "updateUser"
        ],
        "deleteUser" => [
            "controller" => "AdminController",
            "action" => "deleteUser"
        ],
        "resetPassword" => [
            "controller" => "AdminController",
            "action" => "resetPassword"
        ],
        "setMaintenance" => [
            "controller" => "AdminController",
            "action" => "setMaintenance"
        ],
        "settings" => [
            "controller" => "SettingsController",
            "action" => "settings"
        ],
        "addFloor" => [
            "controller" => "SettingsController",
            "action" => "addFloor"
        ],
        "updateFloor" => [
            "controller" => "SettingsController",
            "action" => "updateFloor"
        ],
        "deleteFloor" => [
            "controller" => "SettingsController",
            "action" => "deleteFloor"
        ],
        "addFeature" => [
            "controller" => "SettingsController",
            "action" => "addFeature"
        ],
        "deleteFeature" => [
            "controller" => "SettingsController",
            "action" => "deleteFeature"
        ],
        "editor" => [
            "controller" => "AdminController",
            "action" => "editor"
        ],
        "saveDesk" => [
            "controller" => "AdminController",
            "action" => "saveDesk"
        ],
        "deactivateDesk" => [
            "controller" => "AdminController",
            "action" => "deactivateDesk"
        ],
        "reactivateDesk" => [
            "controller" => "AdminController",
            "action" => "reactivateDesk"
        ],
        "hardDeleteDesk" => [
            "controller" => "AdminController",
            "action" => "hardDeleteDesk"
        ],
        "register" => [
            "controller" => "SecurityController",
            "action" => "register"
        ],
        "changePassword" => [
            "controller" => "SecurityController",
            "action" => "changePassword"
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
            http_response_code(404);
            include 'public/views/404.html';
        }
    }
}