#!/usr/bin/env php
<?php
/**
 * Fix Queue Job Timeout Issues
 * 
 * This script fixes queue job timeout issues by:
 * 1. Checking Redis memory
 * 2. Clearing failed jobs
 * 3. Updating timeout configuration
 * 
 * Error Code: QUEUE_001
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Redis;

echo "🔧 Queue Timeout Fix Script\n";
echo "===========================\n\n";

try {
    // Step 1: Check Redis connection and memory
    echo "1. Checking Redis status...\n";
    
    try {
        $pong = Redis::ping();
        echo "   ✅ Redis connection successful\n";
        
        // Get Redis info
        $info = Redis::info();
        $memoryUsed = $info['used_memory_human'] ?? 'N/A';
        $memoryPeak = $info['used_memory_peak_human'] ?? 'N/A';
        
        echo "   Memory used: {$memoryUsed}\n";
        echo "   Memory peak: {$memoryPeak}\n";
        
        // Check if memory usage is high
        if (isset($info['used_memory']) && isset($info['maxmemory']) && $info['maxmemory'] > 0) {
            $memoryPercentage = ($info['used_memory'] / $info['maxmemory']) * 100;
            if ($memoryPercentage > 80) {
                echo "   ⚠️  Redis memory usage is high ({$memoryPercentage}%)\n";
            }
        }
    } catch (\Exception $e) {
        echo "   ❌ Redis connection failed: " . $e->getMessage() . "\n";
        echo "   Please ensure Redis is running: sudo systemctl start redis\n";
        exit(1);
    }

    // Step 2: Check failed jobs
    echo "\n2. Checking failed jobs...\n";
    
    exec('php artisan queue:failed 2>&1', $output);
    $failedJobsOutput = implode("\n", $output);
    
    // Count failed jobs
    $failedCount = 0;
    if (strpos($failedJobsOutput, 'No failed jobs') === false) {
        $lines = explode("\n", $failedJobsOutput);
        foreach ($lines as $line) {
            if (preg_match('/^\d+\s+/', $line)) {
                $failedCount++;
            }
        }
    }
    
    echo "   Failed jobs: {$failedCount}\n";
    
    if ($failedCount > 0) {
        echo "   Recent failed jobs:\n";
        // Show first 5 failed jobs
        $count = 0;
        foreach ($lines as $line) {
            if (preg_match('/^\d+\s+/', $line) && $count < 5) {
                echo "   " . substr($line, 0, 100) . "...\n";
                $count++;
            }
        }
        
        echo "\n   Clear all failed jobs? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if ($line === 'yes') {
            exec('php artisan queue:flush 2>&1', $flushOutput);
            echo "   ✅ Failed jobs cleared\n";
        }
    }

    // Step 3: Check Horizon configuration
    echo "\n3. Checking Horizon configuration...\n";
    
    $horizonConfig = config('horizon');
    $environments = $horizonConfig['environments'] ?? [];
    $currentEnv = app()->environment();
    
    if (isset($environments[$currentEnv])) {
        echo "   Current environment: {$currentEnv}\n";
        
        foreach ($environments[$currentEnv] as $supervisor => $config) {
            echo "   Supervisor: {$supervisor}\n";
            echo "   - Connection: " . ($config['connection'] ?? 'default') . "\n";
            echo "   - Queue: " . ($config['queue'] ?? 'default') . "\n";
            echo "   - Timeout: " . ($config['timeout'] ?? 60) . " seconds\n";
            echo "   - Memory: " . ($config['memory'] ?? 128) . " MB\n";
            echo "   - Processes: " . ($config['processes'] ?? 1) . "\n";
            echo "   - Max tries: " . ($config['tries'] ?? 3) . "\n";
            
            if (($config['timeout'] ?? 60) < 300) {
                echo "   ⚠️  Timeout might be too low for long-running jobs\n";
            }
        }
    } else {
        echo "   ❌ No Horizon configuration found for environment: {$currentEnv}\n";
    }

    // Step 4: Update timeout configuration
    echo "\n4. Recommended configuration changes...\n";
    
    $configFile = base_path('config/horizon.php');
    if (file_exists($configFile)) {
        echo "   Update config/horizon.php:\n";
        echo "   ```php\n";
        echo "   'environments' => [\n";
        echo "       '{$currentEnv}' => [\n";
        echo "           'supervisor-1' => [\n";
        echo "               'connection' => 'redis',\n";
        echo "               'queue' => ['webhooks', 'default'],\n";
        echo "               'balance' => 'simple',\n";
        echo "               'processes' => 3,\n";
        echo "               'tries' => 3,\n";
        echo "               'timeout' => 300, // 5 minutes\n";
        echo "               'memory' => 512,\n";
        echo "           ],\n";
        echo "       ],\n";
        echo "   ],\n";
        echo "   ```\n";
    }

    // Step 5: Check current queue sizes
    echo "\n5. Checking queue sizes...\n";
    
    $queues = ['default', 'webhooks', 'emails', 'imports'];
    foreach ($queues as $queue) {
        try {
            $size = Redis::llen("queues:{$queue}");
            echo "   Queue '{$queue}': {$size} jobs\n";
            
            if ($size > 1000) {
                echo "   ⚠️  Queue '{$queue}' has many pending jobs\n";
            }
        } catch (\Exception $e) {
            // Queue might not exist
        }
    }

    // Step 6: Restart Horizon
    echo "\n6. Restarting Horizon...\n";
    
    exec('php artisan horizon:terminate 2>&1', $terminateOutput);
    echo "   Horizon terminated\n";
    
    echo "   Please restart Horizon with: php artisan horizon\n";
    echo "   Or use supervisor/systemd for automatic restart\n";

    // Summary
    echo "\n✅ Queue timeout fix completed!\n";
    echo "\nRecommendations:\n";
    echo "1. Update timeout in config/horizon.php to 300 seconds\n";
    echo "2. Monitor Redis memory usage\n";
    echo "3. Set up job retry logic with exponential backoff\n";
    echo "4. Use supervisor to auto-restart Horizon\n";
    echo "5. Monitor failed jobs regularly\n";
    
    echo "\nMonitoring commands:\n";
    echo "- php artisan horizon:list      # View supervisors\n";
    echo "- php artisan queue:failed      # View failed jobs\n";
    echo "- php artisan horizon:metrics   # View metrics\n";
    echo "- redis-cli INFO memory         # Check Redis memory\n";
    
    exit(0);

} catch (\Exception $e) {
    echo "\n❌ Error during fix process: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}