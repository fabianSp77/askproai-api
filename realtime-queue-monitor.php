<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== REALTIME Queue Monitor ===\n\n";

// 1. Setup
$callId = 227;
$recipient = 'fabianspitzer@icloud.com';
$redis = app('redis');

// Login as portal user
$portalUser = \App\Models\PortalUser::first();
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);

// Get call
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
app()->instance('current_company_id', $call->company_id);

// 2. Clear any existing jobs
echo "1. Clearing existing jobs:\n";
$redis->del("queues:default");
$redis->del("queues:emails");
echo "   ✅ Queues cleared\n\n";

// 3. Monitor in real-time
echo "2. Creating email job:\n";

// Create a monitoring thread (simulated with rapid checks)
$startTime = microtime(true);

try {
    // Queue the email
    \Illuminate\Support\Facades\Mail::to($recipient)->queue(new \App\Mail\CallSummaryEmail(
        $call,
        true,  // include_transcript
        true,  // include_csv
        'Realtime Monitor Test - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "   ✅ Mail::queue() called at " . date('H:i:s.u') . "\n";
    
    // Rapid monitoring for 10 seconds
    $checks = 0;
    $jobFound = false;
    $jobProcessed = false;
    $jobData = null;
    
    while ((microtime(true) - $startTime) < 10 && !$jobProcessed) {
        $checks++;
        
        // Check queues
        $defaultCount = $redis->llen("queues:default");
        $emailsCount = $redis->llen("queues:emails");
        
        if (($defaultCount > 0 || $emailsCount > 0) && !$jobFound) {
            $jobFound = true;
            $queue = $defaultCount > 0 ? 'default' : 'emails';
            $jobData = $redis->lindex("queues:{$queue}", 0);
            $job = json_decode($jobData, true);
            
            echo "\n3. JOB FOUND in '$queue' queue at " . date('H:i:s.u') . ":\n";
            echo "   Job ID: " . ($job['uuid'] ?? 'N/A') . "\n";
            echo "   Delay: " . round((microtime(true) - $startTime) * 1000, 2) . "ms\n";
        }
        
        if ($jobFound && $defaultCount == 0 && $emailsCount == 0) {
            $jobProcessed = true;
            echo "\n4. JOB PROCESSED at " . date('H:i:s.u') . ":\n";
            echo "   Processing time: " . round((microtime(true) - $startTime) * 1000, 2) . "ms\n";
        }
        
        usleep(100000); // 100ms
    }
    
    if (!$jobFound) {
        echo "\n❌ NO JOB WAS CREATED!\n";
    } elseif (!$jobProcessed) {
        echo "\n❌ JOB IS STUCK IN QUEUE!\n";
        
        // Try to get error details
        if ($jobData) {
            echo "\n5. Attempting manual processing:\n";
            $output = shell_exec('php artisan queue:work --once --queue=default,emails --stop-when-empty 2>&1');
            echo $output . "\n";
            
            // Check if still there
            if ($redis->llen("queues:default") > 0 || $redis->llen("queues:emails") > 0) {
                echo "\n❌ Job STILL stuck after manual processing!\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ Exception: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 4. Check failed jobs
echo "\n6. Checking failed_jobs table:\n";
$recentFailed = \DB::table('failed_jobs')
    ->where('failed_at', '>', now()->subMinutes(1))
    ->first();

if ($recentFailed) {
    echo "   ❌ FOUND FAILED JOB!\n";
    $payload = json_decode($recentFailed->payload, true);
    echo "   Job: " . ($payload['displayName'] ?? 'Unknown') . "\n";
    echo "   Exception: " . substr($recentFailed->exception, 0, 500) . "\n";
} else {
    echo "   ✅ No recent failed jobs\n";
}

// 5. Check Laravel log
echo "\n7. Recent Laravel log entries:\n";
$log = file_get_contents(storage_path('logs/laravel.log'));
$lines = explode("\n", $log);
$recentLines = array_slice($lines, -10);
foreach ($recentLines as $line) {
    if (str_contains($line, 'ERROR') || str_contains($line, 'CallSummaryEmail')) {
        echo "   " . substr($line, 0, 200) . "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total checks: $checks in " . round(microtime(true) - $startTime, 2) . " seconds\n";
echo "This shows exactly what happens when you click the button.\n";