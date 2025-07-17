<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FRESH Portal Test ===\n\n";

// 1. Clear ALL email activities for this call
$callId = 227;
echo "1. Clearing all email activities for call $callId:\n";
$deleted = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->delete();
echo "   Deleted $deleted activities\n\n";

// 2. Get call and setup
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
app()->instance('current_company_id', $call->company_id);

$portalUser = \App\Models\PortalUser::first();
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);

// 3. Monitor log
$logFile = storage_path('logs/laravel.log');
$initialSize = filesize($logFile);

// 4. Send via portal controller
echo "2. Sending via Portal Controller:\n";

$request = \Illuminate\Http\Request::create(
    "/api/business/calls/{$callId}/send-summary",
    'POST',
    [
        'recipients' => ['fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => false  // No CSV to make it simpler
    ]
);
$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

try {
    $response = $controller->sendSummary($request, $call);
    $responseData = json_decode($response->getContent(), true);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() == 200) {
        echo "   ✅ Success: " . ($responseData['message'] ?? 'OK') . "\n";
    } else {
        echo "   ❌ Error: " . json_encode($responseData) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// 5. Wait for processing
echo "\n3. Waiting for queue processing...\n";
for ($i = 0; $i < 10; $i++) {
    $redis = app('redis');
    $queueCount = $redis->llen("queues:default") + $redis->llen("queues:emails");
    echo "   " . ($i+1) . "s: $queueCount jobs in queue\n";
    if ($queueCount == 0) break;
    sleep(1);
}

// 6. Check logs
echo "\n4. Checking for ResendTransport logs:\n";
$newSize = filesize($logFile);
if ($newSize > $initialSize) {
    $newContent = file_get_contents($logFile, false, null, $initialSize);
    $lines = explode("\n", $newContent);
    
    $resendLogs = [];
    $errorLogs = [];
    
    foreach ($lines as $line) {
        if (str_contains($line, '[ResendTransport]')) {
            $resendLogs[] = $line;
        }
        if (str_contains($line, 'ERROR') || str_contains($line, 'Exception')) {
            $errorLogs[] = $line;
        }
    }
    
    if (count($resendLogs) > 0) {
        echo "   ✅ Found ResendTransport logs:\n";
        foreach ($resendLogs as $log) {
            echo "   " . $log . "\n";
        }
    } else {
        echo "   ❌ NO ResendTransport logs found\n";
    }
    
    if (count($errorLogs) > 0) {
        echo "\n   Error logs:\n";
        foreach ($errorLogs as $log) {
            echo "   " . substr($log, 0, 300) . "\n";
        }
    }
}

// 7. Check Resend dashboard
echo "\n5. Direct API check:\n";
$apiKey = config('services.resend.key');
$testId = 'portal-test-' . uniqid();

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
    'subject' => 'Direct API Test - ' . $testId,
    'html' => '<p>Test ID: ' . $testId . '</p><p>If this arrives but portal email does not, the issue is in Laravel Mail.</p>'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP $httpCode - ";
if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "Email ID: " . ($data['id'] ?? 'unknown') . "\n";
} else {
    echo "Error: $response\n";
}

echo "\n=== FINAL CHECK ===\n";
echo "1. Was email queued? Check step 2\n";
echo "2. Was queue processed? Check step 3\n";
echo "3. Was ResendTransport called? Check step 4\n";
echo "4. Any errors? Check step 4\n";
echo "5. Direct API works? Check step 5\n";