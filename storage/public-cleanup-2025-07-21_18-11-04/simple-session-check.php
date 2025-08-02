<?php

// Start session handling
session_start();

// Bootstrap Laravel partially
require __DIR__."/../vendor/autoload.php";
$app = require_once __DIR__."/../bootstrap/app.php";

// Simple response
header("Content-Type: application/json");

// Check PHP session
$phpSession = [
    "id" => session_id(),
    "data" => $_SESSION ?? []
];

// Check cookies
$cookies = $_COOKIE;

// Check if we have auth data in session
$authenticated = false;
$userId = null;

if (isset($_SESSION["portal_user_id"])) {
    $authenticated = true;
    $userId = $_SESSION["portal_user_id"];
}

echo json_encode([
    "php_session" => $phpSession,
    "cookies" => $cookies,
    "authenticated" => $authenticated,
    "user_id" => $userId,
    "server" => [
        "session_save_path" => session_save_path(),
        "session_name" => session_name()
    ]
], JSON_PRETTY_PRINT);
