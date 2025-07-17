<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SIMULATE EXACT Portal API Call ===\n\n";

// 1. Login as portal user to get proper auth
echo "1. Setting up Portal Authentication:\n";
$portalUser = \App\Models\PortalUser::where('email', 'admin@askpro-ai.com')->first();
if (!$portalUser) {
    $portalUser = \App\Models\PortalUser::first();
}

if ($portalUser) {
    \Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);
    echo "   ✅ Logged in as: " . $portalUser->name . " (" . $portalUser->email . ")\n";
} else {
    echo "   ❌ No portal user found!\n";
    exit(1);
}

// 2. Prepare the EXACT request data that the portal sends
$callId = 227;
$requestData = [
    'recipients' => ['fabianspitzer@icloud.com'],
    'subject' => null, // Portal often sends null
    'message' => null,
    'include_recording' => false,
    'include_transcript' => true,
    'include_csv' => true  // This is what portal sends
];

echo "\n2. Request Data (exactly as portal sends):\n";
echo json_encode($requestData, JSON_PRETTY_PRINT) . "\n";

// 3. Create request object
$request = \Illuminate\Http\Request::create(
    "/api/business/calls/{$callId}/send-summary",
    'POST',
    $requestData,
    [], // cookies
    [], // files
    ['HTTP_ACCEPT' => 'application/json']
);

// Set the guard
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

// 4. Get the controller
echo "\n3. Calling Controller Method:\n";
$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

// Get the call
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);

// Monitor before
$redis = app('redis');
$beforeDefault = $redis->llen("queues:default");
$beforeEmails = $redis->llen("queues:emails");

echo "   Queue before: default=$beforeDefault, emails=$beforeEmails\n";

// 5. Call the EXACT method
try {
    $response = $controller->sendSummary($request, $call);
    $responseData = json_decode($response->getContent(), true);
    
    echo "\n4. Controller Response:\n";
    echo "   Status: " . $response->getStatusCode() . "\n";
    echo "   Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "\n   ❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

// 6. Check queue after
$afterDefault = $redis->llen("queues:default");
$afterEmails = $redis->llen("queues:emails");

echo "\n5. Queue after: default=$afterDefault, emails=$afterEmails\n";

if ($afterDefault > $beforeDefault || $afterEmails > $beforeEmails) {
    echo "   ✅ Job was queued!\n";
    
    // Get job details
    $queue = $afterDefault > $beforeDefault ? 'default' : 'emails';
    $jobData = $redis->lindex("queues:{$queue}", -1);
    if ($jobData) {
        $job = json_decode($jobData, true);
        echo "   Job ID: " . ($job['uuid'] ?? 'N/A') . "\n";
        
        // Wait for processing
        echo "\n6. Waiting for processing...\n";
        sleep(3);
        
        $finalCount = $redis->llen("queues:{$queue}");
        if ($finalCount < $redis->llen("queues:{$queue}")) {
            echo "   ✅ Job was processed\n";
        } else {
            echo "   ⚠️ Job still in queue\n";
        }
    }
} else {
    echo "   ❌ NO JOB WAS QUEUED!\n";
}

// 7. Check activity log
echo "\n7. Checking Activity Log:\n";
$activity = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->orderBy('created_at', 'desc')
    ->first();

if ($activity) {
    echo "   ✅ Activity logged: " . $activity->description . "\n";
    echo "   Metadata: " . json_encode($activity->metadata) . "\n";
} else {
    echo "   ❌ No activity logged\n";
}

// 8. Direct check with Resend
echo "\n8. Checking with Resend API:\n";
$apiKey = config('services.resend.key');

// Send a test email directly to compare
$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'from' => config('mail.from.address'),
    'to' => ['fabianspitzer@icloud.com'],
    'subject' => 'Direct Test After Portal Simulation - ' . now()->format('H:i:s'),
    'html' => '<p>This is sent directly to verify Resend works.</p>'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Direct test: HTTP $httpCode\n";

echo "\n=== ANALYSIS ===\n";
echo "This simulates EXACTLY what happens when you click the button in the portal.\n";
echo "Check the results above to see where it fails.\n";