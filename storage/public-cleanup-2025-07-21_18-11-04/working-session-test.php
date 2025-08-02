<?php

// Start native PHP session
session_start();

// Set headers
header("Content-Type: application/json");
header("Access-Control-Allow-Credentials: true");

// Bootstrap Laravel
require __DIR__."/../vendor/autoload.php";
$app = require_once __DIR__."/../bootstrap/app.php";

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Force session config
config([
    "session.secure" => false,
    "session.same_site" => "lax",
    "session.http_only" => true,
    "session.domain" => null
]);

$response = $kernel->handle($request);

// Get Laravel session data
$laravelSession = [
    "id" => session()->getId(),
    "started" => session()->isStarted(),
    "data" => session()->all()
];

// Get auth status
$user = \Illuminate\Support\Facades\Auth::guard("portal")->user();

$result = [
    "php_session" => [
        "id" => session_id(),
        "data" => $_SESSION
    ],
    "laravel_session" => $laravelSession,
    "auth" => [
        "check" => (bool) $user,
        "user" => $user ? [
            "id" => $user->id,
            "email" => $user->email
        ] : null
    ],
    "cookies" => $_COOKIE,
    "config" => [
        "driver" => config("session.driver"),
        "cookie" => config("session.cookie"),
        "secure" => config("session.secure")
    ]
];

echo json_encode($result, JSON_PRETTY_PRINT);

$kernel->terminate($request, $response);
