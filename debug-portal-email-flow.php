<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ULTRA-DEEP DEBUG Portal E-Mail Flow ===\n\n";

// 1. Simulate EXACT portal request
$callId = 227;
$recipient = 'fabianspitzer@icloud.com';

echo "1. Simuliere EXAKTEN Portal Request:\n";
echo "   Call ID: $callId\n";
echo "   Recipient: $recipient\n\n";

// Get the call
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
if (!$call) {
    echo "❌ Call $callId not found!\n";
    exit(1);
}

// 2. Set up context EXACTLY like portal
echo "2. Setup Portal Context:\n";
app()->instance('current_company_id', $call->company_id);
echo "   Company ID set: " . $call->company_id . "\n";

// Create a portal user context
$portalUser = \App\Models\PortalUser::first();
if ($portalUser) {
    \Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);
    echo "   Portal User: " . $portalUser->name . "\n";
}

// 3. Monitor Laravel log
$logFile = storage_path('logs/laravel.log');
$initialLogSize = filesize($logFile);
echo "   Log file size: " . $initialLogSize . " bytes\n\n";

// 4. Trace mail transport
echo "3. Mail Transport Status:\n";
$transport = \Illuminate\Support\Facades\Mail::getSymfonyTransport();
echo "   Transport: " . get_class($transport) . "\n";
echo "   From Address: " . config('mail.from.address') . "\n";
echo "   Resend Key: " . (config('services.resend.key') ? 'SET' : 'NOT SET') . "\n\n";

// 5. Clear any existing activities
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(10))
    ->delete();

// 6. TRACE EVERY STEP of email creation
echo "4. Creating CallSummaryEmail instance:\n";

try {
    // Monitor memory and time
    $startTime = microtime(true);
    $startMemory = memory_get_usage();
    
    // Create the email object
    $email = new \App\Mail\CallSummaryEmail(
        $call,
        true,  // include_transcript
        true,  // include_csv
        'Portal Debug Test - ' . now()->format('H:i:s'),
        'internal'
    );
    
    $createTime = round((microtime(true) - $startTime) * 1000, 2);
    $memoryUsed = round((memory_get_usage() - $startMemory) / 1024, 2);
    
    echo "   ✅ Email object created in {$createTime}ms, used {$memoryUsed}KB\n";
    
    // Check if email build works
    echo "\n5. Testing email build process:\n";
    
    // Capture any output
    ob_start();
    $errorHandler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
        echo "   ⚠️ PHP Warning/Error: $errstr in $errfile:$errline\n";
    });
    
    // Try to build the email content
    $builtEmail = $email->render();
    
    restore_error_handler();
    $output = ob_get_clean();
    
    if ($output) {
        echo "   Build output: $output\n";
    }
    
    echo "   ✅ Email content built successfully\n";
    
} catch (\Exception $e) {
    echo "   ❌ ERROR creating email: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// 7. TRACE QUEUE PROCESS
echo "\n6. Queueing email (EXACTLY like portal does):\n";

try {
    // Monitor Redis before
    $redis = app('redis');
    $beforeQueues = [];
    foreach (['default', 'high', 'emails', 'low'] as $queue) {
        $beforeQueues[$queue] = $redis->llen("queues:{$queue}");
    }
    
    // Queue the email EXACTLY like portal
    \Illuminate\Support\Facades\Mail::to($recipient)->queue($email);
    
    echo "   ✅ Mail::queue() called\n";
    
    // Check Redis after
    $afterQueues = [];
    $jobAdded = false;
    foreach (['default', 'high', 'emails', 'low'] as $queue) {
        $afterQueues[$queue] = $redis->llen("queues:{$queue}");
        if ($afterQueues[$queue] > $beforeQueues[$queue]) {
            echo "   ✅ Job added to queue: $queue\n";
            $jobAdded = true;
            
            // Get job details
            $jobData = $redis->lindex("queues:{$queue}", -1);
            if ($jobData) {
                $job = json_decode($jobData, true);
                echo "   Job ID: " . ($job['uuid'] ?? 'N/A') . "\n";
                echo "   Job Type: " . ($job['displayName'] ?? 'N/A') . "\n";
            }
        }
    }
    
    if (!$jobAdded) {
        echo "   ❌ NO JOB ADDED TO ANY QUEUE!\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ ERROR queueing email: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 8. Check for immediate errors in log
echo "\n7. Checking for errors in log:\n";
$newLogContent = file_get_contents($logFile);
$newLogSize = filesize($logFile);
if ($newLogSize > $initialLogSize) {
    $newLogs = substr($newLogContent, $initialLogSize);
    $logLines = explode("\n", trim($newLogs));
    foreach ($logLines as $line) {
        if (str_contains($line, 'ERROR') || str_contains($line, 'Exception')) {
            echo "   ❌ Error in log: " . $line . "\n";
        }
    }
} else {
    echo "   ✅ No new errors in log\n";
}

// 9. Wait and check if processed
echo "\n8. Waiting 5 seconds for queue processing...\n";
sleep(5);

// Check queue status
$finalQueues = [];
foreach (['default', 'high', 'emails', 'low'] as $queue) {
    $count = $redis->llen("queues:{$queue}");
    $finalQueues[$queue] = $count;
    if ($count > 0) {
        echo "   ⚠️ $queue still has $count jobs\n";
    }
}

// 10. Check if email was sent to Resend
echo "\n9. Checking Resend API for recent emails:\n";
$apiKey = config('services.resend.key');

$ch = curl_init('https://api.resend.com/emails');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'from' => config('mail.from.address'),
    'to' => [$recipient],
    'subject' => 'Direct API Test - ' . now()->format('H:i:s'),
    'html' => '<p>If this arrives but portal email does not, the problem is in the queue processing.</p>'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Direct API Test: HTTP $httpCode\n";
if ($httpCode == 200) {
    echo "   ✅ Direct Resend API works\n";
} else {
    echo "   ❌ Direct Resend API failed: $response\n";
}

// 11. DEEP DIVE - Check what's in the job
echo "\n10. DEEP ANALYSIS - Job Content:\n";
if ($jobAdded && isset($jobData)) {
    $job = json_decode($jobData, true);
    echo "   Full Job Data:\n";
    echo "   - Display Name: " . ($job['displayName'] ?? 'N/A') . "\n";
    echo "   - Job Class: " . ($job['job'] ?? 'N/A') . "\n";
    echo "   - Queue: " . ($job['queue'] ?? 'N/A') . "\n";
    echo "   - Attempts: " . ($job['attempts'] ?? 0) . "\n";
    
    if (isset($job['data']['command'])) {
        $command = unserialize($job['data']['command']);
        echo "   - Command Class: " . get_class($command) . "\n";
    }
}

echo "\n=== PROBLEM ANALYSIS ===\n";
if (!$jobAdded) {
    echo "❌ CRITICAL: Email is not being queued at all!\n";
    echo "   This explains why it doesn't appear in Resend.\n";
} elseif (count(array_filter($finalQueues)) > 0) {
    echo "❌ Jobs are stuck in queue and not being processed!\n";
} else {
    echo "✅ Job was queued and processed, check Resend dashboard.\n";
}

echo "\n=== NEXT STEPS ===\n";
echo "1. Check if job was added to queue (above)\n";
echo "2. If not, there's a problem with Mail::queue()\n";
echo "3. If yes but still in queue, workers are not processing\n";
echo "4. If processed but not in Resend, check failed_jobs table\n";