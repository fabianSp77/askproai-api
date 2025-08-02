#!/usr/bin/env php
<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test 1: GET login page
echo "=== TEST 1: GET Login Page ===\n";
$getRequest = Illuminate\Http\Request::create('/business/login', 'GET');
$getResponse = $kernel->handle($getRequest);
echo "Status: " . $getResponse->getStatusCode() . "\n";

// Extract CSRF token
$content = $getResponse->getContent();
preg_match('/name="csrf-token" content="([^"]+)"/', $content, $matches);
$csrfToken = $matches[1] ?? null;
echo "CSRF Token found: " . ($csrfToken ? 'Yes' : 'No') . "\n";

// Extract session cookie
$cookies = $getResponse->headers->getCookies();
$sessionCookie = null;
foreach ($cookies as $cookie) {
    if ($cookie->getName() === 'askproai_portal_session') {
        $sessionCookie = $cookie->getValue();
        break;
    }
}
echo "Session Cookie found: " . ($sessionCookie ? 'Yes' : 'No') . "\n\n";

// Test 2: POST login attempt
echo "=== TEST 2: POST Login ===\n";
$postRequest = Illuminate\Http\Request::create('/business/login', 'POST', [
    'email' => 'demo@askproai.de',
    'password' => 'password123',
    '_token' => $csrfToken,
]);

// Set cookies and headers
if ($sessionCookie) {
    $postRequest->cookies->set('askproai_portal_session', $sessionCookie);
}
$postRequest->headers->set('X-CSRF-TOKEN', $csrfToken);
$postRequest->headers->set('X-Requested-With', 'XMLHttpRequest');
$postRequest->headers->set('Accept', 'application/json');

// Handle request
$postResponse = $kernel->handle($postRequest);
echo "Status: " . $postResponse->getStatusCode() . "\n";
echo "Response: " . substr($postResponse->getContent(), 0, 500) . "\n";

// Check for errors
if ($postResponse->getStatusCode() >= 400) {
    echo "\nFull Response:\n";
    echo $postResponse->getContent() . "\n";
}

$kernel->terminate($postRequest, $postResponse);