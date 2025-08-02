<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;

// Boot the app
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Try to find a portal user for testing
$portalUser = PortalUser::first();

if (!$portalUser) {
    echo "No portal users found in database.\n";
    exit(1);
}

echo "Testing with user: {$portalUser->email}\n";

// Login as the user
Auth::guard('portal')->login($portalUser);

// Create a request with session
$request = \Illuminate\Http\Request::create('/business/billing', 'GET');
$request->setLaravelSession($app['session.store']);

// Add the authenticated user to the request
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

try {
    // Call the controller directly
    $controller = new \App\Http\Controllers\Portal\SimpleBillingController();
    $response = $controller->index($request);
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        echo "Redirecting to: " . $response->getTargetUrl() . "\n";
    } elseif ($response instanceof \Illuminate\View\View) {
        echo "View rendered successfully!\n";
        echo "View name: " . $response->getName() . "\n";
    } else {
        echo "Response type: " . get_class($response) . "\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}