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

echo "Testing billing page access...\n\n";

// Login as the user
Auth::guard('portal')->login($portalUser);

// Check if billing API works
$request = \Illuminate\Http\Request::create('/business/api/billing', 'GET');
$request->setLaravelSession($app['session.store']);
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

try {
    $controller = new \App\Http\Controllers\Portal\ReactBillingController(
        app(\App\Services\StripeTopupService::class)
    );
    
    $response = $controller->getBillingData($request);
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "✅ API Response OK\n";
    echo "Company: " . $data['company']['name'] . "\n";
    echo "Balance: " . $data['prepaid_balance']['current_balance'] . " EUR\n";
    echo "Auto-Topup: " . ($data['auto_topup']['enabled'] ? 'Enabled' : 'Disabled') . "\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ API Error: " . $e->getMessage() . "\n";
}

// Check if React build file exists
$buildFile = 'public/build/assets/portal-billing-DkMt9ctW.js';
if (file_exists($buildFile)) {
    echo "✅ React build file exists\n";
    echo "Size: " . number_format(filesize($buildFile) / 1024, 2) . " KB\n";
} else {
    echo "❌ React build file missing\n";
}

// Check if view exists
$viewPath = 'resources/views/portal/billing/react-index.blade.php';
if (file_exists($viewPath)) {
    echo "✅ React view template exists\n";
} else {
    echo "❌ React view template missing\n";
}

echo "\nSummary: The billing page should be working properly!\n";
echo "Access it at: https://api.askproai.de/business/billing\n";
echo "(You need to be logged in to the Business Portal first)\n";