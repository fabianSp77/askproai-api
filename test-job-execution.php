<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING JOB EXECUTION ===\n\n";

// Create a simple file to prove job executed
$testFile = '/tmp/email-job-test-' . time() . '.txt';

// 1. Direct job dispatch
echo "1. Dispatching test job:\n";
$job = new \App\Jobs\SendCallSummaryEmailJob(
    258,
    ['direct-test@example.com'],
    true,
    true,
    'Direct job test',
    'internal'
);

// Override handle method to write to file
$reflectionClass = new ReflectionClass($job);
$handleMethod = $reflectionClass->getMethod('handle');
$handleMethod->setAccessible(true);

// Create a wrapper that writes to file
$originalHandle = function() use ($job, $testFile, $handleMethod) {
    file_put_contents($testFile, "Job executed at: " . now() . "\n");
    
    // Try to execute original
    try {
        $handleMethod->invoke($job);
        file_put_contents($testFile, "Original handle executed successfully\n", FILE_APPEND);
    } catch (\Exception $e) {
        file_put_contents($testFile, "Error in handle: " . $e->getMessage() . "\n", FILE_APPEND);
    }
};

// Dispatch synchronously to test
echo "Executing job synchronously...\n";
try {
    $originalHandle();
    echo "✅ Job executed\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Check test file
if (file_exists($testFile)) {
    echo "\nTest file contents:\n";
    echo file_get_contents($testFile);
    unlink($testFile);
} else {
    echo "❌ Test file not created\n";
}

// 2. Test via queue
echo "\n2. Testing via queue:\n";
$redis = app('redis');
$before = $redis->llen('queues:emails');

dispatch($job);

$after = $redis->llen('queues:emails');
echo "Queue before: $before, after: $after\n";

if ($after > $before) {
    echo "✅ Job queued\n";
    
    // Process it
    echo "Processing job...\n";
    \Artisan::call('queue:work', [
        '--queue' => 'emails',
        '--stop-when-empty' => true,
        '--max-jobs' => 1
    ]);
    
    $output = \Artisan::output();
    echo "Artisan output:\n$output\n";
    
    $final = $redis->llen('queues:emails');
    echo "Queue after processing: $final\n";
}

// 3. Check for any SendCallSummaryEmailJob in logs
echo "\n3. Searching all log files for job:\n";
$logFiles = glob('/var/www/api-gateway/storage/logs/*.log');
foreach ($logFiles as $logFile) {
    $found = shell_exec("grep -l 'SendCallSummaryEmailJob' '$logFile' 2>/dev/null");
    if ($found) {
        echo "Found in: " . basename($logFile) . "\n";
        $lines = shell_exec("grep 'SendCallSummaryEmailJob' '$logFile' | tail -5");
        echo $lines;
    }
}

echo "\n=== END TEST ===\n";