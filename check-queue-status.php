<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== QUEUE STATUS CHECK ===\n\n";

// 1. Check Jobs table
echo "1. Jobs in Queue:\n";
$jobs = \DB::table('jobs')->get();
echo "   Total: " . $jobs->count() . " Jobs\n\n";

if ($jobs->count() > 0) {
    echo "   Details:\n";
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        echo "   - Job ID: {$job->id}\n";
        echo "     Queue: {$job->queue}\n";
        echo "     Created: " . date('Y-m-d H:i:s', $job->created_at) . "\n";
        echo "     Attempts: {$job->attempts}\n";
        echo "     Type: " . ($payload['displayName'] ?? 'Unknown') . "\n";
        echo "     Reserved at: " . ($job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : 'Not reserved') . "\n";
        echo "\n";
    }
}

// 2. Check Failed Jobs
echo "2. Failed Jobs:\n";
$failedJobs = \DB::table('failed_jobs')->orderBy('id', 'desc')->limit(10)->get();
echo "   Total: " . \DB::table('failed_jobs')->count() . " Failed Jobs\n\n";

if ($failedJobs->count() > 0) {
    echo "   Recent failures:\n";
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        echo "   - Failed Job ID: {$job->id}\n";
        echo "     Queue: {$job->queue}\n";
        echo "     Failed at: {$job->failed_at}\n";
        echo "     Type: " . ($payload['displayName'] ?? 'Unknown') . "\n";
        echo "     Exception: " . substr($job->exception, 0, 100) . "...\n";
        echo "\n";
    }
}

// 3. Check Horizon status
echo "3. Horizon Status:\n";
$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
echo "   " . trim($horizonStatus) . "\n\n";

// 4. Check Redis queue lengths
echo "4. Redis Queue Lengths:\n";
try {
    $redis = app('redis');
    $queues = ['default', 'high', 'low', 'webhooks', 'emails'];
    
    foreach ($queues as $queue) {
        $length = $redis->llen("queues:{$queue}");
        echo "   - {$queue}: {$length} jobs\n";
    }
} catch (\Exception $e) {
    echo "   Error reading Redis: " . $e->getMessage() . "\n";
}

// 5. Check for stuck jobs
echo "\n5. Stuck Jobs (reserved > 5 minutes ago):\n";
$stuckJobs = \DB::table('jobs')
    ->whereNotNull('reserved_at')
    ->where('reserved_at', '<', now()->subMinutes(5)->timestamp)
    ->get();

if ($stuckJobs->count() > 0) {
    echo "   ⚠️  Found {$stuckJobs->count()} stuck jobs!\n";
    foreach ($stuckJobs as $job) {
        echo "   - Job ID: {$job->id} (reserved " . round((time() - $job->reserved_at) / 60) . " minutes ago)\n";
    }
} else {
    echo "   ✅ No stuck jobs found\n";
}

// 6. Recommendations
echo "\n=== EMPFEHLUNGEN ===\n";
if ($jobs->count() > 100) {
    echo "⚠️  Queue hat viele Jobs! Mögliche Lösungen:\n";
    echo "1. Mehr Worker starten: php artisan horizon:scale\n";
    echo "2. Jobs manuell verarbeiten: php artisan queue:work --stop-when-empty\n";
    echo "3. Fehlerhafte Jobs entfernen: php artisan queue:flush\n";
} elseif ($failedJobs->count() > 0) {
    echo "⚠️  Es gibt fehlgeschlagene Jobs!\n";
    echo "1. Fehlerhafte Jobs erneut versuchen: php artisan queue:retry all\n";
    echo "2. Einzelnen Job wiederholen: php artisan queue:retry [id]\n";
    echo "3. Alle fehlgeschlagenen Jobs löschen: php artisan queue:flush failed\n";
} else {
    echo "✅ Queue läuft normal\n";
}

// Clear stuck jobs if requested
if (in_array('--clear-stuck', $argv ?? [])) {
    echo "\n=== CLEARING STUCK JOBS ===\n";
    foreach ($stuckJobs as $job) {
        \DB::table('jobs')->where('id', $job->id)->delete();
        echo "Deleted stuck job: {$job->id}\n";
    }
}