#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Call;
use Illuminate\Support\Facades\DB;

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting duplicate call merge process...\n";

// Find all temp calls
$tempCalls = Call::where('retell_call_id', 'LIKE', 'temp_%')
    ->orderBy('created_at', 'desc')
    ->get();

echo "Found " . $tempCalls->count() . " temporary calls\n";

$mergedCount = 0;
$notFoundCount = 0;

foreach ($tempCalls as $tempCall) {
    // Find potential matching real call within 5 minutes
    $realCall = Call::where('retell_call_id', 'NOT LIKE', 'temp_%')
        ->whereNotNull('retell_call_id')
        ->where('created_at', '>=', $tempCall->created_at->subMinutes(1))
        ->where('created_at', '<=', $tempCall->created_at->addMinutes(5))
        ->whereNull('company_id') // Real calls that are missing company_id
        ->first();

    if ($realCall) {
        echo "Merging temp call {$tempCall->retell_call_id} with real call {$realCall->retell_call_id}\n";

        // Update real call with temp call's company and phone data
        $realCall->update([
            'company_id' => $tempCall->company_id,
            'phone_number_id' => $tempCall->phone_number_id,
            'from_number' => $tempCall->from_number ?: $realCall->from_number,
            'to_number' => $tempCall->to_number ?: $realCall->to_number,
            'direction' => $tempCall->direction ?: $realCall->direction,
        ]);

        // Delete the temp call
        $tempCall->delete();
        $mergedCount++;
    } else {
        echo "No matching real call found for temp call {$tempCall->retell_call_id} from {$tempCall->created_at}\n";
        $notFoundCount++;
    }
}

echo "\n=== Summary ===\n";
echo "Merged: $mergedCount calls\n";
echo "Not found: $notFoundCount calls\n";

// Also fix any real calls missing company_id by looking at phone_number_id
$callsWithPhoneButNoCompany = Call::whereNotNull('phone_number_id')
    ->whereNull('company_id')
    ->get();

echo "\nFound " . $callsWithPhoneButNoCompany->count() . " calls with phone_number_id but no company_id\n";

foreach ($callsWithPhoneButNoCompany as $call) {
    $phoneNumber = \App\Models\PhoneNumber::find($call->phone_number_id);
    if ($phoneNumber && $phoneNumber->company_id) {
        $call->update(['company_id' => $phoneNumber->company_id]);
        echo "Fixed company_id for call {$call->retell_call_id}\n";
    }
}

echo "\nDone!\n";