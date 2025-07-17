<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "=== EMAIL PIPELINE DEBUG ===\n\n";

// 1. Test if ResendTransport logs are working
echo "1. Testing ResendTransport logging:\n";
$testMail = new \App\Mail\CallSummaryEmail(
    \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(258),
    true,
    true,
    'Debug test',
    'internal'
);

// Temporarily add more logging to ResendTransport
$transport = Mail::mailer('resend')->getSymfonyTransport();
echo "   Transport class: " . get_class($transport) . "\n";

// 2. Check Horizon processed/failed jobs
echo "\n2. Horizon job statistics:\n";
$redis = app('redis');
$processedKey = 'horizon:processed_jobs';
$failedKey = 'horizon:failed_jobs';

$processed = $redis->hgetall($processedKey);
$failed = $redis->hgetall($failedKey);

echo "   Processed jobs: " . count($processed) . "\n";
echo "   Failed jobs: " . count($failed) . "\n";

// Check recent failed jobs
if (count($failed) > 0) {
    echo "   Recent failed jobs:\n";
    foreach (array_slice($failed, -5) as $key => $value) {
        echo "      - $key: $value\n";
    }
}

// 3. Check email queue specific stats
echo "\n3. Email queue stats:\n";
$emailQueueKey = 'askproaiqueue:emails';
$stats = $redis->hgetall($emailQueueKey);
echo "   Throughput: " . ($stats['throughput'] ?? 0) . " jobs/min\n";
echo "   Runtime: " . ($stats['runtime'] ?? 0) . " ms\n";

// 4. Check if Horizon is processing the email queue
echo "\n4. Horizon supervisor status:\n";
$supervisors = \Laravel\Horizon\Contracts\SupervisorRepository::class;
$supervisorRepo = app($supervisors);
$allSupervisors = $supervisorRepo->all();

foreach ($allSupervisors as $supervisor) {
    if (in_array('emails', $supervisor->processes->pluck('queue')->toArray())) {
        echo "   Email supervisor found: " . $supervisor->name . "\n";
        echo "   Status: " . $supervisor->status . "\n";
        echo "   Processes: " . $supervisor->processes->count() . "\n";
    }
}

// 5. Test direct email send with enhanced logging
echo "\n5. Testing direct email send with logging:\n";
try {
    // Override log level temporarily
    config(['logging.channels.single.level' => 'debug']);
    Log::channel('single')->info('=== STARTING EMAIL TEST ===');
    
    $recipient = 'test-debug-' . time() . '@example.com';
    Mail::to($recipient)->send($testMail);
    
    echo "   ✅ Email sent to: $recipient\n";
    
    // Check logs immediately
    $logContent = shell_exec("tail -20 /var/www/api-gateway/storage/logs/laravel.log | grep -E 'ResendTransport|EMAIL'");
    if ($logContent) {
        echo "   Recent logs:\n$logContent";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 6. Test job dispatch and immediate check
echo "\n6. Testing job dispatch with immediate check:\n";
$beforeCount = $redis->llen('queues:emails');
echo "   Queue before: $beforeCount\n";

\App\Jobs\SendCallSummaryEmailJob::dispatch(
    258,
    ['test-job-debug@example.com'],
    true,
    true,
    'Debug job test',
    'internal'
);

// Check immediately
$afterCount = $redis->llen('queues:emails');
echo "   Queue after: $afterCount\n";

// Wait a moment and check again
sleep(2);
$finalCount = $redis->llen('queues:emails');
echo "   Queue after 2s: $finalCount\n";

if ($finalCount < $afterCount) {
    echo "   ✅ Job was processed\n";
    
    // Check for processing logs
    $logs = shell_exec("tail -50 /var/www/api-gateway/storage/logs/laravel.log | grep -E 'SendCallSummaryEmailJob|Processing|Processed' | tail -10");
    if ($logs) {
        echo "   Processing logs:\n$logs";
    }
} else {
    echo "   ⚠️ Job still in queue\n";
}

// 7. Check call activities
echo "\n7. Recent email activities for call 258:\n";
$activities = \App\Models\CallActivity::where('call_id', 258)
    ->where('activity_type', 'email_sent')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($activities as $activity) {
    echo "   - " . $activity->created_at->format('Y-m-d H:i:s') . ": " . $activity->activity . "\n";
    if (isset($activity->metadata['recipients'])) {
        echo "     Recipients: " . implode(', ', $activity->metadata['recipients']) . "\n";
    }
}

echo "\n=== END DEBUG ===\n";