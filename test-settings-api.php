<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PortalUser;
use App\Http\Controllers\Portal\Api\SettingsApiController;
use Illuminate\Http\Request;

echo "=== TEST SETTINGS API ===\n\n";

// Get a portal user
$user = PortalUser::first();

if (!$user) {
    echo "No portal users found.\n";
    exit(1);
}

echo "Testing with user: {$user->name} (ID: {$user->id})\n";
echo "Company ID: " . ($user->company_id ?: 'NULL') . "\n\n";

// Set the user in auth
auth()->guard('portal')->setUser($user);

// Test getCompany endpoint
try {
    echo "Testing getCompany()...\n";
    $controller = new SettingsApiController();
    $request = Request::create('/business/api/settings/company', 'GET');
    $response = $controller->getCompany($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n\n";
}

// Check if user has company relationship
if ($user->company_id) {
    $company = $user->company;
    echo "Company found: " . ($company ? $company->name : 'NULL (relationship broken)') . "\n";
} else {
    echo "User has no company_id set.\n";
}

echo "\n=== END TEST ===\n";