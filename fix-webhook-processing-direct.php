<?php

/**
 * Fix Webhook Processing Direct
 * 
 * Direkte Verarbeitung des fehlgeschlagenen Webhooks
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "\n=== Fix Webhook Processing Direct ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Get the failed webhook
echo "1. Getting failed webhook data...\n";
$webhook = DB::table('webhook_events')
    ->where('status', 'failed')
    ->where('created_at', '>', now()->subHour())
    ->orderBy('created_at', 'desc')
    ->first();

if (!$webhook) {
    echo "   No failed webhooks found\n";
    exit;
}

$payload = json_decode($webhook->payload, true);
echo "   Found webhook: " . $webhook->event_type . " at " . $webhook->created_at . "\n";

// 2. Process the webhook manually
if ($webhook->event_type === 'call_ended' && isset($payload['call'])) {
    $callData = $payload['call'];
    
    echo "\n2. Processing call data...\n";
    echo "   Call ID: " . $callData['call_id'] . "\n";
    echo "   From: " . ($callData['from_number'] ?? 'unknown') . "\n";
    echo "   To: " . ($callData['to_number'] ?? 'unknown') . "\n";
    
    // Find company from phone number
    $companyId = 1; // Default
    $toNumber = $callData['to_number'] ?? null;
    
    if ($toNumber) {
        $phoneRecord = DB::table('phone_numbers')
            ->where('number', $toNumber)
            ->orWhere('number', 'like', '%' . substr($toNumber, -10))
            ->first();
            
        if ($phoneRecord) {
            $companyId = $phoneRecord->company_id;
            echo "   Company found: $companyId\n";
        }
    }
    
    // Create or update call record
    echo "\n3. Creating/updating call record...\n";
    
    try {
        // Set company context
        app()->instance('company', Company::find($companyId));
        
        $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->updateOrCreate(
                ['retell_call_id' => $callData['call_id']],
                [
                    'company_id' => $companyId,
                    'call_id' => $callData['call_id'],
                    'agent_id' => $callData['agent_id'] ?? null,
                    'retell_agent_id' => $callData['agent_id'] ?? null,
                    'call_type' => $callData['call_type'] ?? 'inbound',
                    'from_number' => $callData['from_number'] ?? null,
                    'to_number' => $callData['to_number'] ?? null,
                    'direction' => $callData['direction'] ?? 'inbound',
                    'call_status' => 'completed',
                    'start_timestamp' => isset($callData['start_timestamp']) 
                        ? \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp'])
                        : null,
                    'end_timestamp' => isset($callData['end_timestamp'])
                        ? \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp'])
                        : now(),
                    'duration_sec' => $callData['call_analysis']['call_length'] ?? 0,
                    'transcript' => $callData['transcript'] ?? null,
                    'recording_url' => $callData['recording_url'] ?? null,
                    'summary' => $callData['call_analysis']['call_summary'] ?? null,
                    'sentiment' => $callData['call_analysis']['user_sentiment'] ?? null,
                    'session_outcome' => $callData['call_analysis']['call_successful'] ? 'Successful' : 'Unsuccessful',
                    'webhook_data' => $callData,
                    'metadata' => $callData['metadata'] ?? []
                ]
            );
        
        echo "   ✅ Call record created/updated: ID " . $call->id . "\n";
        
        // Mark webhook as completed
        DB::table('webhook_events')
            ->where('id', $webhook->id)
            ->update([
                'status' => 'completed',
                'company_id' => $companyId,
                'error_message' => null,
                'updated_at' => now()
            ]);
            
        echo "   ✅ Webhook marked as completed\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
}

// 4. Check results
echo "\n4. Checking results...\n";
$recentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('created_at', '>', now()->subMinutes(10))
    ->orderBy('created_at', 'desc')
    ->get();

echo "   Recent calls: " . $recentCalls->count() . "\n";
foreach ($recentCalls as $call) {
    echo sprintf("   - %s: %s from %s (%s, %ds)\n",
        $call->created_at->format('H:i:s'),
        substr($call->retell_call_id ?? 'unknown', 0, 20),
        $call->from_number,
        $call->call_status,
        $call->duration_sec
    );
}

echo "\n✅ Processing complete!\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";