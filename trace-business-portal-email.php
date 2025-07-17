<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TRACE Business Portal Email Flow ===\n\n";

// 1. Simulate exactly what Business Portal does
echo "1. Simulating Business Portal API call:\n";

$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(228);
if (!$call) {
    echo "❌ Call 228 not found!\n";
    exit(1);
}

// Set company context
app()->instance('current_company_id', $call->company_id);

// Clear previous activities
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 228)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(10))
    ->delete();

// Track the email sending process
$startTime = microtime(true);

try {
    echo "   Call ID: " . $call->id . "\n";
    echo "   Customer: " . ($call->customer_name ?? 'N/A') . "\n";
    echo "   To Email: fabianspitzer@icloud.com\n";
    echo "   From: " . config('mail.from.address') . "\n\n";
    
    // Create mail instance
    $mail = new \App\Mail\CallSummaryEmail(
        $call,
        true,  // includeSummary
        true,  // includeCSV
        'Business Portal Trace Test - ' . now()->format('H:i:s'),
        'internal'
    );
    
    echo "2. Sending email...\n";
    
    // Send email
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send($mail);
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "   ✅ Email sent in {$duration}ms\n\n";
    
    // Check if activity was created
    echo "3. Checking CallActivity:\n";
    $activity = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', 228)
        ->where('activity_type', 'email_sent')
        ->orderBy('created_at', 'desc')
        ->first();
    
    if ($activity) {
        echo "   ✅ Activity created: " . $activity->description . "\n";
        echo "   Created at: " . $activity->created_at . "\n";
    } else {
        echo "   ❌ No activity record found\n";
    }
    
    // Check Laravel log for errors
    echo "\n4. Checking Laravel log for errors:\n";
    $log = file_get_contents(storage_path('logs/laravel.log'));
    $lines = explode("\n", $log);
    $recentLines = array_slice($lines, -20);
    $errorFound = false;
    
    foreach ($recentLines as $line) {
        if (str_contains($line, 'ERROR') || str_contains($line, 'Exception')) {
            echo "   " . $line . "\n";
            $errorFound = true;
        }
    }
    
    if (!$errorFound) {
        echo "   ✅ No recent errors in log\n";
    }
    
    echo "\n5. Testing with queue (like Business Portal):\n";
    
    // Clear activities again
    \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', 228)
        ->where('activity_type', 'email_sent')
        ->where('created_at', '>', now()->subSeconds(5))
        ->delete();
    
    // Queue email
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue($mail);
    
    echo "   ✅ Email queued\n";
    
    // Wait for processing
    sleep(2);
    
    // Check queue status
    $redis = app('redis');
    $queuedJobs = 0;
    foreach (['default', 'high', 'emails'] as $queue) {
        $queuedJobs += $redis->llen("queues:{$queue}");
    }
    
    echo "   Jobs in queue: $queuedJobs\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "If the email was sent successfully but not received:\n";
echo "1. Check spam folder\n";
echo "2. Check Resend dashboard for delivery status\n";
echo "3. Try with a different recipient email (Gmail, etc.)\n";
echo "4. Check if iCloud is blocking emails from this sender\n";