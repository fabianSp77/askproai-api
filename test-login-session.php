<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Login with Session ===\n\n";

// Step 1: Get CSRF token
$getRequest = Illuminate\Http\Request::create('/business/login', 'GET');
$getRequest->setLaravelSession($app['session']->driver());
$getResponse = $kernel->handle($getRequest);

// Extract CSRF token from response
$content = $getResponse->getContent();
preg_match('/name="_token" value="([^"]+)"/', $content, $matches);
$csrfToken = $matches[1] ?? null;

echo "1. CSRF Token: " . ($csrfToken ? substr($csrfToken, 0, 20) . '...' : 'NOT FOUND') . "\n";
echo "   Session ID: " . $getRequest->session()->getId() . "\n";

// Step 2: Login with same session
$loginRequest = Illuminate\Http\Request::create(
    '/business/login',
    'POST',
    [
        '_token' => $csrfToken,
        'email' => 'demo@askproai.de',
        'password' => 'password'
    ]
);

// Use the same session from GET request
$loginRequest->setLaravelSession($getRequest->session());
$loginRequest->headers->set('Cookie', 'askproai_portal_session=' . $getRequest->session()->getId());

echo "\n2. Attempting login...\n";
$loginResponse = $kernel->handle($loginRequest);

echo "   Response Status: " . $loginResponse->getStatusCode() . "\n";
if ($loginResponse->getStatusCode() == 302) {
    echo "   Redirect to: " . $loginResponse->headers->get('Location') . "\n";
} else {
    $content = $loginResponse->getContent();
    if (strpos($content, 'Die angegebenen Zugangsdaten sind ungültig') !== false) {
        echo "   ❌ Invalid credentials error\n";
    } elseif ($loginResponse->getStatusCode() == 419) {
        echo "   ❌ CSRF token mismatch\n";
    }
}

// Check session after login
echo "\n3. Session check:\n";
echo "   Auth check: " . (auth()->guard('portal')->check() ? 'YES' : 'NO') . "\n";
if (auth()->guard('portal')->check()) {
    echo "   User: " . auth()->guard('portal')->user()->email . "\n";
}

$kernel->terminate($getRequest, $getResponse);
$kernel->terminate($loginRequest, $loginResponse);