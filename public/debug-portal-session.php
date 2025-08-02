<?php

require_once __DIR__."/../vendor/autoload.php";
$app = require_once __DIR__."/../bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Handle the request
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Output session debug info
header("Content-Type: application/json");

$sessionId = session()->getId();
$sessionConfig = [
    "driver" => config("session.driver"),
    "cookie" => config("session.cookie"),
    "path" => config("session.path"),
    "domain" => config("session.domain"),
    "secure" => config("session.secure"),
    "same_site" => config("session.same_site"),
    "files" => config("session.files"),
];

$portalUser = auth()->guard("portal")->user();
$sessionData = session()->all();

// Check cookies
$cookies = [];
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, "session") \!== false || strpos($name, "XSRF") \!== false) {
        $cookies[$name] = substr($value, 0, 20) . "...";
    }
}

echo json_encode([
    "session_id" => $sessionId,
    "session_config" => $sessionConfig,
    "portal_user" => $portalUser ? [
        "id" => $portalUser->id,
        "email" => $portalUser->email,
        "company_id" => $portalUser->company_id,
    ] : null,
    "session_keys" => array_keys($sessionData),
    "cookies" => $cookies,
    "request_info" => [
        "url" => $request->url(),
        "method" => $request->method(),
        "headers" => [
            "Cookie" => $request->header("Cookie"),
        ],
    ],
], JSON_PRETTY_PRINT);

$kernel->terminate($request, $response);
