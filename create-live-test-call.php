<?php

/**
 * Create Live Test Call
 * 
 * Erstellt direkt einen laufenden Testanruf
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Events\CallCreated;

echo "\n=== Create Live Test Call ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

$company = Company::first();
if (!$company) {
    echo "❌ No company found\n";
    exit(1);
}

// Create a live call directly
echo "1. Creating live call in database...\n";

try {
    $callId = 'live_test_' . uniqid();
    
    $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->create([
        'company_id' => $company->id,
        'retell_call_id' => $callId,
        'call_id' => $callId,
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'retell_agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'inbound',
        'from_number' => '+49151' . rand(10000000, 99999999),
        'to_number' => '+498912345678',
        'direction' => 'inbound',
        'call_status' => 'in_progress',
        'start_timestamp' => now(),
        'end_timestamp' => null,  // WICHTIG: NULL für aktive Anrufe
        'duration_sec' => 0,
        'session_outcome' => 'In Progress',
        'metadata' => [
            'source' => 'live_test',
            'test' => true
        ],
        'webhook_data' => [
            'test_call' => true,
            'created_by' => 'create-live-test-call.php'
        ]
    ]);
    
    echo "   ✅ Call created successfully!\n";
    echo "   - Call ID: {$call->retell_call_id}\n";
    echo "   - From: {$call->from_number}\n";
    echo "   - Status: {$call->call_status}\n";
    echo "   - Start: {$call->start_timestamp}\n";
    echo "   - End: " . ($call->end_timestamp ?? 'NULL - ACTIVE!') . "\n";
    
    // Broadcast event for real-time update
    event(new CallCreated($call));
    echo "   ✅ CallCreated event broadcasted\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error creating call: " . $e->getMessage() . "\n";
    echo "   " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n2. Verifying active calls...\n";
$activeCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->whereNull('end_timestamp')
    ->where('created_at', '>', now()->subHours(2))
    ->get();

echo "   Found " . $activeCalls->count() . " active calls:\n";
foreach ($activeCalls as $activeCall) {
    $duration = $activeCall->start_timestamp ? now()->diffInSeconds($activeCall->start_timestamp) : 0;
    echo sprintf("   - %s from %s (%ds)\n",
        $activeCall->retell_call_id,
        $activeCall->from_number,
        $duration
    );
}

echo "\n3. Simulating call progress...\n";
echo "   Call will remain active until manually ended\n";
echo "   To end the call, run: php end-test-call.php {$call->id}\n";

echo "\n=== SUCCESS ===\n";
echo "✅ Live call created and should be visible in the widget!\n";
echo "🔗 Go to: https://api.askproai.de/admin/calls\n";
echo "📞 Look for call from: {$call->from_number}\n";

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";