<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Mail Queue Status ===\n\n";

// Check Redis queues
$redis = app('redis');

// Check which queue is used for mails
echo "1. Mail Configuration:\n";
echo "   Default Queue: " . config('queue.default') . "\n";
echo "   Mail Queue: " . (config('mail.queue') ?? 'default') . "\n\n";

// Check all queues
echo "2. Queue Lengths:\n";
$queues = ['default', 'high', 'low', 'webhooks', 'emails', 'mails'];

foreach ($queues as $queue) {
    $length = $redis->llen("queues:{$queue}");
    if ($length > 0) {
        echo "   - {$queue}: {$length} Jobs\n";
        
        // Show first job details
        $firstJob = $redis->lindex("queues:{$queue}", 0);
        if ($firstJob) {
            $job = json_decode($firstJob, true);
            echo "     First job: " . ($job['displayName'] ?? 'Unknown') . "\n";
            if (isset($job['pushedAt'])) {
                echo "     Pushed at: " . date('Y-m-d H:i:s', $job['pushedAt']) . "\n";
            }
        }
    }
}

// Check failed jobs
echo "\n3. Failed Mail Jobs:\n";
$failedJobs = \DB::table('failed_jobs')
    ->where('payload', 'like', '%SendQueuedMailable%')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

if ($failedJobs->count() > 0) {
    foreach ($failedJobs as $job) {
        echo "   - Failed at: {$job->failed_at}\n";
        echo "     Queue: {$job->queue}\n";
        echo "     Error: " . substr($job->exception, 0, 100) . "...\n\n";
    }
} else {
    echo "   ✅ No failed mail jobs\n";
}

// Check Horizon configuration
echo "\n4. Horizon Mail Queue Config:\n";
$horizonConfig = config('horizon.environments.production');
if (isset($horizonConfig['supervisor-1']['queue'])) {
    echo "   Queues monitored: " . implode(', ', $horizonConfig['supervisor-1']['queue']) . "\n";
}

// Process any pending mail jobs
echo "\n5. Processing pending mail jobs...\n";
$exitCode = \Illuminate\Support\Facades\Artisan::call('queue:work', [
    '--queue' => 'default,emails,mails',
    '--stop-when-empty' => true,
    '--tries' => 1
]);

$output = \Illuminate\Support\Facades\Artisan::output();
if ($output) {
    echo $output;
} else {
    echo "   No pending jobs to process\n";
}