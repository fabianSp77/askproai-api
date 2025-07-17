<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== BEREINIGE ALTE QUEUE JOBS ===\n\n";

try {
    $redis = app('redis');
    
    // Zeige Jobs in high queue
    $highQueueJobs = $redis->lrange('queues:high', 0, -1);
    echo "Jobs in 'high' queue VOR Bereinigung: " . count($highQueueJobs) . "\n";
    
    $oldJobsCount = 0;
    $keptJobs = [];
    
    foreach ($highQueueJobs as $jobData) {
        $job = json_decode($jobData, true);
        
        // Check if job is from July 2nd (old)
        if (isset($job['pushedAt'])) {
            $pushedDate = date('Y-m-d', $job['pushedAt']);
            if ($pushedDate === '2025-07-02') {
                $oldJobsCount++;
                echo "  - Entferne alten Job: " . ($job['displayName'] ?? 'Unknown') . " (vom {$pushedDate})\n";
                // Don't keep this job
                continue;
            }
        }
        
        // Keep newer jobs
        $keptJobs[] = $jobData;
    }
    
    if ($oldJobsCount > 0) {
        // Clear the queue
        $redis->del('queues:high');
        
        // Re-add only the newer jobs
        if (count($keptJobs) > 0) {
            foreach ($keptJobs as $job) {
                $redis->rpush('queues:high', $job);
            }
        }
        
        echo "\n✅ Entfernt: {$oldJobsCount} alte Jobs\n";
        echo "✅ Behalten: " . count($keptJobs) . " aktuelle Jobs\n";
    } else {
        echo "\n✅ Keine alten Jobs gefunden\n";
    }
    
    // Zeige finale Queue-Größen
    echo "\n=== FINALE QUEUE GRÖSSEN ===\n";
    $queues = ['default', 'high', 'low', 'webhooks', 'emails', 'mcp-high', 'mcp-default'];
    
    foreach ($queues as $queue) {
        $length = $redis->llen("queues:{$queue}");
        echo "  - {$queue}: {$length} Jobs\n";
    }
    
    echo "\n✅ Bereinigung abgeschlossen!\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}