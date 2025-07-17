<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\PortalUser;
use App\Http\Controllers\Portal\Api\CallApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Get a recent call
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->orderBy('created_at', 'desc')
    ->first();

if (!$call) {
    echo "No calls found.\n";
    exit(1);
}

// Get a portal user for the same company
$user = PortalUser::where('company_id', $call->company_id)->first();

if (!$user) {
    echo "No portal user found for company.\n";
    exit(1);
}

// Simulate authentication
Auth::guard('portal')->setUser($user);

echo "Testing send summary for call ID: {$call->id}\n";
echo "Company ID: {$call->company_id}\n";
echo "User: {$user->name} ({$user->email})\n\n";

try {
    // Create request
    $request = Request::create('/api/calls/' . $call->id . '/send-summary', 'POST', [
        'recipients' => ['test@example.com'],
        'include_transcript' => true,
        'include_recording' => false,
        'message' => 'Test summary email'
    ]);
    
    // Create controller instance
    $controller = new CallApiController();
    
    // Call the method
    $response = $controller->sendSummary($request, $call);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . $response->getContent() . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}