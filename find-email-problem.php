<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SUCHE EMAIL PROBLEM ===\n\n";

// 1. Test what happens when we dispatch a job
echo "1. DISPATCHING TEST JOB:\n";
$redis = app('redis');
$before = $redis->llen('queues:emails');
echo "   Queue before: $before\n";

// Dispatch job
\App\Jobs\SendCallSummaryEmailJob::dispatch(
    258,
    ['test-' . time() . '@askproai.de'],
    true,
    true,
    'Debug test',
    'internal'
);

$after = $redis->llen('queues:emails');
echo "   Queue after: $after\n";

if ($after > $before) {
    // Get job details
    $job = $redis->lindex('queues:emails', -1);
    $jobData = json_decode($job, true);
    echo "   Job ID: " . $jobData['id'] . "\n";
    echo "   Job Class: " . $jobData['displayName'] . "\n";
    
    // Wait a bit
    echo "\n   Waiting 3 seconds...\n";
    sleep(3);
    
    $final = $redis->llen('queues:emails');
    echo "   Queue after wait: $final\n";
    
    if ($final < $after) {
        echo "   ✅ Job was processed!\n";
        
        // Check logs for ResendTransport
        echo "\n2. CHECKING LOGS FOR RESEND ACTIVITY:\n";
        $timestamp = date('H:i', time() - 10); // Last 10 minutes
        $logs = shell_exec("grep '$timestamp' /var/www/api-gateway/storage/logs/laravel-2025-07-08.log | grep -E 'ResendTransport|SendCallSummaryEmailJob' | tail -10");
        echo $logs ?: "   No logs found\n";
        
    } else {
        echo "   ❌ Job still in queue\n";
        
        // Try to process manually
        echo "\n3. TRYING MANUAL PROCESSING:\n";
        $job = $redis->lpop('queues:emails');
        if ($job) {
            $payload = json_decode($job, true);
            
            // Unserialize the job
            $jobInstance = unserialize($payload['data']['command']);
            
            echo "   Executing job manually...\n";
            try {
                $jobInstance->handle();
                echo "   ✅ Manual execution completed\n";
            } catch (\Exception $e) {
                echo "   ❌ Error: " . $e->getMessage() . "\n";
            }
        }
    }
} else {
    echo "   ❌ Job was not queued!\n";
}

// 4. Check if logs are being written
echo "\n4. CHECKING IF LOGS ARE WRITTEN:\n";
\Illuminate\Support\Facades\Log::error('[EMAIL TEST] This should appear in logs');

$found = shell_exec("grep 'EMAIL TEST' /var/www/api-gateway/storage/logs/laravel-2025-07-08.log");
echo $found ?: "   Log not found\n";

// 5. Check ResendTransport logs with different approach
echo "\n5. SEARCHING ALL LOGS FOR RESENDTRANSPORT:\n";
$allLogs = shell_exec("find /var/www/api-gateway/storage/logs -name '*.log' -mtime -1 -exec grep -l 'ResendTransport' {} \\; 2>/dev/null");
if ($allLogs) {
    echo "   Found in files:\n$allLogs";
    
    // Get latest entries
    $files = explode("\n", trim($allLogs));
    foreach ($files as $file) {
        if ($file) {
            echo "\n   From $file:\n";
            $content = shell_exec("grep 'ResendTransport' '$file' | tail -3");
            echo $content;
        }
    }
} else {
    echo "   No ResendTransport logs found in any file\n";
}

// 6. Test if the problem is with the Job or with Mail sending
echo "\n6. TESTING MAIL SENDING DIRECTLY:\n";
try {
    // Enable all logging
    \Illuminate\Support\Facades\Log::error('[BEFORE MAIL SEND]');
    
    $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(258);
    \Illuminate\Support\Facades\Mail::to('direct-test@askproai.de')->send(
        new \App\Mail\CallSummaryEmail($call, true, true, 'Direct test', 'internal')
    );
    
    \Illuminate\Support\Facades\Log::error('[AFTER MAIL SEND]');
    echo "   ✅ Direct mail send completed\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    \Illuminate\Support\Facades\Log::error('[MAIL ERROR]', ['error' => $e->getMessage()]);
}

echo "\n=== ENDE ===\n";