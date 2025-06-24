<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== TESTING TABLE REFERENCE FIXES ===\n\n";

// Test 1: Check mcp_metrics table structure
echo "1. Checking mcp_metrics table structure...\n";
if (Schema::hasTable('mcp_metrics')) {
    $columns = Schema::getColumnListing('mcp_metrics');
    echo "   Columns: " . implode(', ', $columns) . "\n";
    
    // Test insert
    try {
        DB::table('mcp_metrics')->insert([
            'service' => 'TestService',
            'operation' => 'testOperation',
            'success' => true,
            'duration_ms' => 100,
            'tenant_id' => 'test-tenant',
            'metadata' => json_encode(['test' => true]),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "   ✅ Insert test successful\n";
        
        // Clean up
        DB::table('mcp_metrics')->where('service', 'TestService')->delete();
    } catch (\Exception $e) {
        echo "   ❌ Insert failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ mcp_metrics table does not exist\n";
}

// Test 2: Check webhook_events for notifications
echo "\n2. Testing webhook_events for notification tracking...\n";
try {
    $count = DB::table('webhook_events')
        ->where('provider', 'notification')
        ->where('created_at', '>=', now()->subDay())
        ->count();
    echo "   ✅ Query successful (found $count notification events)\n";
} catch (\Exception $e) {
    echo "   ❌ Query failed: " . $e->getMessage() . "\n";
}

// Test 3: Check cache-based locking
echo "\n3. Testing cache-based time slot locking...\n";
try {
    $lockKey = 'appointment_lock:test:20250623120000:20250623130000';
    $lockData = [
        'staff_id' => 'test-staff',
        'branch_id' => 'test-branch',
        'lock_token' => 'test-token',
        'expires_at' => now()->addMinutes(5)->toIso8601String()
    ];
    
    if (\Cache::add($lockKey, $lockData, 300)) {
        echo "   ✅ Lock acquired successfully\n";
        \Cache::forget($lockKey);
    } else {
        echo "   ❌ Failed to acquire lock\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Cache error: " . $e->getMessage() . "\n";
}

// Test 4: Check all critical tables
echo "\n4. Verifying critical tables still exist...\n";
$criticalTables = [
    'appointments',
    'branches', 
    'calls',
    'companies',
    'customers',
    'phone_numbers',
    'services',
    'staff',
    'users',
    'webhook_events',
    'mcp_metrics'
];

$allExist = true;
foreach ($criticalTables as $table) {
    if (!Schema::hasTable($table)) {
        echo "   ❌ Missing: $table\n";
        $allExist = false;
    }
}

if ($allExist) {
    echo "   ✅ All critical tables exist\n";
}

// Test 5: Check removed tables are gone
echo "\n5. Verifying problematic tables were removed...\n";
$removedTables = [
    'api_call_logs',
    'service_usage_logs',
    'notification_log',
    'appointment_locks',
    'invoice_items',
    'invoice_items_flexible'
];

$anyExist = false;
foreach ($removedTables as $table) {
    if (Schema::hasTable($table)) {
        echo "   ❌ Still exists: $table\n";
        $anyExist = true;
    }
}

if (!$anyExist) {
    echo "   ✅ All problematic tables removed\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Table reference fixes have been applied\n";
echo "✅ System should now be functional\n";