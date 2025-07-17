<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== HIGH PRIORITY QUEUE ANALYSIS ===\n\n";

try {
    $redis = app('redis');
    
    // Get all jobs from high priority queue
    $highQueueJobs = $redis->lrange('queues:high', 0, -1);
    
    echo "Jobs in 'high' queue: " . count($highQueueJobs) . "\n\n";
    
    foreach ($highQueueJobs as $index => $jobData) {
        $job = json_decode($jobData, true);
        
        echo "Job " . ($index + 1) . ":\n";
        echo "  ID: " . ($job['uuid'] ?? 'N/A') . "\n";
        echo "  Type: " . ($job['displayName'] ?? 'Unknown') . "\n";
        echo "  Attempts: " . ($job['attempts'] ?? 0) . "\n";
        echo "  Pushed at: " . (isset($job['pushedAt']) ? date('Y-m-d H:i:s', $job['pushedAt']) : 'N/A') . "\n";
        
        // Try to decode the job data
        if (isset($job['data']['command'])) {
            $command = unserialize($job['data']['command']);
            echo "  Command Class: " . get_class($command) . "\n";
            
            // If it's a mail job, show recipient
            if ($command instanceof \Illuminate\Mail\SendQueuedMailable) {
                echo "  Mail Job Details:\n";
                $mailable = unserialize($command->mailable);
                if (method_exists($mailable, 'envelope')) {
                    $envelope = $mailable->envelope();
                    $to = $envelope->to;
                    if (is_array($to) && count($to) > 0) {
                        echo "    Recipients: ";
                        foreach ($to as $recipient) {
                            echo $recipient->address . " ";
                        }
                        echo "\n";
                    }
                }
            }
        }
        
        echo "\n";
    }
    
    // Check if workers are processing
    echo "=== WORKER STATUS ===\n";
    $processes = shell_exec('ps aux | grep -E "horizon|queue:work" | grep -v grep');
    if ($processes) {
        echo "Active workers:\n";
        echo $processes;
    } else {
        echo "⚠️  No active workers found!\n";
    }
    
    // Process the queue
    echo "\n=== PROCESSING HIGH PRIORITY QUEUE ===\n";
    echo "Verarbeite high priority jobs...\n";
    
    $exitCode = \Artisan::call('queue:work', [
        '--queue' => 'high',
        '--stop-when-empty' => true,
        '--tries' => 3
    ]);
    
    echo \Artisan::output();
    
    // Check again
    $highQueueJobsAfter = $redis->lrange('queues:high', 0, -1);
    echo "\nJobs remaining in 'high' queue: " . count($highQueueJobsAfter) . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}