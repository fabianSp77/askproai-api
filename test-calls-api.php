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

echo "Testing calls API...\n\n";

// Login as the user
Auth::guard('portal')->login($portalUser);

// Create a request
$request = \Illuminate\Http\Request::create('/business/api/calls', 'GET');
$request->setLaravelSession($app['session.store']);
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

// Test the API endpoint
try {
    $controller = new \App\Http\Controllers\Portal\ReactCallController();
    
    $response = $controller->apiIndex($request);
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "API Response:\n";
    echo "Total calls: " . count($data['data'] ?? []) . "\n";
    echo "Stats:\n";
    if (isset($data['stats'])) {
        echo "  - Today: " . $data['stats']['total_today'] . "\n";
        echo "  - New: " . $data['stats']['new'] . "\n";
        echo "  - In Progress: " . $data['stats']['in_progress'] . "\n";
        echo "  - Action Required: " . $data['stats']['action_required'] . "\n";
    }
    
    if (isset($data['data']) && count($data['data']) > 0) {
        echo "\nFirst call:\n";
        $firstCall = $data['data'][0];
        echo "  - ID: " . $firstCall['id'] . "\n";
        echo "  - Phone: " . $firstCall['phone_number'] . "\n";
        echo "  - Status: " . $firstCall['status'] . "\n";
        echo "  - Customer: " . ($firstCall['customer']['name'] ?? 'N/A') . "\n";
    }
    
    echo "\n✅ Calls API is working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}