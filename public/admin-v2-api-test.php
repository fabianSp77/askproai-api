<?php

// Direct API test without Laravel routing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$raw = file_get_contents('php://input');
// Handle escaped characters in JSON
$raw = stripslashes($raw);
$input = json_decode($raw, true);

// Debug output
if (empty($input) && !is_array($input)) {
    echo json_encode([
        'error' => 'Failed to parse JSON',
        'raw' => $raw,
        'json_error' => json_last_error_msg()
    ]);
    exit;
}

// Simulate login check
if (isset($input['email']) && isset($input['password'])) {
    if ($input['email'] === 'admin@askproai.de' && $input['password'] === 'TestPass123!') {
        echo json_encode([
            'success' => true,
            'message' => 'Direct PHP login test successful',
            'token' => bin2hex(random_bytes(32)),
            'note' => 'This bypasses Laravel completely'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials'
        ]);
    }
} else {
    echo json_encode([
        'error' => 'Missing email or password'
    ]);
}