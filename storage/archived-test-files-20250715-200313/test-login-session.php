<?php

// Direct session test
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

$response = [
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_name' => session_name(),
    'session_data' => $_SESSION ?? [],
    'cookies' => $_COOKIE ?? [],
    'server' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
        'HTTP_COOKIE' => $_SERVER['HTTP_COOKIE'] ?? null,
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);