<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TRACE Mail Disappearance ===\n\n";

// 1. Check mail configuration
echo "1. Mail Configuration:\n";
echo "   Default mailer: " . config('mail.default') . "\n";
echo "   Resend driver configured: " . (config('mail.mailers.resend') ? 'YES' : 'NO') . "\n";
echo "   Queue connection: " . config('queue.default') . "\n";
echo "   Mail queue: " . config('mail.queue') . "\n\n";

// 2. Check if mail is actually configured to use queue
echo "2. Checking Mailable configuration:\n";
$mailableFile = app_path('Mail/CallSummaryEmail.php');
$content = file_get_contents($mailableFile);

// Check if it implements ShouldQueue
if (str_contains($content, 'implements ShouldQueue')) {
    echo "   ✅ CallSummaryEmail implements ShouldQueue\n";
} else {
    echo "   ❌ CallSummaryEmail does NOT implement ShouldQueue\n";
}

// Check queue connection
if (preg_match('/\$connection\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
    echo "   Queue connection: " . $matches[1] . "\n";
} else {
    echo "   Queue connection: default\n";
}

// 3. Test queue job processing with detailed logging
echo "\n3. Creating test email with detailed tracing:\n";

// Add temporary event listeners
\Illuminate\Support\Facades\Event::listen('Illuminate\Mail\Events\*', function ($eventName, $data) {
    echo "   [EVENT] " . class_basename($eventName) . "\n";
});

\Illuminate\Support\Facades\Queue::before(function ($event) {
    echo "   [QUEUE] Processing job: " . $event->job->resolveName() . "\n";
});

\Illuminate\Support\Facades\Queue::after(function ($event) {
    echo "   [QUEUE] Completed job: " . $event->job->resolveName() . "\n";
});

\Illuminate\Support\Facades\Queue::failing(function ($event) {
    echo "   [QUEUE] FAILED job: " . $event->job->resolveName() . "\n";
    echo "   Exception: " . $event->exception->getMessage() . "\n";
});

// Clear activities
$callId = 227;
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', $callId)
    ->where('activity_type', 'email_sent')
    ->delete();

$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
app()->instance('current_company_id', $call->company_id);

// Try queueing
try {
    echo "\n4. Queueing email:\n";
    
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        false,
        'Trace Test - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "   ✅ Queued\n";
    
    // Process immediately
    echo "\n5. Processing queue manually:\n";
    $exitCode = \Illuminate\Support\Facades\Artisan::call('queue:work', [
        '--once' => true,
        '--queue' => 'default,emails',
        '--stop-when-empty' => true
    ]);
    
    $output = \Illuminate\Support\Facades\Artisan::output();
    echo $output;
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

// 6. Check database for failed jobs
echo "\n6. Checking failed_jobs table:\n";
$failed = \DB::table('failed_jobs')
    ->where('failed_at', '>', now()->subMinute())
    ->first();

if ($failed) {
    echo "   ❌ FOUND FAILED JOB!\n";
    $payload = json_decode($failed->payload, true);
    echo "   Job: " . ($payload['displayName'] ?? 'Unknown') . "\n";
    echo "   Exception:\n" . $failed->exception . "\n";
} else {
    echo "   ✅ No failed jobs\n";
}

// 7. Check if sync driver is being used
echo "\n7. Testing with SYNC driver:\n";
config(['mail.queue' => null]); // Force sync

try {
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        false,
        'Sync Test - ' . now()->format('H:i:s'),
        'internal'
    ));
    echo "   ✅ Sync send completed\n";
} catch (\Exception $e) {
    echo "   ❌ Sync send failed: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNOSIS ===\n";
echo "The email is disappearing because:\n";
echo "1. It might be using a different mail driver when queued\n";
echo "2. The job might be failing silently\n";
echo "3. The Mailable might not be configured correctly\n";