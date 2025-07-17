<?php
// Test Calls API Authentication Issue

require_once 'vendor/autoload.php';
use App\Models\PortalUser;
use App\Models\Call;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Set up the request
$request = Illuminate\Http\Request::create('/business/api/calls', 'GET');

// Set session manually for testing
session()->start();
session(['portal_user_id' => 23]); // Demo user

// Create a response
$response = $kernel->handle($request);

echo "=== Testing Calls API Authentication ===\n\n";

echo "1. Request URL: /business/api/calls\n";
echo "2. Session ID: " . session()->getId() . "\n";
echo "3. Portal User ID in Session: " . session('portal_user_id') . "\n\n";

echo "4. Response Status: " . $response->getStatusCode() . "\n";
echo "5. Response Headers:\n";
foreach ($response->headers->all() as $key => $value) {
    echo "   - $key: " . implode(', ', $value) . "\n";
}

echo "\n6. Response Content:\n";
$content = json_decode($response->getContent(), true);
if ($content) {
    echo json_encode($content, JSON_PRETTY_PRINT) . "\n";
} else {
    echo substr($response->getContent(), 0, 500) . "\n";
}

// Check if we can access the data directly
echo "\n\n=== Direct Database Query ===\n";
$user = PortalUser::find(23);
if ($user) {
    echo "User found: {$user->email} (Company: {$user->company_id})\n";
    
    // Set company context
    app()->instance('current_company_id', $user->company_id);
    
    // Get calls
    $calls = Call::withoutGlobalScopes()
        ->where('company_id', $user->company_id)
        ->latest()
        ->limit(5)
        ->get();
    
    echo "Recent calls for company {$user->company_id}: " . $calls->count() . "\n";
    foreach ($calls as $call) {
        echo "  - Call #{$call->id}: {$call->from_number} ({$call->status})\n";
    }
} else {
    echo "User not found!\n";
}

// Test with manual authentication
echo "\n\n=== Manual Authentication Test ===\n";
if ($user) {
    \Illuminate\Support\Facades\Auth::guard('portal')->login($user);
    
    $request2 = Illuminate\Http\Request::create('/business/api/calls', 'GET');
    $response2 = $kernel->handle($request2);
    
    echo "Response after manual login: " . $response2->getStatusCode() . "\n";
    if ($response2->getStatusCode() === 200) {
        $data = json_decode($response2->getContent(), true);
        echo "Calls returned: " . count($data['data'] ?? []) . "\n";
    }
}