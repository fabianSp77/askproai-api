#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Testing Simple Appointment Creation\n";
echo "=====================================\n\n";

// Check appointments table
echo "1. Checking appointments table:\n";
$columns = \Schema::getColumnListing('appointments');
echo "   Columns: " . implode(', ', array_slice($columns, 0, 10)) . "...\n\n";

// Get a call and customer
$call = \App\Models\Call::withoutGlobalScopes()->find(175);
$customer = \App\Models\Customer::withoutGlobalScopes()
    ->where('phone', $call->from_number)
    ->first();

echo "2. Test data:\n";
echo "   Call ID: {$call->id}\n";
echo "   Customer: " . ($customer ? $customer->name : "Not found") . "\n\n";

// Try direct insert
echo "3. Testing direct DB insert:\n";
try {
    $id = \DB::table('appointments')->insertGetId([
        'customer_id' => $customer->id ?? 2,
        'company_id' => 1,
        'branch_id' => $call->branch_id,
        'start_time' => \Carbon\Carbon::tomorrow()->setTime(16, 0),
        'end_time' => \Carbon\Carbon::tomorrow()->setTime(17, 0),
        'status' => 'scheduled',
        'notes' => 'Test appointment',
        'source' => 'test',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "   ✅ Success! Appointment ID: $id\n";
    
    // Link to call
    \DB::table('calls')->where('id', $call->id)->update(['appointment_id' => $id]);
    echo "   ✅ Linked to call\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    
    // Show SQL error details
    $pdo = \DB::connection()->getPdo();
    $errorInfo = $pdo->errorInfo();
    if ($errorInfo[0] !== '00000') {
        echo "   SQL Error: " . print_r($errorInfo, true) . "\n";
    }
}

// Check if appointment was created
echo "\n4. Verification:\n";
$appointment = \DB::table('appointments')->orderBy('id', 'desc')->first();
if ($appointment) {
    echo "   Latest appointment ID: {$appointment->id}\n";
    echo "   Created at: {$appointment->created_at}\n";
}