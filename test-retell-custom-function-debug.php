<?php

// Test endpoint to debug what Retell sends to custom functions

header('Content-Type: application/json');

// Log all incoming data
$logFile = '/tmp/retell_custom_function_debug.log';
$timestamp = date('Y-m-d H:i:s');

// Get all request data
$requestData = [
    'timestamp' => $timestamp,
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    'headers' => getallheaders(),
    'get_params' => $_GET,
    'post_data' => $_POST,
    'raw_body' => file_get_contents('php://input'),
    'parsed_body' => json_decode(file_get_contents('php://input'), true)
];

// Write to log file
file_put_contents($logFile, "\n\n=== REQUEST AT $timestamp ===\n" . json_encode($requestData, JSON_PRETTY_PRINT), FILE_APPEND);

// Parse the request
$data = json_decode(file_get_contents('php://input'), true);
$args = $data['args'] ?? $data;

// Try to find phone number in various places
$phoneNumber = null;
$phoneSources = [];

// Check different possible locations
if (!empty($args['telefonnummer'])) {
    $phoneSources[] = "args.telefonnummer: " . $args['telefonnummer'];
}
if (!empty($data['call']['from_number'])) {
    $phoneSources[] = "call.from_number: " . $data['call']['from_number'];
}
if (!empty($data['from_number'])) {
    $phoneSources[] = "from_number: " . $data['from_number'];
}
if (!empty($data['caller_number'])) {
    $phoneSources[] = "caller_number: " . $data['caller_number'];
}

// Return debug response
$response = [
    'success' => true,
    'message' => 'Debug: Telefonnummer-Quellen gefunden',
    'phone_sources' => $phoneSources,
    'all_data_keys' => array_keys($data ?? []),
    'args_keys' => array_keys($args ?? []),
    'debug_info' => [
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
        'has_call_object' => isset($data['call']),
        'log_file' => $logFile
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);

// Also save response to log
file_put_contents($logFile, "\n\nRESPONSE:\n" . json_encode($response, JSON_PRETTY_PRINT), FILE_APPEND);