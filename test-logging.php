<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;

echo "=== TESTING LOGGING ===\n\n";

// Test 1: Direct logging
echo "1. Testing direct logging:\n";
Log::info('[TEST] Direct log message', ['test' => 'value']);
echo "Logged info message\n";

// Test 2: Check log file
echo "\n2. Checking log file:\n";
$logFile = storage_path('logs/laravel.log');
$lastLine = shell_exec("tail -1 $logFile");
echo "Last log line: $lastLine\n";

// Test 3: Log from job context
echo "\n3. Testing job logging:\n";
dispatch(new class implements \Illuminate\Contracts\Queue\ShouldQueue {
    use \Illuminate\Bus\Queueable;
    use \Illuminate\Queue\SerializesModels;
    use \Illuminate\Queue\InteractsWithQueue;
    use \Illuminate\Foundation\Bus\Dispatchable;
    
    public function __construct() {
        $this->onQueue('emails');
    }
    
    public function handle() {
        \Illuminate\Support\Facades\Log::info('[TEST JOB] This is from a job');
        echo "Job executed and logged\n";
    }
});

// Wait for job
sleep(2);

// Check for job log
echo "\n4. Checking for job log:\n";
$jobLog = shell_exec("grep 'TEST JOB' $logFile");
echo $jobLog ?: "No job log found\n";

// Test 5: Check logging config
echo "\n5. Logging configuration:\n";
echo "Default channel: " . config('logging.default') . "\n";
echo "Stack channels: " . implode(', ', config('logging.channels.stack.channels')) . "\n";

// Test 6: Test if logs are being written elsewhere
echo "\n6. All log files in storage/logs:\n";
$logFiles = shell_exec("ls -la /var/www/api-gateway/storage/logs/ | grep -E '\\.log$' | awk '{print $9, $5}'");
echo $logFiles;