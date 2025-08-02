<?php
// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use Illuminate\Http\Request;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$response = $kernel->handle($request);

header('Content-Type: application/json');

// Get session configuration
$sessionConfig = config('session');

// Check cookie parameters
$cookieParams = session_get_cookie_params();

// Get all cookies
$cookies = $_COOKIE;

// Check if we're on HTTPS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;

// Get Laravel's session cookie name
$sessionCookieName = $sessionConfig['cookie'];
$sessionCookieValue = $_COOKIE[$sessionCookieName] ?? null;

// Check session file if using file driver
$sessionFileInfo = null;
if ($sessionConfig['driver'] === 'file' && $sessionCookieValue) {
    $sessionPath = storage_path('framework/sessions/' . $sessionCookieValue);
    if (file_exists($sessionPath)) {
        $sessionFileInfo = [
            'exists' => true,
            'readable' => is_readable($sessionPath),
            'writable' => is_writable($sessionPath),
            'size' => filesize($sessionPath),
            'owner' => posix_getpwuid(fileowner($sessionPath))['name'] ?? 'unknown',
            'permissions' => substr(sprintf('%o', fileperms($sessionPath)), -4),
            'modified' => date('Y-m-d H:i:s', filemtime($sessionPath))
        ];
    } else {
        $sessionFileInfo = ['exists' => false];
    }
}

// Output debug information
echo json_encode([
    'environment' => [
        'is_https' => $isHttps,
        'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'unknown',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
        'request_scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'unknown'
    ],
    'session_config' => [
        'driver' => $sessionConfig['driver'],
        'cookie_name' => $sessionConfig['cookie'],
        'domain' => $sessionConfig['domain'],
        'path' => $sessionConfig['path'],
        'secure' => $sessionConfig['secure'],
        'http_only' => $sessionConfig['http_only'],
        'same_site' => $sessionConfig['same_site'],
        'encrypt' => $sessionConfig['encrypt'],
        'lifetime' => $sessionConfig['lifetime']
    ],
    'env_values' => [
        'SESSION_SECURE_COOKIE' => env('SESSION_SECURE_COOKIE'),
        'SESSION_DOMAIN' => env('SESSION_DOMAIN'),
        'SESSION_SAME_SITE' => env('SESSION_SAME_SITE'),
        'SESSION_ENCRYPT' => env('SESSION_ENCRYPT')
    ],
    'php_cookie_params' => $cookieParams,
    'cookies' => array_map(function($value) {
        return substr($value, 0, 20) . '...';
    }, $cookies),
    'session_cookie' => [
        'name' => $sessionCookieName,
        'exists' => isset($_COOKIE[$sessionCookieName]),
        'value' => $sessionCookieValue ? substr($sessionCookieValue, 0, 20) . '...' : null
    ],
    'session_file' => $sessionFileInfo,
    'potential_issues' => [
        'secure_mismatch' => $sessionConfig['secure'] && !$isHttps ? 
            'CRITICAL: Secure cookie on non-HTTPS will fail!' : 'OK',
        'domain_issue' => !empty($sessionConfig['domain']) ? 
            'Domain set to: ' . $sessionConfig['domain'] : 'No domain restriction',
        'env_config_mismatch' => env('SESSION_SECURE_COOKIE') !== null && 
            env('SESSION_SECURE_COOKIE') != $sessionConfig['secure'] ? 
            'ENV and config mismatch!' : 'OK'
    ]
], JSON_PRETTY_PRINT);

$kernel->terminate($request, $response);