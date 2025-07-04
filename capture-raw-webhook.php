<?php
// Simple webhook capture script
file_put_contents(
    '/var/www/api-gateway/storage/logs/webhook-capture.log', 
    date('Y-m-d H:i:s') . "\n" .
    "Headers:\n" . print_r(getallheaders(), true) . "\n" .
    "Body:\n" . file_get_contents('php://input') . "\n" .
    "POST:\n" . print_r($_POST, true) . "\n" .
    "---\n\n",
    FILE_APPEND
);

// Return success to avoid retries
header('Content-Type: application/json');
echo json_encode(['success' => true, 'captured' => true]);