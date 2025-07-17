<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECK Failed Email Jobs ===\n\n";

// 1. Check failed jobs in last hour
echo "1. Failed Jobs (letzte Stunde):\n";
$failedJobs = \DB::table('failed_jobs')
    ->where('failed_at', '>', now()->subHour())
    ->orderBy('failed_at', 'desc')
    ->get();

if ($failedJobs->count() > 0) {
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        
        // Check if it's an email job
        if (str_contains($payload['displayName'] ?? '', 'Mail') || 
            str_contains($payload['displayName'] ?? '', 'CallSummaryEmail')) {
            
            echo "\n   ===== FAILED EMAIL JOB =====\n";
            echo "   ID: " . $job->id . "\n";
            echo "   Job: " . ($payload['displayName'] ?? 'Unknown') . "\n";
            echo "   Failed at: " . $job->failed_at . "\n";
            echo "   Queue: " . $job->queue . "\n";
            
            // Parse exception
            echo "\n   Exception:\n";
            $exceptionLines = explode("\n", $job->exception);
            foreach (array_slice($exceptionLines, 0, 10) as $line) {
                echo "   " . $line . "\n";
            }
            
            // Check if it's a specific email
            if (isset($payload['data']['command'])) {
                $serialized = $payload['data']['command'];
                try {
                    $command = unserialize($serialized);
                    if ($command && property_exists($command, 'to')) {
                        $to = $command->to;
                        if (is_array($to) && isset($to[0]['address'])) {
                            echo "\n   To: " . $to[0]['address'] . "\n";
                        }
                    }
                } catch (\Exception $e) {
                    echo "   Could not parse command data\n";
                }
            }
        }
    }
} else {
    echo "   ✅ Keine fehlgeschlagenen E-Mail Jobs\n";
}

// 2. Check ProcessCallSummaryEmailJob specifically
echo "\n2. Checking for ProcessCallSummaryEmailJob failures:\n";
$summaryJobFailures = \DB::table('failed_jobs')
    ->where('payload', 'like', '%ProcessCallSummaryEmailJob%')
    ->where('failed_at', '>', now()->subDay())
    ->get();

if ($summaryJobFailures->count() > 0) {
    echo "   ❌ Found " . $summaryJobFailures->count() . " failed ProcessCallSummaryEmailJob\n";
} else {
    echo "   ✅ No ProcessCallSummaryEmailJob failures\n";
}

// 3. Fix the warning issue
echo "\n3. Fixing 'appointment_intent_detected' warning:\n";
$mailClass = app_path('Mail/CallSummaryEmail.php');
if (file_exists($mailClass)) {
    $content = file_get_contents($mailClass);
    $lineNumber = 138;
    $lines = explode("\n", $content);
    if (isset($lines[$lineNumber - 1])) {
        echo "   Line $lineNumber: " . trim($lines[$lineNumber - 1]) . "\n";
        echo "   This should be wrapped in isset() or null coalescing\n";
    }
}

// 4. Test with fixed version
echo "\n4. Testing email with error handling:\n";

$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(227);
if ($call) {
    app()->instance('current_company_id', $call->company_id);
    
    try {
        // Temporarily suppress warnings
        $oldErrorReporting = error_reporting(E_ALL & ~E_WARNING);
        
        \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->send(new \App\Mail\CallSummaryEmail(
            $call,
            true,
            false, // No CSV to make it simpler
            'Failed Jobs Test - ' . now()->format('H:i:s'),
            'internal'
        ));
        
        error_reporting($oldErrorReporting);
        
        echo "   ✅ Email sent successfully (warnings suppressed)\n";
        
    } catch (\Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
        echo "   This might be why portal emails fail!\n";
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Fix the warning in CallSummaryEmail.php line 138\n";
echo "2. Add proper error handling for missing data\n";
echo "3. Check if this warning causes the job to fail silently\n";