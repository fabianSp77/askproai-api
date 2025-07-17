<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FINAL TEST Portal Email ===\n\n";

// 1. Clear activities
$callId = 227;
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->delete();

// 2. Setup
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
app()->instance('current_company_id', $call->company_id);

$portalUser = \App\Models\PortalUser::first();
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);

// 3. Clear Redis queues
$redis = app('redis');
$redis->del("queues:default");
$redis->del("queues:emails");

// 4. Send via Portal Controller (exactly as user does)
echo "1. Sending via Portal Controller:\n";

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

$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

try {
    $response = $controller->sendSummary($request, $call);
    $responseData = json_decode($response->getContent(), true);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    echo "   Message: " . ($responseData['message'] ?? 'N/A') . "\n";
    
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Monitor queue processing
echo "\n2. Monitoring queue processing:\n";

for ($i = 0; $i < 10; $i++) {
    $queueCount = $redis->llen("queues:default") + $redis->llen("queues:emails");
    echo "   " . ($i+1) . "s: $queueCount jobs in queue\n";
    
    if ($queueCount == 0 && $i > 0) {
        echo "   ✅ Queue processed!\n";
        break;
    }
    
    sleep(1);
}

// 6. Check logs
echo "\n3. Checking for ResendTransport logs:\n";
$log = file_get_contents(storage_path('logs/laravel.log'));
$lines = explode("\n", $log);
$recentLines = array_slice($lines, -30);

$foundTransport = false;
foreach ($recentLines as $line) {
    if (str_contains($line, '[ResendTransport]')) {
        echo "   " . $line . "\n";
        $foundTransport = true;
    }
}

if ($foundTransport) {
    echo "\n   ✅ ResendTransport was called!\n";
} else {
    echo "   ❌ No ResendTransport logs\n";
}

// 7. Check failed jobs
echo "\n4. Checking for failed jobs:\n";
$failed = \DB::table('failed_jobs')
    ->where('failed_at', '>', now()->subMinutes(2))
    ->first();

if ($failed) {
    echo "   ❌ Failed job found!\n";
    $payload = json_decode($failed->payload, true);
    echo "   Job: " . ($payload['displayName'] ?? 'Unknown') . "\n";
    echo "   Exception: " . substr($failed->exception, 0, 300) . "\n";
} else {
    echo "   ✅ No failed jobs\n";
}

echo "\n=== FINAL RESULT ===\n";
echo "This test simulates EXACTLY what happens when you click the button.\n";
echo "If ResendTransport logs appear, the fix worked!\n";
echo "Check your email for the message.\n";