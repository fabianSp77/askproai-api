<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ANALYZE Stuck Queue Job ===\n\n";

// 1. Get the stuck job
echo "1. Getting stuck job from queue:\n";
$redis = app('redis');

$jobData = $redis->lindex("queues:default", 0);
if (!$jobData) {
    echo "   No job in default queue\n";
    exit(1);
}

$job = json_decode($jobData, true);
echo "   Job UUID: " . ($job['uuid'] ?? 'N/A') . "\n";
echo "   Display Name: " . ($job['displayName'] ?? 'N/A') . "\n";
echo "   Attempts: " . ($job['attempts'] ?? 0) . "\n";
echo "   Pushed At: " . date('Y-m-d H:i:s', $job['pushedAt'] ?? 0) . "\n";

// 2. Try to process it manually
echo "\n2. Attempting to process job manually:\n";

try {
    // Get the job payload
    $payload = $job['data'] ?? [];
    
    if (isset($payload['command'])) {
        $command = unserialize($payload['command']);
        echo "   Command class: " . get_class($command) . "\n";
        
        if ($command instanceof \Illuminate\Mail\SendQueuedMailable) {
            echo "   Mailable class: " . get_class($command->mailable) . "\n";
            
            // Try to send it directly
            echo "\n3. Attempting direct send:\n";
            try {
                // Get the mailable
                $mailable = $command->mailable;
                
                // Get recipients
                $to = $command->to;
                if (is_array($to) && isset($to[0]['address'])) {
                    echo "   To: " . $to[0]['address'] . "\n";
                }
                
                // Try to send
                \Illuminate\Support\Facades\Mail::send($mailable);
                echo "   ✅ Direct send successful!\n";
                
            } catch (\Exception $e) {
                echo "   ❌ Direct send failed: " . $e->getMessage() . "\n";
                echo "   Error class: " . get_class($e) . "\n";
                echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
                
                // Check for specific errors
                if (str_contains($e->getMessage(), 'appointment_intent_detected')) {
                    echo "\n   ⚠️ FOUND THE ISSUE: Undefined array key error!\n";
                }
            }
        }
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error processing job: " . $e->getMessage() . "\n";
}

// 3. Check Horizon supervisor config
echo "\n4. Checking Horizon Configuration:\n";
$horizonConfig = config('horizon.environments.production');
if ($horizonConfig) {
    foreach ($horizonConfig as $supervisor => $config) {
        if (isset($config['queue']) && (in_array('default', (array)$config['queue']) || $config['queue'] === 'default')) {
            echo "   Supervisor '$supervisor' processes 'default' queue\n";
            echo "   - Connection: " . ($config['connection'] ?? 'N/A') . "\n";
            echo "   - Max Processes: " . ($config['maxProcesses'] ?? 'N/A') . "\n";
            echo "   - Timeout: " . ($config['timeout'] ?? 'N/A') . "\n";
        }
    }
}

// 4. Process with queue:work
echo "\n5. Trying to process with queue:work:\n";
$output = shell_exec('timeout 10 php artisan queue:work --once --queue=default 2>&1');
echo $output;

// 5. Check if job is still there
$jobStillThere = $redis->lindex("queues:default", 0);
if ($jobStillThere && $jobStillThere === $jobData) {
    echo "\n❌ Job is STILL in queue - it's not being processed!\n";
} else {
    echo "\n✅ Job was processed\n";
}

echo "\n=== DIAGNOSIS ===\n";
echo "The job is stuck because:\n";
echo "1. There might be an error in the CallSummaryEmail class\n";
echo "2. The queue workers might not be processing the 'default' queue\n";
echo "3. The job might be failing silently\n";

echo "\n=== IMMEDIATE FIX ===\n";
echo "Run this to process all stuck emails:\n";
echo "php artisan queue:work --queue=default --tries=1\n";