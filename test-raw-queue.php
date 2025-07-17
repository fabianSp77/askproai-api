<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST RAW QUEUE OPERATIONS ===\n\n";

$redis = app('redis');

// 1. Test raw Redis operations
echo "1. Testing Raw Redis:\n";
try {
    $testKey = 'test:queue:' . time();
    $redis->set($testKey, 'test');
    $value = $redis->get($testKey);
    $redis->del($testKey);
    echo "   ✅ Redis is working (value: $value)\n";
} catch (\Exception $e) {
    echo "   ❌ Redis error: " . $e->getMessage() . "\n";
}

// 2. Test Laravel Queue manually
echo "\n2. Testing Laravel Queue Push:\n";
try {
    $queue = app('queue');
    $connection = $queue->connection('redis');
    
    // Push a simple job
    $jobId = $connection->push('test', ['data' => 'test'], 'emails');
    echo "   Job ID: $jobId\n";
    
    // Check if it's in queue
    $count = $redis->llen('queues:emails');
    echo "   Items in emails queue: $count\n";
    
    if ($count > 0) {
        echo "   ✅ Manual queue push works\n";
        // Remove test job
        $redis->lpop('queues:emails');
    } else {
        echo "   ❌ Manual queue push failed\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Queue error: " . $e->getMessage() . "\n";
}

// 3. Test with a real Mail job
echo "\n3. Testing Mail Queue Directly:\n";
try {
    $call = \App\Models\Call::first();
    if (!$call) {
        echo "   No calls found\n";
    } else {
        app()->instance('current_company_id', $call->company_id);
        
        // Create mail instance
        $mail = new \App\Mail\CallSummaryEmail($call, true, true, 'Direct test', 'internal');
        
        // Try to queue it manually
        $mailer = app('mail.manager')->mailer();
        $pendingMail = new \Illuminate\Mail\PendingMail($mailer);
        $pendingMail->to('test@example.com');
        
        // Check before
        $before = $redis->llen('queues:emails');
        
        // Queue it
        $pendingMail->queue($mail);
        
        // Check after
        $after = $redis->llen('queues:emails');
        
        echo "   Queue before: $before\n";
        echo "   Queue after: $after\n";
        echo "   Difference: " . ($after - $before) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

// 4. Check if there's a sync issue
echo "\n4. Checking Queue Connection:\n";
$defaultConnection = config('queue.default');
$mailConnection = config('mail.mailers.resend.queue_connection') ?? config('queue.default');
echo "   Default queue: $defaultConnection\n";
echo "   Mail queue: $mailConnection\n";

if ($defaultConnection !== $mailConnection) {
    echo "   ⚠️  Queue connections don't match!\n";
}

// 5. Check if emails are being sent synchronously
echo "\n5. Testing Synchronous Behavior:\n";
$mailConfig = config('mail');
if (isset($mailConfig['mailers']['resend'])) {
    $resendConfig = $mailConfig['mailers']['resend'];
    echo "   Resend transport: " . ($resendConfig['transport'] ?? 'not set') . "\n";
    
    // Check if there's a force sync setting
    if (app()->environment('local') || app()->environment('testing')) {
        echo "   Environment: " . app()->environment() . "\n";
        echo "   ⚠️  Local/testing environment might force sync sending\n";
    }
}

echo "\n=== DIAGNOSIS ===\n";
echo "The issue appears to be that emails are being sent SYNCHRONOUSLY\n";
echo "instead of being queued. This could be because:\n";
echo "1. The mail driver is forcing synchronous sends\n";
echo "2. The queue connection is set to 'sync'\n";
echo "3. There's an issue with the Resend mail transport\n";