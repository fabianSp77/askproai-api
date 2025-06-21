<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;

// Set company context
$company = Company::first();
if ($company) {
    app()->bind('current_company_id', function () use ($company) {
        return $company->id;
    });
}

echo "Checking latest call (ID: 99)...\n\n";

$latestCall = Call::find(99);

if ($latestCall) {
    echo "Call Details:\n";
    echo "ID: " . $latestCall->id . "\n";
    echo "Retell Call ID: " . $latestCall->retell_call_id . "\n";
    echo "From: " . $latestCall->from_number . "\n";
    echo "To: " . $latestCall->to_number . "\n";
    echo "Status: " . ($latestCall->status ?: 'N/A') . "\n";
    echo "Duration: " . $latestCall->duration_sec . " seconds\n";
    echo "Start Time: " . $latestCall->start_timestamp . "\n";
    echo "End Time: " . $latestCall->end_timestamp . "\n";
    echo "Created: " . $latestCall->created_at . "\n";
    echo "Updated: " . $latestCall->updated_at . "\n";
    echo "\n";
    
    echo "Extracted Data:\n";
    echo "Name: " . ($latestCall->extracted_name ?: 'N/A') . "\n";
    echo "Email: " . ($latestCall->extracted_email ?: 'N/A') . "\n";
    echo "Date: " . ($latestCall->extracted_date ?: 'N/A') . "\n";
    echo "Time: " . ($latestCall->extracted_time ?: 'N/A') . "\n";
    echo "\n";
    
    echo "Transcript: " . ($latestCall->transcript ? substr($latestCall->transcript, 0, 200) . '...' : 'NOT RECEIVED') . "\n";
    echo "Summary: " . ($latestCall->summary ?: 'NOT RECEIVED') . "\n";
    echo "\n";
    
    if ($latestCall->analysis) {
        echo "Analysis Data:\n";
        $analysis = json_decode($latestCall->analysis, true);
        print_r($analysis);
    }
    
    // Check if this is a complete call
    if ($latestCall->transcript || $latestCall->summary || $latestCall->duration_sec > 0) {
        echo "\n✅ Call appears to be complete with transcript/summary data!\n";
    } else {
        echo "\n⏳ Waiting for call_ended webhook with transcript and summary...\n";
    }
} else {
    echo "Call ID 98 not found.\n";
}

// Also check for any webhooks in the last 5 minutes
echo "\n\nRecent webhook logs:\n";
$recentLogs = DB::table('webhook_logs')
    ->where('created_at', '>=', now()->subMinutes(5))
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($recentLogs->count() > 0) {
    foreach ($recentLogs as $log) {
        echo $log->created_at . " - " . $log->event_type . " - Status: " . $log->status . "\n";
    }
} else {
    echo "No webhook logs found in the last 5 minutes.\n";
}

echo "\nDone!\n";