<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "=== EXACT PORTAL EMAIL TEST ===\n\n";

$callId = 258;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);

// 1. Test 1: Direct Mail::send (works)
echo "Test 1: Direct Mail::send\n";
try {
    app()->instance('current_company_id', $call->company_id);
    
    Mail::to('direct-test@example.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        true,
        'Direct test - ' . now()->format('H:i:s'),
        'internal'
    ));
    echo "✅ Direct send completed\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// 2. Test 2: Using the job (as portal does)
echo "\nTest 2: Job dispatch (as portal does)\n";
try {
    $redis = app('redis');
    $before = $redis->llen('queues:emails');
    
    \App\Jobs\SendCallSummaryEmailJob::dispatch(
        $callId,
        ['fabianspitzer@icloud.com'],
        true,
        true,
        'Portal simulation - ' . now()->format('H:i:s'),
        'internal'
    );
    
    $after = $redis->llen('queues:emails');
    echo "Queue before: $before, after: $after\n";
    
    if ($after > $before) {
        echo "✅ Job queued\n";
        
        // Wait for processing
        echo "Waiting 5 seconds for processing...\n";
        sleep(5);
        
        $final = $redis->llen('queues:emails');
        echo "Queue after wait: $final\n";
        
        if ($final < $after) {
            echo "✅ Job was processed\n";
        } else {
            echo "❌ Job NOT processed - still in queue\n";
            
            // Get the job details
            $job = $redis->lindex('queues:emails', 0);
            if ($job) {
                $payload = json_decode($job, true);
                echo "Job details:\n";
                echo "  ID: " . $payload['id'] . "\n";
                echo "  Created: " . date('Y-m-d H:i:s', $payload['pushedAt']) . "\n";
                echo "  Attempts: " . ($payload['attempts'] ?? 0) . "\n";
            }
        }
    } else {
        echo "❌ Job NOT queued\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// 3. Check Resend dashboard via API
echo "\n3. Checking Resend API for recent emails:\n";
$ch = curl_init('https://api.resend.com/emails');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . config('mail.mailers.resend.key'),
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($statusCode === 200) {
    $emails = json_decode($response, true);
    if (isset($emails['data'])) {
        echo "Recent emails in Resend:\n";
        foreach (array_slice($emails['data'], 0, 5) as $email) {
            echo "  - " . $email['created_at'] . ": " . $email['to'][0] . " - " . $email['subject'] . "\n";
        }
    }
} else {
    echo "Could not fetch Resend emails. Status: $statusCode\n";
}

// 4. Check logs
echo "\n4. Recent ResendTransport logs:\n";
$logs = shell_exec("tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep -i resend | tail -5");
if ($logs) {
    echo $logs;
} else {
    echo "No ResendTransport logs found\n";
}

// 5. Check horizon workers
echo "\n5. Horizon email workers:\n";
$workers = shell_exec("ps aux | grep 'queue:work.*emails' | grep -v grep");
if ($workers) {
    echo $workers;
} else {
    echo "No email workers found running\n";
}

echo "\n=== END TEST ===\n";