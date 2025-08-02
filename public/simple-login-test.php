<?php
session_start();

// Simple session test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['test_user'] = 'admin@test.com';
    $_SESSION['logged_in'] = true;
    echo json_encode([
        'status' => 'logged_in',
        'session_id' => session_id(),
        'session_data' => $_SESSION
    ]);
    exit;
}

// Check session
echo json_encode([
    'status' => 'checking',
    'session_id' => session_id(),
    'logged_in' => isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : false,
    'session_data' => $_SESSION
]);