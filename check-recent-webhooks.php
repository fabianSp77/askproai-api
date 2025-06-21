<?php

// Check for recent webhook activity
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use Carbon\Carbon;

echo "Checking for recent call activity...\n\n";

// Set company context
$company = Company::first();
if ($company) {
    app()->bind('current_company_id', function () use ($company) {
        return $company->id;
    });
}

// Check calls from last hour
$recentCalls = Call::where('created_at', '>=', Carbon::now()->subHour())
    ->orderBy('created_at', 'desc')
    ->get();

echo "Calls in last hour: " . $recentCalls->count() . "\n\n";

foreach ($recentCalls as $call) {
    echo "Call ID: " . $call->id . "\n";
    echo "Retell Call ID: " . $call->retell_call_id . "\n";
    echo "From: " . $call->from_number . "\n";
    echo "Status: " . $call->status . "\n";
    echo "Duration: " . $call->duration_sec . " seconds\n";
    echo "Has Transcript: " . ($call->transcript ? 'YES' : 'NO') . "\n";
    echo "Has Summary: " . ($call->summary ? 'YES' : 'NO') . "\n";
    echo "Created: " . $call->created_at . "\n";
    echo "Updated: " . $call->updated_at . "\n";
    echo str_repeat('-', 50) . "\n";
}

// Check specific call
$callId = 'call_43b4ecb8206b29cdef036ca03ea';
$specificCall = Call::where('call_id', $callId)
    ->orWhere('retell_call_id', $callId)
    ->first();

if ($specificCall) {
    echo "\nFound your test call:\n";
    echo "Database ID: " . $specificCall->id . "\n";
    echo "Status: " . $specificCall->status . "\n";
    echo "Transcript: " . ($specificCall->transcript ?: 'Not yet received') . "\n";
    echo "Summary: " . ($specificCall->summary ?: 'Not yet received') . "\n";
    
    if ($specificCall->analysis) {
        echo "\nAnalysis Data:\n";
        print_r(json_decode($specificCall->analysis, true));
    }
}

echo "\nDone!\n";