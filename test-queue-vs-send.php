<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST: Queue vs Direct Send ===\n\n";

$callId = 258;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);

if (!$call) {
    echo "Call $callId not found!\n";
    exit(1);
}

// Set company context
app()->instance('current_company_id', $call->company_id);

echo "Testing with Call #$callId\n\n";

// 1. Test QUEUE method (what the API uses)
echo "1. Testing QUEUE method:\n";
try {
    $redis = app('redis');
    $beforeCount = $redis->llen('queues:emails');
    echo "   Emails in queue before: $beforeCount\n";
    
    \Illuminate\Support\Facades\Mail::to('test-queue@example.com')
        ->queue(new \App\Mail\CallSummaryEmail(
            $call,
            true,
            true,
            'Test Queue Method',
            'internal'
        ));
    
    $afterCount = $redis->llen('queues:emails');
    echo "   Emails in queue after: $afterCount\n";
    echo "   Difference: " . ($afterCount - $beforeCount) . "\n";
    
    if ($afterCount > $beforeCount) {
        echo "   ✅ Email was queued successfully\n";
        
        // Get job details
        $job = $redis->lindex('queues:emails', -1);
        $jobData = json_decode($job, true);
        if ($jobData) {
            echo "   Job ID: " . ($jobData['id'] ?? 'unknown') . "\n";
            echo "   Job Type: " . ($jobData['displayName'] ?? 'unknown') . "\n";
        }
    } else {
        echo "   ❌ Email was NOT queued!\n";
    }
} catch (\Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// 2. Test SEND method (direct)
echo "\n2. Testing SEND method (direct):\n";
try {
    \Illuminate\Support\Facades\Mail::to('test-send@example.com')
        ->send(new \App\Mail\CallSummaryEmail(
            $call,
            true,
            true,
            'Test Send Method',
            'internal'
        ));
    echo "   ✅ Direct send completed\n";
} catch (\Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

// 3. Check if it's a configuration issue
echo "\n3. Mail Configuration:\n";
echo "   Driver: " . config('mail.default') . "\n";
echo "   Queue Connection: " . config('queue.default') . "\n";
echo "   Mail Queue: " . config('mail.queue') . "\n";

// 4. Test the mail class directly
echo "\n4. Testing Mail Class Construction:\n";
try {
    $mail = new \App\Mail\CallSummaryEmail(
        $call,
        true,
        true,
        'Test Construction',
        'internal'
    );
    echo "   ✅ Mail object created successfully\n";
    echo "   Class: " . get_class($mail) . "\n";
    
    // Check if it implements ShouldQueue
    $interfaces = class_implements($mail);
    if (in_array('Illuminate\Contracts\Queue\ShouldQueue', $interfaces)) {
        echo "   ✅ Implements ShouldQueue\n";
    } else {
        echo "   ❌ Does NOT implement ShouldQueue!\n";
    }
} catch (\Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}

// 5. Check Resend logs
echo "\n5. Checking Resend API:\n";
$resendKey = config('services.resend.key');
if ($resendKey) {
    echo "   ✅ Resend API key configured\n";
    
    // Make a test API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.resend.com/emails");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $resendKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "   ✅ Resend API is accessible\n";
    } else {
        echo "   ❌ Resend API error: HTTP $httpCode\n";
    }
} else {
    echo "   ❌ Resend API key NOT configured!\n";
}

echo "\n=== CONCLUSION ===\n";
echo "If emails are not being queued, check:\n";
echo "1. CallSummaryEmail implements ShouldQueue\n";
echo "2. Queue driver is configured correctly\n";
echo "3. No exceptions during queue process\n";