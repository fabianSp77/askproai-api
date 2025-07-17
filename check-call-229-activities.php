<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\CallActivity;

// Get call 229
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(229);

if (!$call) {
    echo "Call 229 not found.\n";
    exit(1);
}

echo "Call ID: {$call->id}\n";
echo "Company ID: {$call->company_id}\n";
echo "Created: {$call->created_at}\n\n";

// Set company context
app()->instance('current_company_id', $call->company_id);

// Get activities
$activities = CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 229)
    ->orderBy('created_at', 'desc')
    ->get();

echo "Activities found: " . $activities->count() . "\n\n";

foreach ($activities as $activity) {
    echo "Activity ID: {$activity->id}\n";
    echo "Type: {$activity->activity_type}\n";
    echo "Title: {$activity->title}\n";
    echo "Description: {$activity->description}\n";
    echo "Created: {$activity->created_at}\n";
    echo "User ID: {$activity->user_id}\n";
    
    if ($activity->metadata) {
        echo "Metadata:\n";
        foreach ($activity->metadata as $key => $value) {
            if (is_array($value)) {
                echo "  - {$key}: " . json_encode($value) . "\n";
            } else {
                echo "  - {$key}: {$value}\n";
            }
        }
    }
    echo "---\n\n";
}

// Check if there are any jobs in the queue related to this call
echo "\nChecking mail queue...\n";
$jobs = \DB::table('jobs')->get();
echo "Total jobs in queue: " . $jobs->count() . "\n";

// Check failed jobs
$failedJobs = \DB::table('failed_jobs')
    ->where('payload', 'like', '%229%')
    ->get();
    
echo "Failed jobs mentioning call 229: " . $failedJobs->count() . "\n";

// Check mail log
$mailLogPath = storage_path('logs/mail.log');
if (file_exists($mailLogPath)) {
    echo "\nMail log exists, last 10 lines:\n";
    $lines = array_slice(file($mailLogPath), -10);
    foreach ($lines as $line) {
        echo $line;
    }
}