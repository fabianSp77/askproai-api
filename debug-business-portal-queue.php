<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG Business Portal Queue Problem ===\n\n";

// 1. Check Queue Configuration
echo "1. Queue Konfiguration:\n";
echo "   Default Queue: " . config('queue.default') . "\n";
echo "   Mail Queue: " . (config('mail.queue') ?: 'default') . "\n\n";

// 2. Check if queue jobs are being created
echo "2. Prüfe letzte Queue Jobs:\n";

$jobs = \DB::table('jobs')->orderBy('created_at', 'desc')->limit(10)->get();
if ($jobs->count() > 0) {
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        echo "   - Queue: " . $job->queue . "\n";
        echo "     Job: " . ($payload['displayName'] ?? 'Unknown') . "\n";
        echo "     Created: " . date('Y-m-d H:i:s', $job->created_at) . "\n";
        echo "     Attempts: " . $job->attempts . "\n\n";
    }
} else {
    echo "   Keine Jobs in der Datenbank-Queue gefunden\n\n";
}

// 3. Check Redis queues
echo "3. Redis Queue Status:\n";
$redis = app('redis');
$queues = ['default', 'high', 'emails', 'low'];

foreach ($queues as $queue) {
    $count = $redis->llen("queues:{$queue}");
    echo "   - $queue: $count Jobs\n";
    
    if ($count > 0) {
        // Show first job
        $job = json_decode($redis->lindex("queues:{$queue}", 0), true);
        if ($job) {
            echo "     First Job: " . ($job['displayName'] ?? 'Unknown') . "\n";
            echo "     Pushed At: " . date('Y-m-d H:i:s', $job['pushedAt'] ?? 0) . "\n";
        }
    }
}

// 4. Check failed jobs
echo "\n4. Failed Jobs:\n";
$failedJobs = \DB::table('failed_jobs')
    ->where('failed_at', '>', now()->subHours(1))
    ->orderBy('failed_at', 'desc')
    ->limit(5)
    ->get();

if ($failedJobs->count() > 0) {
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        echo "   - " . ($payload['displayName'] ?? 'Unknown') . "\n";
        echo "     Failed at: " . $job->failed_at . "\n";
        echo "     Exception: " . substr($job->exception, 0, 200) . "...\n\n";
    }
} else {
    echo "   ✅ Keine fehlgeschlagenen Jobs\n";
}

// 5. Test direct vs queued email
echo "\n5. Test Direct vs Queued E-Mail:\n";

$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(227);
if ($call) {
    app()->instance('current_company_id', $call->company_id);
    
    // Test 1: Direct send (works)
    echo "\n   a) Direct Send (wie Test-Skripte):\n";
    try {
        \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
            $call,
            true,
            false,
            'Direct Send Test - ' . now()->format('H:i:s'),
            'internal'
        ));
        echo "      ✅ Direct send erfolgreich\n";
    } catch (\Exception $e) {
        echo "      ❌ Error: " . $e->getMessage() . "\n";
    }
    
    // Test 2: Queued send (like Business Portal)
    echo "\n   b) Queued Send (wie Business Portal):\n";
    try {
        \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue(new \App\Mail\CallSummaryEmail(
            $call,
            true,
            false,
            'Queued Send Test - ' . now()->format('H:i:s'),
            'internal'
        ));
        echo "      ✅ Queue erfolgreich\n";
        
        // Check if job is in queue
        sleep(1);
        $queueCount = 0;
        foreach ($queues as $queue) {
            $queueCount += $redis->llen("queues:{$queue}");
        }
        echo "      Jobs in Queue: $queueCount\n";
        
    } catch (\Exception $e) {
        echo "      ❌ Error: " . $e->getMessage() . "\n";
    }
}

// 6. Check queue worker status
echo "\n6. Queue Worker Status:\n";
$workers = shell_exec('ps aux | grep -E "queue:work|horizon" | grep -v grep | wc -l');
echo "   Active Workers: " . trim($workers) . "\n";

$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
echo "   Horizon Status: " . trim($horizonStatus) . "\n";

echo "\n=== PROBLEM GEFUNDEN ===\n";
if (trim($workers) == "0") {
    echo "❌ KEINE Queue Worker laufen!\n";
    echo "   → Lösung: php artisan horizon\n";
} else {
    echo "✅ Queue Worker laufen\n";
    echo "   → Prüfen Sie ob die richtigen Queues verarbeitet werden\n";
}

echo "\n=== LÖSUNG ===\n";
echo "1. Starten Sie Horizon neu:\n";
echo "   php artisan horizon:terminate\n";
echo "   php artisan horizon\n\n";
echo "2. Oder manuell Queue verarbeiten:\n";
echo "   php artisan queue:work --queue=default,high,emails\n";