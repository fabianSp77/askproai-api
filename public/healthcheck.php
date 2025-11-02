<?php
/**
 * Standalone Health Check Endpoint
 *
 * Purpose: Provides health status for CI/CD deployment gates
 * Security: Bearer token authentication (reads from .env)
 *
 * SECURITY FIX (2025-11-02):
 * - Now reads HEALTHCHECK_TOKEN from .env file instead of hardcoded value
 * - Supports environment-specific token rotation
 * - Prevents secret exposure in version control
 */

// Load environment variables from .env file
// (Standalone PHP without Laravel bootstrap)
$envPath = dirname(__DIR__) . '/.env';
$expectedToken = '';

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if ($key === 'HEALTHCHECK_TOKEN') {
                $expectedToken = $value;
                break;
            }
        }
    }
}

header('Content-Type: application/json');

// Check Bearer token authentication
$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';

// Verify token exists and matches (timing-safe comparison)
if ($expectedToken && hash_equals('Bearer ' . $expectedToken, $auth)) {
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
