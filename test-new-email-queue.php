<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING NEW EMAIL QUEUE SYSTEM ===\n\n";

$callId = 258;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);

if (!$call) {
    echo "Call not found!\n";
    exit(1);
}

// Set company context
app()->instance('current_company_id', $call->company_id);

$redis = app('redis');

// 1. Test the new job
echo "1. Testing SendCallSummaryEmailJob:\n";
$beforeCount = $redis->llen('queues:emails');
echo "   Queue before: $beforeCount\n";

try {
    \App\Jobs\SendCallSummaryEmailJob::dispatch(
        $callId,
        ['test-queue-job@example.com'],
        true,
        true,
        'Test Queue Job',
        'internal'
    );
    
    $afterCount = $redis->llen('queues:emails');
    echo "   Queue after: $afterCount\n";
    echo "   Difference: " . ($afterCount - $beforeCount) . "\n";
    
    if ($afterCount > $beforeCount) {
        echo "   ✅ Job was queued successfully!\n";
        
        // Get job details
        $job = $redis->lindex('queues:emails', -1);
        $jobData = json_decode($job, true);
        if ($jobData) {
            echo "   Job Type: " . ($jobData['displayName'] ?? 'unknown') . "\n";
            echo "   Job ID: " . ($jobData['id'] ?? 'unknown') . "\n";
        }
    } else {
        echo "   ❌ Job was not queued\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 2. Simulate portal API call
echo "\n2. Simulating Portal API Call:\n";

// Clear activities
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(10))
    ->delete();

$portalUser = \App\Models\PortalUser::first();
$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

$request = \Illuminate\Http\Request::create(
    "/business/api/calls/{$callId}/send-summary",
    'POST',
    [
        'recipients' => ['fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => true,
        'message' => 'Test from new queue system - ' . now()->format('H:i:s')
    ]
);

$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

$beforeApi = $redis->llen('queues:emails');

try {
    $response = $controller->sendSummary($request, $call);
    $responseData = json_decode($response->getContent(), true);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    echo "   Response: " . ($responseData['message'] ?? 'unknown') . "\n";
    
    $afterApi = $redis->llen('queues:emails');
    echo "   Queue change: " . ($afterApi - $beforeApi) . "\n";
    
    if ($afterApi > $beforeApi) {
        echo "   ✅ Email was queued!\n";
    } else {
        echo "   ❌ Email was NOT queued\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 3. Process one job manually to test
echo "\n3. Processing one job manually:\n";
if ($redis->llen('queues:emails') > 0) {
    try {
        $worker = app('queue.worker');
        $options = app('queue.worker.options')->merge([
            'stop-when-empty' => true,
            'max-jobs' => 1
        ]);
        
        echo "   Processing...\n";
        $worker->runNextJob('redis', 'emails', $options);
        echo "   ✅ Job processed\n";
    } catch (\Exception $e) {
        echo "   ❌ Processing error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   No jobs to process\n";
}

echo "\n=== RESULT ===\n";
echo "The new job-based system should properly queue emails.\n";
echo "Check your email and Resend dashboard.\n";