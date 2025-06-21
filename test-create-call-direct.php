<?php

// Test creating a call directly in database

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Customer;
use Illuminate\Support\Str;

echo "=== Testing Direct Call Creation ===\n\n";

// Set company context
$companyId = 85;
$branchId = '7362c5a9-7d2b-46cd-9bcb-d69f6a60c73b';

try {
    // 1. Create customer first without phone validation
    echo "1. Creating customer...\n";
    $customer = Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('phone', '+491234567890')
        ->where('company_id', $companyId)
        ->first();
        
    if (!$customer) {
        \DB::statement("INSERT INTO customers (company_id, phone, name, created_via, created_at, updated_at) 
                       VALUES (?, ?, ?, ?, NOW(), NOW())", [
            $companyId,
            '+491234567890',
            'Test Customer',
            'test_script'
        ]);
        
        $customer = Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('phone', '+491234567890')
            ->where('company_id', $companyId)
            ->first();
    }
    
    echo "✅ Customer found/created: " . $customer->id . "\n";
    
    // 2. Create call
    echo "\n2. Creating call...\n";
    $callId = '550e8400-e29b-41d4-a716-446655440001';
    
    // Delete if exists
    \DB::statement("DELETE FROM calls WHERE retell_call_id = ?", [$callId]);
    
    // Insert directly
    \DB::statement("INSERT INTO calls (company_id, branch_id, customer_id, retell_call_id, call_id, 
                                     from_number, to_number, direction, call_status, duration_sec,
                                     transcript, summary, created_at, updated_at) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())", [
        $companyId,
        $branchId,
        $customer->id,
        $callId,
        $callId,
        '+491234567890',
        '+493083793369',
        'inbound',
        'completed',
        120,
        'Test transcript',
        'Test summary'
    ]);
    
    // Verify
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('retell_call_id', $callId)
        ->first();
        
    if ($call) {
        echo "✅ Call created successfully!\n";
        echo "  - ID: " . $call->id . "\n";
        echo "  - Company: " . $call->company_id . "\n";
        echo "  - Branch: " . $call->branch_id . "\n";
        echo "  - Customer: " . $call->customer_id . "\n";
    } else {
        echo "❌ Call creation failed!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}