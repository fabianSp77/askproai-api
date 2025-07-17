<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DIAGNOSING STUCK EMAIL QUEUE ===\n\n";

// 1. Check Horizon status
echo "1. HORIZON STATUS:\n";
$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
echo "   " . trim($horizonStatus) . "\n\n";

// 2. Check Horizon processes
echo "2. HORIZON PROCESSES:\n";
$processes = shell_exec('ps aux | grep -E "horizon|queue" | grep -v grep');
echo $processes ?: "   No processes found\n";
echo "\n";

// 3. Check Redis queue contents
echo "3. QUEUE CONTENTS:\n";
$redis = app('redis');
$queues = ['default', 'emails', 'high', 'high:notify'];
foreach ($queues as $queue) {
    $queueKey = "queues:$queue";
    $count = $redis->llen($queueKey);
    echo "   $queue: $count jobs\n";
    
    if ($count > 0) {
        // Get first job details
        $job = $redis->lindex($queueKey, 0);
        $jobData = json_decode($job, true);
        if ($jobData) {
            echo "      First job: " . ($jobData['displayName'] ?? 'unknown') . "\n";
            echo "      Created: " . date('Y-m-d H:i:s', $jobData['pushedAt'] ?? 0) . "\n";
            echo "      Attempts: " . ($jobData['attempts'] ?? 0) . "\n";
        }
    }
}

// 4. Check failed jobs
echo "\n4. FAILED JOBS:\n";
$failedCount = \DB::table('failed_jobs')->count();
$recentFailed = \DB::table('failed_jobs')
    ->where('failed_at', '>', now()->subHours(1))
    ->count();
echo "   Total failed: $failedCount\n";
echo "   Failed in last hour: $recentFailed\n";

if ($recentFailed > 0) {
    $lastFailed = \DB::table('failed_jobs')
        ->orderBy('failed_at', 'desc')
        ->first();
    echo "   Last failure: " . $lastFailed->failed_at . "\n";
    echo "   Queue: " . $lastFailed->queue . "\n";
    echo "   Exception: " . substr($lastFailed->exception, 0, 200) . "...\n";
}

// 5. Check Horizon configuration
echo "\n5. HORIZON CONFIGURATION:\n";
$horizonConfig = config('horizon');
echo "   Environments: " . implode(', ', array_keys($horizonConfig['environments'] ?? [])) . "\n";

$env = app()->environment();
$envConfig = $horizonConfig['environments'][$env] ?? null;
if ($envConfig) {
    foreach ($envConfig as $supervisor => $config) {
        echo "   Supervisor '$supervisor':\n";
        echo "      Connection: " . ($config['connection'] ?? 'default') . "\n";
        echo "      Queue: " . (is_array($config['queue'] ?? null) ? implode(', ', $config['queue']) : ($config['queue'] ?? 'default')) . "\n";
        echo "      Processes: " . ($config['maxProcesses'] ?? 'not set') . "\n";
        echo "      Tries: " . ($config['tries'] ?? 'not set') . "\n";
    }
}

// 6. Check if email supervisor is configured
echo "\n6. EMAIL QUEUE CONFIGURATION:\n";
$hasEmailWorker = false;
if ($envConfig) {
    foreach ($envConfig as $supervisor => $config) {
        $queues = is_array($config['queue'] ?? null) ? $config['queue'] : [$config['queue'] ?? 'default'];
        if (in_array('emails', $queues)) {
            $hasEmailWorker = true;
            echo "   ✅ Email worker found: $supervisor\n";
        }
    }
}
if (!$hasEmailWorker) {
    echo "   ❌ NO EMAIL WORKER CONFIGURED!\n";
}

// 7. Test processing a job manually
echo "\n7. TESTING MANUAL JOB PROCESSING:\n";
$emailJob = $redis->lpop("queues:emails");
if ($emailJob) {
    echo "   Found email job, putting it back...\n";
    $redis->lpush("queues:emails", $emailJob);
    
    // Try to process manually
    echo "   Attempting manual processing...\n";
    try {
        $worker = app('queue.worker');
        $worker->runNextJob(
            'redis',
            'emails',
            app('queue.worker.options')->merge(['stop-when-empty' => true])
        );
        echo "   ✅ Manual processing successful\n";
    } catch (\Exception $e) {
        echo "   ❌ Manual processing failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "   No email jobs in queue\n";
}

// 8. Check latest logs
echo "\n8. RECENT LOG ENTRIES:\n";
$logs = shell_exec("tail -20 " . storage_path('logs/laravel.log') . " | grep -E 'email|queue|horizon' | tail -10");
echo $logs ?: "   No relevant log entries\n";

// 9. Check if queues are paused
echo "\n9. HORIZON PAUSE STATUS:\n";
$isPaused = $redis->get('horizon:paused:master');
echo "   Master: " . ($isPaused ? "PAUSED" : "RUNNING") . "\n";

// Get all pause keys
$pauseKeys = $redis->keys('horizon:paused:*');
foreach ($pauseKeys as $key) {
    $supervisor = str_replace('horizon:paused:', '', $key);
    $paused = $redis->get($key);
    echo "   $supervisor: " . ($paused ? "PAUSED" : "RUNNING") . "\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
echo "\nPOSSIBLE ISSUES:\n";
echo "1. Horizon not processing 'emails' queue\n";
echo "2. Workers are paused\n";
echo "3. Connection issues with Redis\n";
echo "4. Job class loading problems\n";

echo "\nSOLUTIONS TO TRY:\n";
echo "1. Restart Horizon: php artisan horizon:terminate\n";
echo "2. Clear and restart: php artisan queue:restart\n";
echo "3. Process manually: php artisan queue:work redis --queue=emails --tries=1\n";
echo "4. Check Horizon dashboard: " . url('/horizon') . "\n";