<?php
// Test authentication status for business portal

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Start session
session_start();

echo "=== Business Portal Auth Status ===\n\n";

// Check all session data
echo "Session ID: " . session_id() . "\n";
echo "Session data:\n";
print_r($_SESSION);

// Check Laravel auth
$app->make('auth')->shouldUse('portal');
$portalUser = $app->make('auth')->guard('portal')->user();

echo "\nPortal Guard User: ";
if ($portalUser) {
    echo "✅ Authenticated\n";
    echo "User ID: " . $portalUser->id . "\n";
    echo "Name: " . $portalUser->name . "\n";
    echo "Email: " . $portalUser->email . "\n";
    echo "Company ID: " . $portalUser->company_id . "\n";
} else {
    echo "❌ Not authenticated\n";
}

// Check portal session key
$portalSessionKey = 'login_portal_' . sha1('Illuminate\Auth\SessionGuard.portal');
echo "\nPortal session key: $portalSessionKey\n";
echo "Portal session value: " . (session($portalSessionKey) ?? 'Not set') . "\n";

// Check if there's a demo user
$demoUser = \App\Models\PortalUser::where('email', 'demo@business.portal')->first();
if ($demoUser) {
    echo "\n✅ Demo user exists:\n";
    echo "ID: " . $demoUser->id . "\n";
    echo "Email: " . $demoUser->email . "\n";
    echo "Active: " . ($demoUser->is_active ? 'Yes' : 'No') . "\n";
    
    // Try to login
    echo "\nAttempting to login demo user...\n";
    $app->make('auth')->guard('portal')->login($demoUser);
    
    // Set session
    session([$portalSessionKey => $demoUser->id]);
    session(['portal_user_id' => $demoUser->id]);
    session()->save();
    
    echo "Login result: " . ($app->make('auth')->guard('portal')->check() ? '✅ Success' : '❌ Failed') . "\n";
} else {
    echo "\n❌ Demo user not found\n";
}

// Test route access
echo "\n=== Testing Route Access ===\n";
$routes = [
    '/business' => 'Main page',
    '/business/login' => 'Login page',
    '/business/dashboard' => 'Dashboard (React)',
];

foreach ($routes as $route => $desc) {
    $testRequest = \Illuminate\Http\Request::create($route, 'GET');
    $testRequest->setLaravelSession(session());
    
    try {
        $testResponse = $app->handle($testRequest);
        echo "$desc ($route): HTTP " . $testResponse->getStatusCode() . "\n";
        
        if ($testResponse->getStatusCode() == 302) {
            echo "  → Redirects to: " . $testResponse->headers->get('Location') . "\n";
        }
    } catch (\Exception $e) {
        echo "$desc ($route): ERROR - " . $e->getMessage() . "\n";
    }
}