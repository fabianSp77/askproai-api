<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Boot the app
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;

// Find a portal user
$portalUser = PortalUser::first();

if (!$portalUser) {
    echo "No portal users found.\n";
    exit(1);
}

// Login as the user
Auth::guard('portal')->login($portalUser);

// Create a request
$request = \Illuminate\Http\Request::create('/business/api/billing', 'GET');
$request->setLaravelSession($app['session.store']);
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

// Test the API endpoint
try {
    $controller = new \App\Http\Controllers\Portal\ReactBillingController(
        app(\App\Services\StripeTopupService::class)
    );
    
    $response = $controller->getBillingData($request);
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "API Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    
    if (isset($data['prepaid_balance'])) {
        echo "\n✅ API is working correctly!\n";
    } else {
        echo "\n❌ API response format is incorrect\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}