<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST Portal with Logging ===\n\n";

// 1. Clear logs
echo "1. Clearing log tail:\n";
$logFile = storage_path('logs/laravel.log');
$initialSize = filesize($logFile);
echo "   Log size: $initialSize bytes\n\n";

// 2. Simulate portal email
$callId = 227;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
app()->instance('current_company_id', $call->company_id);

// Login as portal user
$portalUser = \App\Models\PortalUser::first();
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);

echo "2. Sending email via Portal Controller:\n";

// Create request
$request = \Illuminate\Http\Request::create(
    "/api/business/calls/{$callId}/send-summary",
    'POST',
    [
        'recipients' => ['fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => true
    ]
);
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

// Call controller
$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

try {
    $response = $controller->sendSummary($request, $call);
    echo "   Response: " . $response->getStatusCode() . "\n";
    echo "   " . json_encode(json_decode($response->getContent(), true), JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 3. Wait for processing
echo "\n3. Waiting for queue processing...\n";
sleep(5);

// 4. Check logs
echo "\n4. Checking logs for ResendTransport:\n";
$newSize = filesize($logFile);
if ($newSize > $initialSize) {
    $newContent = file_get_contents($logFile, false, null, $initialSize);
    $lines = explode("\n", $newContent);
    
    $foundTransportLog = false;
    foreach ($lines as $line) {
        if (str_contains($line, '[ResendTransport]')) {
            echo "   " . $line . "\n";
            $foundTransportLog = true;
        }
    }
    
    if (!$foundTransportLog) {
        echo "   ❌ NO ResendTransport logs found!\n";
        echo "\n   All new log entries:\n";
        foreach ($lines as $line) {
            if (trim($line)) {
                echo "   " . substr($line, 0, 200) . "\n";
            }
        }
    }
} else {
    echo "   No new log entries\n";
}

// 5. Direct test to compare
echo "\n5. Direct email test for comparison:\n";
try {
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        false,
        'Direct Test - ' . now()->format('H:i:s'),
        'internal'
    ));
    echo "   ✅ Direct send completed\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Wait and check logs again
sleep(2);

echo "\n6. Checking logs after direct send:\n";
$finalSize = filesize($logFile);
if ($finalSize > $newSize) {
    $directContent = file_get_contents($logFile, false, null, $newSize);
    $lines = explode("\n", $directContent);
    
    foreach ($lines as $line) {
        if (str_contains($line, '[ResendTransport]')) {
            echo "   " . $line . "\n";
        }
    }
}

echo "\n=== ANALYSIS ===\n";
echo "If ResendTransport logs appear for direct send but not for portal:\n";
echo "→ The portal emails are not reaching the transport layer\n";
echo "→ They might be failing before that point\n";