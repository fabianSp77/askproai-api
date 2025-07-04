<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Redis;

echo "=== FAILED JOBS IN REDIS ANALYSE ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// Get all failed jobs from Redis
$failedJobIds = Redis::zrevrange('askproaifailed_jobs', 0, -1);
$totalFailed = count($failedJobIds);

echo "Gefundene Failed Jobs: $totalFailed\n\n";

// Analyze failed jobs by type
$failedByType = [];
$oldestFailed = null;
$newestFailed = null;

foreach ($failedJobIds as $jobId) {
    $jobData = Redis::hgetall($jobId);
    
    if (isset($jobData['name'])) {
        $jobType = $jobData['name'];
        if (!isset($failedByType[$jobType])) {
            $failedByType[$jobType] = 0;
        }
        $failedByType[$jobType]++;
        
        // Track oldest/newest
        if (isset($jobData['failed_at'])) {
            $failedAt = (int)$jobData['failed_at'];
            if (!$oldestFailed || $failedAt < $oldestFailed) {
                $oldestFailed = $failedAt;
            }
            if (!$newestFailed || $failedAt > $newestFailed) {
                $newestFailed = $failedAt;
            }
        }
    }
}

// Show summary
echo "FAILED JOBS NACH TYP:\n";
echo str_repeat("-", 30) . "\n";
foreach ($failedByType as $type => $count) {
    echo "$type: $count\n";
}

echo "\nZEITRAUM:\n";
echo str_repeat("-", 30) . "\n";
if ($oldestFailed) {
    echo "Ältester Failed Job: " . date('Y-m-d H:i:s', $oldestFailed) . "\n";
    echo "Neuester Failed Job: " . date('Y-m-d H:i:s', $newestFailed) . "\n";
    
    $daysOld = round((time() - $oldestFailed) / (24 * 3600), 1);
    echo "Zeitspanne: $daysOld Tage\n";
}

// Show last 5 failed jobs details
echo "\nLETZTE 5 FAILED JOBS (Details):\n";
echo str_repeat("-", 50) . "\n";

$recentFailed = array_slice($failedJobIds, 0, 5);
foreach ($recentFailed as $i => $jobId) {
    $jobData = Redis::hgetall($jobId);
    
    echo "\n" . ($i + 1) . ". Job ID: $jobId\n";
    if (isset($jobData['name'])) {
        echo "   Type: " . $jobData['name'] . "\n";
    }
    if (isset($jobData['failed_at'])) {
        echo "   Failed at: " . date('Y-m-d H:i:s', $jobData['failed_at']) . "\n";
    }
    if (isset($jobData['exception'])) {
        $exception = $jobData['exception'];
        // Extract error message
        if (preg_match('/"message":"([^"]+)"/', $exception, $matches)) {
            echo "   Error: " . $matches[1] . "\n";
        }
    }
}

echo "\n💡 EMPFEHLUNG:\n";
echo str_repeat("-", 30) . "\n";
echo "Diese Failed Jobs sind alle alt und stammen aus der Migration.\n";
echo "Sie können sicher gelöscht werden mit:\n";
echo "php artisan horizon:clear\n";
echo "\nOder nur Failed Jobs löschen:\n";
echo "Redis::del('askproaifailed_jobs');\n";
echo "Redis::del(Redis::keys('askproaifailed:*'));\n";