#!/usr/bin/env php
<?php

/**
 * Debug script to understand why collect-data fails
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Scopes\TenantScope;
use Illuminate\Support\Facades\Log;

// Test data from the failed request
$callId = 'call_a41d3d15a7e41fc075945c4904d';

echo "Debugging collect-data issue\n";
echo "===========================\n\n";

// Enable logging
Log::info('Debug: Starting collect-data debugging', ['call_id' => $callId]);

// Step 1: Check if call exists
echo "Step 1: Check if call exists\n";
$existingCall = Call::withoutGlobalScope(TenantScope::class)
    ->where('retell_call_id', $callId)
    ->first();

if ($existingCall) {
    echo "✓ Call exists in database\n";
    echo "- ID: {$existingCall->id}\n";
    echo "- Created: {$existingCall->created_at}\n";
    echo "- From: {$existingCall->from_number}\n";
    echo "- Status: {$existingCall->status}\n";
    
    // Check metadata
    $metadata = $existingCall->metadata ?? [];
    echo "- Has customer data: " . (isset($metadata['customer_data']) ? 'Yes' : 'No') . "\n";
    
    if (isset($metadata['customer_data'])) {
        echo "- Customer name: " . ($metadata['customer_data']['full_name'] ?? 'N/A') . "\n";
    }
} else {
    echo "✗ Call does not exist\n";
}

// Step 2: Simulate the controller logic
echo "\nStep 2: Simulate controller logic\n";

// The actual phone number placeholder
$phoneFromRequest = '{{caller_phone_number}}';
$actualPhone = '+491604366218'; // From the call record

echo "- Phone from request: $phoneFromRequest\n";
echo "- Actual phone: $actualPhone\n";

// Check placeholder replacement
if ($phoneFromRequest === '{{caller_phone_number}}') {
    echo "✓ Placeholder detected correctly\n";
} else {
    echo "✗ Placeholder not detected\n";
}

// Step 3: Test creating a new call (what happens in the error)
echo "\nStep 3: Test what happens when creating duplicate\n";

try {
    $newCall = Call::withoutGlobalScope(TenantScope::class)->create([
        'retell_call_id' => $callId,
        'from_number' => $phoneFromRequest,
        'to_number' => 'unknown',
        'status' => 'completed',
        'duration_sec' => 0,
        'company_id' => 1,
        'branch_id' => 1
    ]);
    echo "✗ Unexpectedly created duplicate call!\n";
} catch (\Exception $e) {
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        echo "✓ Got expected duplicate entry error\n";
        echo "- Error: " . substr($e->getMessage(), 0, 100) . "...\n";
    } else {
        echo "✗ Got unexpected error: " . $e->getMessage() . "\n";
    }
}

// Step 4: Check company/branch resolution
echo "\nStep 4: Check company/branch resolution\n";
$phoneRecord = \App\Models\PhoneNumber::where('number', 'LIKE', '%' . substr($actualPhone, -8) . '%')
    ->first();
    
if ($phoneRecord) {
    echo "✓ Found phone number record\n";
    echo "- Company ID: {$phoneRecord->company_id}\n";
    echo "- Branch ID: {$phoneRecord->branch_id}\n";
} else {
    echo "✗ No phone number record found\n";
}

echo "\nDebugging complete.\n";