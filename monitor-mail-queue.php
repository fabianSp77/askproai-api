<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== E-Mail Queue Monitor ===\n\n";

// Check mail configuration
echo "Mail Configuration:\n";
echo "- Driver: " . config('mail.default') . "\n";
echo "- From: " . config('mail.from.address') . "\n\n";

// Check jobs in queue
$pendingJobs = \DB::table('jobs')->count();
$failedJobs = \DB::table('failed_jobs')->count();

echo "Queue Status:\n";
echo "- Pending jobs: {$pendingJobs}\n";
echo "- Failed jobs: {$failedJobs}\n\n";

// Check recent mail logs
if (config('mail.default') === 'log') {
    echo "⚠️  Mail driver is set to 'log' - emails are being logged instead of sent!\n";
    echo "Check logs at: storage/logs/laravel.log\n\n";
    
    // Show recent logged emails
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $mailLines = [];
        
        foreach ($lines as $i => $line) {
            if (strpos($line, 'Message-ID:') !== false || strpos($line, 'Subject:') !== false) {
                $mailLines[] = $line;
                // Get next few lines for context
                for ($j = 1; $j <= 5 && isset($lines[$i + $j]); $j++) {
                    if (strpos($lines[$i + $j], '[20') === 0) break; // Stop at next log entry
                    $mailLines[] = $lines[$i + $j];
                }
            }
        }
        
        if (!empty($mailLines)) {
            echo "Recent logged emails:\n";
            echo implode('', array_slice($mailLines, -20));
        }
    }
}

// Check failed jobs details
if ($failedJobs > 0) {
    echo "\nFailed Jobs Details:\n";
    $failed = \DB::table('failed_jobs')->latest()->limit(5)->get();
    foreach ($failed as $job) {
        $payload = json_decode($job->payload, true);
        echo "- Job: " . ($payload['displayName'] ?? 'Unknown') . "\n";
        echo "  Failed at: {$job->failed_at}\n";
        echo "  Exception: " . substr($job->exception, 0, 200) . "...\n\n";
    }
}

// Process queue (if requested)
if (in_array('--process', $argv)) {
    echo "\nProcessing queue...\n";
    \Artisan::call('queue:work', [
        '--stop-when-empty' => true,
        '--tries' => 1
    ]);
    echo \Artisan::output();
}