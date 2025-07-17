<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST PORTAL EMAIL FLOW ===\n\n";

// 1. Get a portal user
$portalUser = \App\Models\PortalUser::first();
if (!$portalUser) {
    echo "❌ No portal user found\n";
    exit(1);
}
echo "✅ Portal User: {$portalUser->email} (Company: {$portalUser->company_id})\n";

// 2. Get call 258
$callId = 258;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
if (!$call) {
    echo "❌ Call not found\n";
    exit(1);
}
echo "✅ Call found: {$call->id} (Company: {$call->company_id})\n";

// 3. Set portal auth context
Auth::guard('portal')->login($portalUser);
app()->instance('current_company_id', $call->company_id);

echo "\n=== TESTING EMAIL DISPATCH ===\n";

// 4. Dispatch job directly
try {
    echo "Dispatching job...\n";
    
    $job = new \App\Jobs\SendCallSummaryEmailJob(
        $call->id,
        ['fabianspitzer@icloud.com'],
        true,
        true,
        'Test from portal flow - ' . now()->format('H:i:s'),
        'internal'
    );
    
    // Check job queue
    echo "Job queue: " . $job->queue . "\n";
    
    // Dispatch
    dispatch($job);
    
    echo "✅ Job dispatched successfully\n";
    
    // Check Redis queue
    $redis = app('redis');
    $queueSize = $redis->llen('queues:emails');
    echo "Queue size after dispatch: $queueSize\n";
    
} catch (\Exception $e) {
    echo "❌ Error dispatching job: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// 5. Check Horizon metrics
echo "\n=== HORIZON METRICS ===\n";
try {
    $horizon = app('Laravel\Horizon\Contracts\MetricsRepository');
    $throughput = $horizon->throughputForQueue('emails');
    echo "Email queue throughput: " . end($throughput) . " jobs/min\n";
    
    $runtime = $horizon->runtimeForQueue('emails');
    echo "Email queue avg runtime: " . end($runtime) . " ms\n";
} catch (\Exception $e) {
    echo "Could not get Horizon metrics: " . $e->getMessage() . "\n";
}

// 6. Process job manually
echo "\n=== MANUAL JOB PROCESSING ===\n";
if ($queueSize > 0) {
    try {
        // Get job from queue
        $jobPayload = $redis->lpop('queues:emails');
        if ($jobPayload) {
            $payload = json_decode($jobPayload, true);
            echo "Job type: " . $payload['displayName'] . "\n";
            echo "Job ID: " . $payload['id'] . "\n";
            
            // Put it back and process with artisan
            $redis->lpush('queues:emails', $jobPayload);
            
            echo "\nProcessing with artisan...\n";
            \Artisan::call('queue:work', [
                '--queue' => 'emails',
                '--stop-when-empty' => true,
                '--max-jobs' => 1
            ]);
            
            $output = \Artisan::output();
            echo $output;
        }
    } catch (\Exception $e) {
        echo "❌ Error processing: " . $e->getMessage() . "\n";
    }
}

// 7. Check Resend logs
echo "\n=== CHECKING RESEND ACTIVITY ===\n";
$logs = shell_exec("tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep -i resend | tail -5");
if ($logs) {
    echo "Recent Resend activity:\n$logs";
} else {
    echo "No recent Resend activity in logs\n";
}

echo "\n=== END TEST ===\n";