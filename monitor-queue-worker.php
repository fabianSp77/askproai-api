<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== MONITOR Queue Worker ===\n\n";

// 1. Add event listeners
echo "1. Adding queue event listeners:\n";

$events = app('events');

$events->listen('Illuminate\Queue\Events\JobProcessing', function ($event) {
    echo "[QUEUE] Processing: " . $event->job->resolveName() . "\n";
    \Log::info('[QUEUE] Processing job', [
        'job' => $event->job->resolveName(),
        'queue' => $event->job->getQueue(),
        'attempts' => $event->job->attempts()
    ]);
});

$events->listen('Illuminate\Queue\Events\JobProcessed', function ($event) {
    echo "[QUEUE] Processed: " . $event->job->resolveName() . "\n";
    \Log::info('[QUEUE] Job processed successfully');
});

$events->listen('Illuminate\Queue\Events\JobFailed', function ($event) {
    echo "[QUEUE] FAILED: " . $event->job->resolveName() . "\n";
    echo "Exception: " . $event->exception->getMessage() . "\n";
    \Log::error('[QUEUE] Job failed', [
        'exception' => $event->exception->getMessage()
    ]);
});

// Mail events
$events->listen('Illuminate\Mail\Events\MessageSending', function ($event) {
    echo "[MAIL] Sending message to: " . implode(', ', array_keys($event->message->getTo())) . "\n";
    \Log::info('[MAIL] Message sending', [
        'to' => array_keys($event->message->getTo()),
        'subject' => $event->message->getSubject()
    ]);
});

$events->listen('Illuminate\Mail\Events\MessageSent', function ($event) {
    echo "[MAIL] Message sent!\n";
    \Log::info('[MAIL] Message sent successfully');
});

// 2. Clear any old jobs
$redis = app('redis');
$redis->del("queues:default");

// 3. Queue a test email
$callId = 228;
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
app()->instance('current_company_id', $call->company_id);

echo "\n2. Queueing test email:\n";

\Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue(new \App\Mail\CallSummaryEmail(
    $call,
    true,
    false,
    'Worker Monitor Test - ' . now()->format('H:i:s'),
    'internal'
));

echo "   ✅ Email queued\n";

// 4. Process with verbose output
echo "\n3. Processing queue with monitoring:\n";

// Use Artisan to process
\Illuminate\Support\Facades\Artisan::call('queue:work', [
    '--once' => true,
    '--queue' => 'default',
    '-vvv' => true // Verbose output
]);

$output = \Illuminate\Support\Facades\Artisan::output();
echo "Artisan output:\n" . $output . "\n";

// 5. Check what happened
echo "\n4. Checking results:\n";

$log = file_get_contents(storage_path('logs/laravel.log'));
$lines = explode("\n", $log);
$recentLines = array_slice($lines, -20);

$mailLogs = 0;
$queueLogs = 0;
$resendLogs = 0;

foreach ($recentLines as $line) {
    if (str_contains($line, '[MAIL]')) {
        echo "   " . $line . "\n";
        $mailLogs++;
    }
    if (str_contains($line, '[QUEUE]')) {
        echo "   " . $line . "\n";
        $queueLogs++;
    }
    if (str_contains($line, '[ResendTransport]')) {
        echo "   " . $line . "\n";
        $resendLogs++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Queue logs: $queueLogs\n";
echo "Mail logs: $mailLogs\n";
echo "ResendTransport logs: $resendLogs\n";

if ($mailLogs == 0) {
    echo "\n❌ NO MAIL EVENTS FIRED!\n";
    echo "The queue job is not actually sending emails!\n";
} else {
    echo "\n✅ Mail events fired\n";
}