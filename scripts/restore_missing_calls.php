#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\PhoneNumber;

echo "Restoring missing test calls from today...\n";

// Find AskProAI phone number
$phoneNumber = PhoneNumber::where('number', '+493083793369')->first();
$phoneNumberId = $phoneNumber ? $phoneNumber->id : null;
$companyId = $phoneNumber ? $phoneNumber->company_id : 15;

echo "AskProAI: Company ID = $companyId, Phone Number ID = $phoneNumberId\n";

// Missing calls from logs
$missingCalls = [
    [
        'retell_call_id' => 'call_821f7addd4c0f23dd0a45f9379a',
        'from_number' => 'anonymous',
        'to_number' => '+493083793369',
        'created_at' => '2025-09-28 12:19:54',
        'status' => 'completed',
        'direction' => 'inbound',
        'notes' => 'Hans Schuster - Beratung am 01.10.2025 um 11:00'
    ],
    [
        'retell_call_id' => 'call_7c06460211169536a275933ca7a',
        'from_number' => 'anonymous',
        'to_number' => '+493083793369',
        'created_at' => '2025-09-28 12:24:59',
        'status' => 'completed',
        'direction' => 'inbound',
        'notes' => 'Hans Schuster - Beratung am 01.10.2025 um 11:00'
    ],
    [
        'retell_call_id' => 'call_9230fe0d70f76b44bbdf639c8b8',
        'from_number' => 'anonymous',
        'to_number' => '+493083793369',
        'created_at' => '2025-09-28 12:31:40',
        'status' => 'completed',
        'direction' => 'inbound',
        'notes' => 'Hans Schulze - Beratung am 01.10.2025 um 11:00'
    ],
    [
        'retell_call_id' => 'call_5594a2c32c079032a7825d666a7',
        'from_number' => 'anonymous',
        'to_number' => '+493083793369',
        'created_at' => '2025-09-28 13:23:28',
        'status' => 'completed',
        'direction' => 'inbound',
        'notes' => 'Hans Schuster - Beratung am 01.10.2025 um 11:00'
    ],
    [
        'retell_call_id' => 'call_0e4133ea8ddb59bc54f3fbfef7a',
        'from_number' => 'anonymous',
        'to_number' => '+493083793369',
        'created_at' => '2025-09-28 13:30:04',
        'status' => 'completed',
        'direction' => 'inbound',
        'notes' => 'Tomshoster - Beratung am 01.10.2025 um 11:00'
    ],
    [
        'retell_call_id' => 'call_d137a2873517fc1167b7678cae6',
        'from_number' => 'anonymous',
        'to_number' => '+493083793369',
        'created_at' => '2025-09-28 13:40:46',
        'status' => 'completed',
        'direction' => 'inbound',
        'notes' => 'Hans Schuster - Beratung am 01.10.2025 um 11:00'
    ]
];

$created = 0;
$skipped = 0;

foreach ($missingCalls as $callData) {
    // Check if call already exists
    $existing = Call::where('retell_call_id', $callData['retell_call_id'])->first();

    if ($existing) {
        echo "Call {$callData['retell_call_id']} already exists, skipping\n";
        $skipped++;
        continue;
    }

    // Create the call
    $call = Call::create([
        'retell_call_id' => $callData['retell_call_id'],
        'call_id' => $callData['retell_call_id'],
        'from_number' => $callData['from_number'],
        'to_number' => $callData['to_number'],
        'phone_number_id' => $phoneNumberId,
        'company_id' => $companyId,
        'status' => $callData['status'],
        'direction' => $callData['direction'],
        'appointment_made' => true,
        'call_successful' => true,
        'notes' => $callData['notes'],
        'created_at' => $callData['created_at'],
        'updated_at' => $callData['created_at']
    ]);

    echo "Created call: {$callData['retell_call_id']} from {$callData['created_at']}\n";
    $created++;
}

echo "\n=== Summary ===\n";
echo "Created: $created calls\n";
echo "Skipped: $skipped calls (already existed)\n";

// Check final count
$totalCalls = Call::count();
$todayCalls = Call::whereDate('created_at', '2025-09-28')->count();

echo "\nTotal calls in database: $totalCalls\n";
echo "Calls from today (2025-09-28): $todayCalls\n";