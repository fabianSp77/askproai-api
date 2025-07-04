<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== ULTRATHINK HORIZON & JOBS DEEP ANALYSIS ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 70) . "\n\n";

$issues = [];
$successes = [];

// 1. HORIZON PROZESS ANALYSE
echo "1. HORIZON PROZESS ANALYSE\n";
echo str_repeat("-", 50) . "\n";

// Master Prozess
$horizonMaster = shell_exec('ps aux | grep "horizon$" | grep -v grep');
if ($horizonMaster) {
    echo "✅ Horizon Master Prozess läuft:\n";
    $lines = explode("\n", trim($horizonMaster));
    foreach ($lines as $line) {
        if ($line) {
            preg_match('/(\d+)\s+(\d+\.\d+)\s+(\d+\.\d+)/', $line, $matches);
            if (isset($matches[1])) {
                echo "   PID: {$matches[1]} | CPU: {$matches[2]}% | MEM: {$matches[3]}%\n";
            }
        }
    }
    $successes[] = "Horizon Master läuft";
} else {
    echo "❌ Kein Horizon Master Prozess gefunden!\n";
    $issues[] = "Horizon Master nicht aktiv";
}

// Supervisors
echo "\n📊 Horizon Supervisors:\n";
$supervisors = shell_exec('php artisan horizon:list 2>&1');
echo $supervisors;

// Worker Count
$workerCount = trim(shell_exec('ps aux | grep "horizon:work" | grep -v grep | wc -l'));
echo "\n✅ Aktive Worker: $workerCount\n";

if ($workerCount < 10) {
    $issues[] = "Nur $workerCount Worker aktiv (sollten mehr sein)";
} else {
    $successes[] = "$workerCount Worker aktiv";
}

// 2. QUEUE KONFIGURATION PRÜFUNG
echo "\n2. QUEUE KONFIGURATION PRÜFUNG\n";
echo str_repeat("-", 50) . "\n";

$queueConfig = [
    'connection' => config('queue.default'),
    'redis_client' => config('database.redis.client'),
    'redis_prefix' => config('database.redis.options.prefix', 'NOT SET'),
    'horizon_prefix' => config('horizon.prefix'),
];

foreach ($queueConfig as $key => $value) {
    echo "$key: $value\n";
}

if ($queueConfig['connection'] !== 'redis') {
    $issues[] = "Queue Connection ist nicht 'redis': " . $queueConfig['connection'];
} else {
    $successes[] = "Queue Connection korrekt: redis";
}

if (!empty($queueConfig['redis_prefix']) && $queueConfig['redis_prefix'] !== '') {
    $issues[] = "Redis Prefix ist gesetzt: '" . $queueConfig['redis_prefix'] . "' (sollte leer sein)";
}

// 3. JOB PROCESSING TEST
echo "\n3. JOB PROCESSING TEST\n";
echo str_repeat("-", 50) . "\n";

// Test verschiedene Queues
$queues = ['default', 'webhooks', 'appointments'];
$jobTests = [];

foreach ($queues as $queue) {
    echo "\nTeste Queue: $queue\n";
    
    // Clear test key
    Redis::del("test_job_$queue");
    
    // Dispatch test job
    dispatch(function() use ($queue) {
        Redis::set("test_job_$queue", time());
        Log::info("Test job processed on queue: $queue");
    })->onQueue($queue);
    
    // Wait
    sleep(2);
    
    // Check result
    $result = Redis::get("test_job_$queue");
    if ($result && (time() - $result) < 5) {
        echo "✅ Queue '$queue' verarbeitet Jobs\n";
        $jobTests[$queue] = true;
        $successes[] = "Queue '$queue' funktioniert";
    } else {
        echo "❌ Queue '$queue' verarbeitet KEINE Jobs!\n";
        $jobTests[$queue] = false;
        $issues[] = "Queue '$queue' verarbeitet keine Jobs";
    }
}

// 4. REAL JOB TYPES TEST
echo "\n4. REAL JOB TYPES TEST\n";
echo str_repeat("-", 50) . "\n";

// Test HeartbeatJob
echo "\nTeste HeartbeatJob:\n";
Redis::del('askproai:heartbeat:last');
dispatch(new \App\Jobs\HeartbeatJob());
sleep(2);
$heartbeat = Redis::get('askproai:heartbeat:last');
if ($heartbeat && (time() - $heartbeat) < 5) {
    echo "✅ HeartbeatJob funktioniert\n";
    $successes[] = "HeartbeatJob wird verarbeitet";
} else {
    echo "❌ HeartbeatJob funktioniert NICHT\n";
    $issues[] = "HeartbeatJob wird nicht verarbeitet";
}

// Test ProcessRetellCallStartedJob
echo "\nTeste ProcessRetellCallStartedJob:\n";
try {
    $testData = [
        'event' => 'call_started',
        'call_id' => 'job_test_' . time(),
        'call' => [
            'call_id' => 'job_test_' . time(),
            'from_number' => '+491234567890',
            'to_number' => '+493083793369',
            'agent_id' => 'agent_test',
            'start_timestamp' => time() * 1000
        ]
    ];
    
    $company = \App\Models\Company::first();
    if ($company) {
        dispatch(new \App\Jobs\ProcessRetellCallStartedJob($testData, $company->id));
        echo "✅ ProcessRetellCallStartedJob dispatched erfolgreich\n";
        $successes[] = "Webhook Jobs können dispatched werden";
    }
} catch (Exception $e) {
    echo "❌ Fehler beim Dispatch: " . $e->getMessage() . "\n";
    $issues[] = "Webhook Job Dispatch fehlgeschlagen";
}

// 5. FAILED JOBS ANALYSE
echo "\n5. FAILED JOBS ANALYSE\n";
echo str_repeat("-", 50) . "\n";

$failedCount = Redis::zcard('askproaifailed_jobs');
echo "Failed Jobs in Redis: $failedCount\n";

if ($failedCount > 0) {
    echo "\nLetzte 5 Failed Jobs:\n";
    $failedJobs = Redis::zrevrange('askproaifailed_jobs', 0, 4);
    foreach ($failedJobs as $jobId) {
        $jobData = Redis::hgetall($jobId);
        if (isset($jobData['failed_at']) && isset($jobData['name'])) {
            $failedAt = date('Y-m-d H:i:s', $jobData['failed_at']);
            echo "- {$jobData['name']} (Failed: $failedAt)\n";
        }
    }
    $issues[] = "$failedCount Failed Jobs vorhanden";
}

// Check database failed jobs
$dbFailedJobs = DB::table('failed_jobs')->count();
echo "\nFailed Jobs in Database: $dbFailedJobs\n";

// 6. QUEUE METRICS
echo "\n6. QUEUE METRICS\n";
echo str_repeat("-", 50) . "\n";

// Queue Längen
foreach ($queues as $queue) {
    $length = Redis::llen("queues:$queue");
    $reserved = Redis::zcard("queues:$queue:reserved");
    echo "Queue '$queue': $length pending, $reserved reserved\n";
    
    if ($length > 100) {
        $issues[] = "Queue '$queue' hat $length pending Jobs (zu viele!)";
    }
}

// Recent Jobs
$recentJobsKey = 'askproai:horizon:recent_jobs';
$recentCount = Redis::zcard($recentJobsKey);
echo "\nRecent Jobs tracked: $recentCount\n";

// Last processed time
$lastJob = Redis::zrevrange($recentJobsKey, 0, 0, 'WITHSCORES');
if (count($lastJob) >= 2) {
    $lastTime = $lastJob[1] / 1000;
    $minutesAgo = round((time() - $lastTime) / 60, 1);
    echo "Letzter Job vor: $minutesAgo Minuten\n";
    
    if ($minutesAgo > 30) {
        $issues[] = "Letzter Job vor $minutesAgo Minuten (zu lange her)";
    } else {
        $successes[] = "Jobs werden aktuell verarbeitet";
    }
}

// 7. MEMORY & PERFORMANCE
echo "\n7. MEMORY & PERFORMANCE\n";
echo str_repeat("-", 50) . "\n";

// Redis Memory
$redisInfo = Redis::info('memory');
if (isset($redisInfo['used_memory_human'])) {
    echo "Redis Memory: " . $redisInfo['used_memory_human'] . "\n";
}

// Check for memory leaks
$horizonMemory = shell_exec("ps aux | grep horizon | awk '{sum += $6} END {print sum/1024}'");
echo "Horizon Total Memory: " . round(floatval($horizonMemory)) . " MB\n";

if (floatval($horizonMemory) > 2048) {
    $issues[] = "Horizon nutzt zu viel Memory: " . round(floatval($horizonMemory)) . " MB";
}

// 8. SUPERVISOR CONFIG CHECK
echo "\n8. SUPERVISOR CONFIG CHECK\n";
echo str_repeat("-", 50) . "\n";

$supervisorConfig = shell_exec('cat /etc/supervisor/conf.d/horizon.conf 2>&1');
if (strpos($supervisorConfig, 'horizon') !== false) {
    echo "✅ Supervisor Config vorhanden\n";
    
    // Check autostart
    if (strpos($supervisorConfig, 'autostart=true') !== false) {
        echo "✅ Autostart aktiviert\n";
        $successes[] = "Horizon startet automatisch";
    } else {
        echo "❌ Autostart nicht aktiviert\n";
        $issues[] = "Horizon Autostart nicht aktiviert";
    }
    
    // Check autorestart
    if (strpos($supervisorConfig, 'autorestart=true') !== false) {
        echo "✅ Autorestart aktiviert\n";
    }
} else {
    echo "❌ Keine Supervisor Config gefunden\n";
    $issues[] = "Supervisor Config fehlt";
}

// 9. LIVE MONITORING
echo "\n9. LIVE MONITORING (10 Sekunden)\n";
echo str_repeat("-", 50) . "\n";

$startJobs = Redis::get('askproai:heartbeat:last') ?? 0;
echo "Starte Live-Monitoring...\n";

// Dispatch mehrere Test-Jobs
for ($i = 1; $i <= 5; $i++) {
    dispatch(new \App\Jobs\HeartbeatJob())->onQueue('default');
    echo "Dispatched Job #$i\n";
    sleep(2);
}

$endJobs = Redis::get('askproai:heartbeat:last') ?? 0;
if ($endJobs > $startJobs) {
    echo "✅ Jobs werden live verarbeitet!\n";
    $successes[] = "Live Job-Verarbeitung funktioniert";
} else {
    echo "❌ Keine Live-Verarbeitung erkannt\n";
    $issues[] = "Live Job-Verarbeitung nicht erkannt";
}

// FINAL SUMMARY
echo "\n" . str_repeat("=", 70) . "\n";
echo "ULTRATHINK HORIZON & JOBS ZUSAMMENFASSUNG\n";
echo str_repeat("=", 70) . "\n\n";

if (count($issues) == 0) {
    echo "🎉 PERFEKT! Keine Probleme gefunden!\n\n";
    echo "✅ ERFOLGE:\n";
    foreach ($successes as $success) {
        echo "   - $success\n";
    }
} else {
    echo "⚠️ GEFUNDENE PROBLEME:\n";
    foreach ($issues as $issue) {
        echo "   ❌ $issue\n";
    }
    
    echo "\n✅ ERFOLGE:\n";
    foreach ($successes as $success) {
        echo "   - $success\n";
    }
    
    echo "\n💡 EMPFEHLUNGEN:\n";
    if (in_array("Horizon Master nicht aktiv", $issues)) {
        echo "   1. Starte Horizon neu: supervisorctl restart horizon\n";
    }
    if ($failedCount > 0) {
        echo "   2. Failed Jobs prüfen: php artisan horizon:failed\n";
    }
    foreach ($jobTests as $queue => $working) {
        if (!$working) {
            echo "   3. Queue '$queue' prüfen - Worker möglicherweise blockiert\n";
        }
    }
}

echo "\n";