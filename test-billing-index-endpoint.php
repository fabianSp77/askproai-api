<?php
// Test billing index endpoint directly

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$kernel->terminate($request, $response);

echo "=== Testing Billing Index Endpoint ===\n\n";

// Get authenticated user
$user = \App\Models\PortalUser::find(23);
if (!$user) {
    die("User not found\n");
}

echo "User: {$user->email} (ID: {$user->id})\n";
echo "Company: " . ($user->company ? $user->company->name : 'None') . "\n\n";

// Set authentication
\Illuminate\Support\Facades\Auth::guard('portal')->login($user);
app()->instance('current_company_id', $user->company_id);

// Test the index endpoint
echo "Testing BillingApiController::index()...\n";

try {
    $controller = app()->make(\App\Http\Controllers\Portal\Api\BillingApiController::class);
    $request = \Illuminate\Http\Request::create('/business/api/billing', 'GET');
    
    // Call the method
    $response = $controller->index($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response: " . $response->getContent() . "\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}