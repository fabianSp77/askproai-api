<?php
header('Content-Type: application/json');

// Check Bearer token
$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';
$expectedToken = 'PewleIAhHC8IliNYhuKYG8W9iMMdrz0Ed3If6kCIMH0=';

if ($auth === 'Bearer ' . $expectedToken) {
    http_response_code(200);
    echo json_encode([
        'status' => 'healthy',
        'service' => 'staging',
        'timestamp' => time()
    ]);
} else {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
}
