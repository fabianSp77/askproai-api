<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== REAL PORTAL EMAIL TEST ===\n\n";

// Clear log so we can see new entries
shell_exec("echo '' > /var/www/api-gateway/storage/logs/laravel.log");

$callId = 258;
$recipient = 'fabianspitzer@icloud.com';

echo "1. Simulating exact portal API call:\n";

// Create request as portal would
$request = new \Illuminate\Http\Request();
$request->setMethod('POST');
$request->merge([
    'recipients' => [$recipient],
    'include_transcript' => true,
    'include_csv' => true,
    'message' => 'Test vom neuen System - ' . now()->format('H:i:s')
]);

// Get portal user and set auth
$portalUser = \App\Models\PortalUser::first();
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);

// Get call with tenant scope disabled
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);

// Set company context
app()->instance('current_company_id', $call->company_id);

// Create controller instance
$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

try {
    // Call the sendSummary method directly
    echo "Calling sendSummary method...\n";
    $response = $controller->sendSummary($request, $call);
    
    $responseData = json_decode($response->getContent(), true);
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Check queue
echo "\n2. Checking queue:\n";
$redis = app('redis');
$queueSize = $redis->llen('queues:emails');
echo "Email queue size: $queueSize\n";

// Wait for processing
echo "\nWaiting 10 seconds for processing...\n";
sleep(10);

$finalSize = $redis->llen('queues:emails');
echo "Queue size after wait: $finalSize\n";

// Check logs
echo "\n3. Checking logs for ResendTransport:\n";
$logs = shell_exec("grep -i resend /var/www/api-gateway/storage/logs/laravel.log | tail -10");
if ($logs) {
    echo $logs;
} else {
    echo "No ResendTransport logs found\n";
}

echo "\n4. Checking for any errors:\n";
$errors = shell_exec("grep -i 'error\\|exception' /var/www/api-gateway/storage/logs/laravel.log | tail -10");
if ($errors) {
    echo $errors;
} else {
    echo "No errors found\n";
}

echo "\n5. Checking call activities:\n";
$activities = \App\Models\CallActivity::where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($activities as $activity) {
    echo "- " . $activity->created_at->format('H:i:s') . ": " . $activity->activity . "\n";
    if (isset($activity->metadata['recipients'])) {
        echo "  Recipients: " . implode(', ', $activity->metadata['recipients']) . "\n";
    }
}

echo "\n=== END TEST ===\n";
echo "Check your email at: $recipient\n";