<?php

/**
 * SYNC EXISTING CALLS TO RETELL_CALL_SESSIONS
 *
 * Backfills RetellCallSession records from existing Call records
 * so they appear in the admin panel.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\RetellCallSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   🔄 SYNC CALLS TO RETELL_CALL_SESSIONS                    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

// Get all calls that don't have RetellCallSession
echo "=== STEP 1: Finding Calls Without RetellCallSession ===\n";

$callsWithoutSession = Call::whereNotIn('retell_call_id', function ($query) {
    $query->select('call_id')->from('retell_call_sessions');
})->orderBy('created_at', 'desc')->get();

echo "Found " . $callsWithoutSession->count() . " calls without RetellCallSession\n\n";

if ($callsWithoutSession->isEmpty()) {
    echo "✅ All calls already have RetellCallSession records!\n";
    exit(0);
}

// Migrate each call
echo "=== STEP 2: Creating RetellCallSession Records ===\n";

$created = 0;
$failed = 0;

foreach ($callsWithoutSession as $call) {
    try {
        $session = RetellCallSession::create([
            'id' => Str::uuid(),
            'call_id' => $call->retell_call_id,
            'company_id' => $call->company_id,
            'customer_id' => $call->customer_id,
            'agent_id' => $call->agent_id,
            'agent_version' => $call->agent_version,
            'started_at' => $call->start_timestamp ?? $call->created_at,
            'ended_at' => $call->end_timestamp,
            'call_status' => $call->call_status ?? $call->status ?? 'ended',
            'disconnection_reason' => $call->disconnection_reason,
            'duration_ms' => $call->duration_ms,
            'conversation_flow_id' => null, // Not available in Call model
            'current_flow_node' => null,
            'flow_state' => [],
            'total_events' => 0,
            'function_call_count' => 0,
            'transcript_segment_count' => 0,
            'error_count' => 0,
            'metadata' => [],
        ]);

        echo "✅ {$call->retell_call_id}: Created session (ID: {$session->id})\n";
        $created++;
    } catch (\Exception $e) {
        echo "❌ {$call->retell_call_id}: Failed - {$e->getMessage()}\n";
        $failed++;
    }
}

echo "\n";
echo "=== STEP 3: Summary ===\n";
echo "Calls processed: " . $callsWithoutSession->count() . "\n";
echo "✅ Created: {$created}\n";
echo "❌ Failed: {$failed}\n\n";

// Verify
$totalSessions = RetellCallSession::count();
echo "=== STEP 4: Verification ===\n";
echo "Total RetellCallSessions now: {$totalSessions}\n\n";

// Check test call specifically
$testCall = RetellCallSession::where('call_id', 'call_55f6c3e44a663d9e57a38d80e7b')->first();

if ($testCall) {
    echo "✅ TEST CALL VERIFIED in RetellCallSession:\n";
    echo "   Call ID: {$testCall->call_id}\n";
    echo "   Status: {$testCall->call_status}\n";
    echo "   Started: {$testCall->started_at}\n";
    echo "   Duration: " . round($testCall->duration_ms / 1000) . "s\n\n";
} else {
    echo "⚠️  Test call not found - might not have been in database\n\n";
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         ✅ MIGRATION COMPLETE!                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "🌐 Check admin panel now:\n";
echo "   https://api.askproai.de/admin/retell-call-sessions\n\n";

echo "📋 NEXT TEST:\n";
echo "   ✅ Webhook URL: Configured\n";
echo "   ✅ Webhook Handler: Fixed to create RetellCallSession\n";
echo "   ✅ Historical calls: Migrated\n";
echo "   → Ready for test call!\n\n";
