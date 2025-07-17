<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST Fixed Portal Email ===\n\n";

// 1. Clear activities
$callId = 228; // Use different call to avoid duplicate protection
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->delete();

// 2. Setup
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
    
if (!$call) {
    echo "Call $callId not found!\n";
    exit(1);
}

app()->instance('current_company_id', $call->company_id);

$portalUser = \App\Models\PortalUser::first();
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);

// 3. Test 1: Queue email (as portal does)
echo "1. Testing QUEUE email (as portal does):\n";

try {
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue(new \App\Mail\CallSummaryEmail(
        $call,
        true,  // transcript
        false, // no CSV for now
        'FIXED Queue Test - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "   ✅ Queued successfully\n";
    
    // Process queue
    echo "\n2. Processing queue:\n";
    $output = shell_exec('php artisan queue:work --once --queue=default 2>&1');
    echo $output . "\n";
    
    // Check if processed
    $redis = app('redis');
    $remaining = $redis->llen("queues:default");
    if ($remaining == 0) {
        echo "   ✅ Queue processed\n";
    } else {
        echo "   ⚠️ Still $remaining jobs in queue\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 4. Check failed jobs
echo "\n3. Checking for failed jobs:\n";
$failed = \DB::table('failed_jobs')
    ->where('failed_at', '>', now()->subMinute())
    ->orderBy('failed_at', 'desc')
    ->first();

if ($failed) {
    echo "   ❌ FAILED JOB FOUND!\n";
    $payload = json_decode($failed->payload, true);
    echo "   Job: " . ($payload['displayName'] ?? 'Unknown') . "\n";
    echo "   Exception: " . substr($failed->exception, 0, 500) . "\n";
} else {
    echo "   ✅ No failed jobs\n";
}

// 5. Check logs for ResendTransport
echo "\n4. Checking for ResendTransport logs:\n";
$log = file_get_contents(storage_path('logs/laravel.log'));
$lines = explode("\n", $log);
$recentLines = array_slice($lines, -20);

$found = false;
foreach ($recentLines as $line) {
    if (str_contains($line, '[ResendTransport]')) {
        echo "   " . $line . "\n";
        $found = true;
    }
}

if (!$found) {
    echo "   ❌ No ResendTransport logs found\n";
}

// 6. Test 2: Direct send (for comparison)
echo "\n5. Testing DIRECT send (for comparison):\n";

try {
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        false,
        'FIXED Direct Test - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "   ✅ Direct send completed\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== RESULT ===\n";
echo "If QUEUE now works, the fix was successful!\n";
echo "Check your email for 'FIXED Queue Test'\n";