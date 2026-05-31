<?php
require_once "src/models/User.php";

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

set_exception_handler(function (\Throwable $exception) {
    error_log($exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    http_response_code(500);
    include 'public/views/500.html';
    exit;
});

require_once "Routing.php";

$path = trim($_SERVER["REQUEST_URI"], '/');
$path = parse_url($path, PHP_URL_PATH);

Routing::run($path);