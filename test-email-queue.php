#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Jobs\SendCallSummaryJob;
use Illuminate\Support\Facades\Log;

// Get a call
$call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('id', 277)
    ->first();

if (!$call) {
    echo "No call found\n";
    exit(1);
}

echo "Found call ID: {$call->id}\n";
echo "Company ID: {$call->company_id}\n";
echo "Created at: {$call->created_at}\n\n";

// Dispatch email job
try {
    echo "Dispatching email job...\n";
    SendCallSummaryJob::dispatch($call);
    echo "✅ Email job dispatched successfully!\n";
    
    // Check queue status
    $redis = app('redis');
    $emailsCount = $redis->llen('queues:emails');
    echo "\nEmails queue count: {$emailsCount}\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}