<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;
use App\Models\Call;
use App\Mail\CallSummaryEmail;

echo "Testing mail system...\n";
echo "Mail config:\n";
echo "- Driver: " . config('mail.default') . "\n";
echo "- Host: " . config('mail.mailers.smtp.host') . "\n";
echo "- Port: " . config('mail.mailers.smtp.port') . "\n";
echo "- From: " . config('mail.from.address') . "\n";
echo "- Queue Connection: " . config('queue.default') . "\n\n";

// Test 1: Direct mail send (no queue)
try {
    echo "Test 1: Sending test email directly (no queue)...\n";
    Mail::raw('This is a test email sent directly without queue.', function ($message) {
        $message->to('test@example.com')
                ->subject('Direct Test Email - ' . now()->format('Y-m-d H:i:s'));
    });
    echo "✅ Direct email sent successfully!\n\n";
} catch (\Exception $e) {
    echo "❌ Direct email failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Check if emails are being queued
try {
    echo "Test 2: Checking queue system...\n";
    
    // Get a sample call
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->orderBy('created_at', 'desc')
        ->first();
    
    if ($call) {
        echo "Using call ID: {$call->id}\n";
        
        // Count jobs before
        $jobsBefore = \DB::table('jobs')->count();
        echo "Jobs in queue before: {$jobsBefore}\n";
        
        // Send via queue
        Mail::to('test@example.com')->send(new CallSummaryEmail(
            $call,
            true,
            false,
            'Test from mail system check',
            'internal'
        ));
        
        // Count jobs after
        $jobsAfter = \DB::table('jobs')->count();
        echo "Jobs in queue after: {$jobsAfter}\n";
        
        if ($jobsAfter > $jobsBefore) {
            echo "✅ Email was queued successfully!\n";
            
            // Check job details
            $latestJob = \DB::table('jobs')
                ->orderBy('id', 'desc')
                ->first();
            
            if ($latestJob) {
                $payload = json_decode($latestJob->payload, true);
                echo "Job details:\n";
                echo "- Queue: {$latestJob->queue}\n";
                echo "- Attempts: {$latestJob->attempts}\n";
                echo "- Created: " . date('Y-m-d H:i:s', $latestJob->created_at) . "\n";
                echo "- Job Class: " . ($payload['displayName'] ?? 'Unknown') . "\n";
            }
        } else {
            echo "⚠️  Email might have been sent synchronously or failed to queue\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Queue test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 3: Process any queued jobs
echo "\nTest 3: Processing queued jobs...\n";
$exitCode = \Illuminate\Support\Facades\Artisan::call('queue:work', [
    '--stop-when-empty' => true,
    '--tries' => 1
]);

echo "Queue processing completed with exit code: {$exitCode}\n";

// Check for failed jobs
$failedJobs = \DB::table('failed_jobs')->count();
echo "\nFailed jobs in queue: {$failedJobs}\n";

if ($failedJobs > 0) {
    $latestFailed = \DB::table('failed_jobs')
        ->orderBy('failed_at', 'desc')
        ->first();
    
    if ($latestFailed) {
        echo "\nLatest failed job:\n";
        echo "- Failed at: {$latestFailed->failed_at}\n";
        echo "- Queue: {$latestFailed->queue}\n";
        echo "- Exception: " . substr($latestFailed->exception, 0, 200) . "...\n";
    }
}