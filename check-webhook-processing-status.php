<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== WEBHOOK ASYNC PROCESSING STATUS CHECK ===\n\n";

// 1. Check Horizon Status
echo "1. HORIZON STATUS:\n";
$horizonStatus = \Laravel\Horizon\Contracts\MasterSupervisorRepository::class;
$masters = app($horizonStatus)->all();
if (count($masters) > 0) {
    echo "   ✅ Horizon is running with " . count($masters) . " master supervisor(s)\n";
    foreach ($masters as $master) {
        echo "      - {$master->name} (PID: {$master->pid})\n";
    }
} else {
    echo "   ❌ Horizon is NOT running\n";
}

// 2. Check Queue Configuration
echo "\n2. QUEUE CONFIGURATION:\n";
echo "   Queue Connection: " . config('queue.default') . "\n";
try {
    \Illuminate\Support\Facades\Redis::connection()->ping();
    echo "   Redis Connection: ✅ Connected\n";
} catch (\Exception $e) {
    echo "   Redis Connection: ❌ Not Connected - " . $e->getMessage() . "\n";
}

// 3. Check Webhook Queue Workers
echo "\n3. WEBHOOK QUEUE WORKERS:\n";
$supervisors = app(\Laravel\Horizon\Contracts\SupervisorRepository::class)->all();
$webhookQueues = ['webhooks', 'webhooks-high'];
$foundWebhookWorkers = false;

foreach ($supervisors as $supervisor) {
    if (array_intersect($supervisor->processes, $webhookQueues)) {
        echo "   ✅ Found webhook worker: {$supervisor->name}\n";
        echo "      - Status: {$supervisor->status}\n";
        echo "      - Processes: " . implode(', ', $supervisor->processes) . "\n";
        $foundWebhookWorkers = true;
    }
}

if (!$foundWebhookWorkers) {
    echo "   ❌ No webhook workers found\n";
}

// 4. Check Recent Webhook Events
echo "\n4. RECENT WEBHOOK EVENTS (Last 24 hours):\n";
$recentEvents = \App\Models\WebhookEvent::where('created_at', '>', now()->subDay())
    ->selectRaw('provider, status, COUNT(*) as count')
    ->groupBy('provider', 'status')
    ->get();

foreach ($recentEvents as $event) {
    echo "   {$event->provider} - {$event->status}: {$event->count}\n";
}

// 5. Check Failed Jobs
echo "\n5. FAILED WEBHOOK JOBS:\n";
$failedJobs = DB::table('failed_jobs')
    ->where('queue', 'like', '%webhook%')
    ->where('failed_at', '>', now()->subDay())
    ->count();
echo "   Failed webhook jobs (last 24h): {$failedJobs}\n";

// 6. Check Async Configuration
echo "\n6. ASYNC WEBHOOK CONFIGURATION:\n";
echo "   Retell async enabled: " . (config('services.webhook.async.retell', true) ? "✅ Yes" : "❌ No") . "\n";
echo "   CalCom async enabled: " . (config('services.webhook.async.calcom', true) ? "✅ Yes" : "❌ No") . "\n";

// 7. Check Job Processing
echo "\n7. JOB PROCESSING TEST:\n";
try {
    // Dispatch a test job
    dispatch(new \Illuminate\Queue\CallQueuedClosure(function () {
        \Log::info('Test webhook job executed');
    }))->onQueue('webhooks');
    
    echo "   ✅ Test job dispatched successfully\n";
} catch (\Exception $e) {
    echo "   ❌ Failed to dispatch test job: " . $e->getMessage() . "\n";
}

// 8. Check Tenant Scope Issues
echo "\n8. TENANT SCOPE CONFIGURATION:\n";
$tenantScopeBypass = app()->bound('tenant_scope_bypass');
echo "   Tenant scope bypass available: " . ($tenantScopeBypass ? "✅ Yes" : "❌ No") . "\n";

// 9. Recommendations
echo "\n9. RECOMMENDATIONS:\n";
if (count($masters) == 0) {
    echo "   ⚠️  Start Horizon: php artisan horizon\n";
}
if ($failedJobs > 0) {
    echo "   ⚠️  Review failed jobs: php artisan queue:failed\n";
    echo "   ⚠️  Retry failed jobs: php artisan queue:retry all\n";
}

// 10. Process a test webhook through the processor
echo "\n10. WEBHOOK PROCESSOR TEST:\n";
try {
    $processor = app(\App\Services\WebhookProcessor::class);
    
    // Create a test payload
    $testPayload = [
        'event' => 'test_event',
        'test_id' => 'test_' . uniqid(),
        'timestamp' => time()
    ];
    
    // Check if we're in console
    echo "   Running in console: " . (app()->runningInConsole() ? "Yes (async disabled)" : "No (async enabled)") . "\n";
    
    // Process through webhook processor
    $result = $processor->process('retell', $testPayload, [], 'test-correlation-id');
    
    if ($result['queued'] ?? false) {
        echo "   ✅ Webhook would be queued for async processing\n";
    } else {
        echo "   ⚠️  Webhook processed synchronously\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Webhook processor error: " . $e->getMessage() . "\n";
}

echo "\n=== END OF STATUS CHECK ===\n";