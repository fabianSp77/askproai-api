<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\WebhookEvent;
use App\Models\Call;
use App\Models\Company;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SIMPLE WEBHOOK PROCESSING ===\n\n";

try {
    // Just mark them as processed and check if we have call data
    $pendingWebhooks = WebhookEvent::where('status', 'pending')
        ->where('provider', 'retell')
        ->where(function($query) {
            $query->whereJsonContains('payload->call->to_number', '+493083793369')
                  ->orWhereJsonContains('payload->call->to_number', '+49 30 837 93 369');
        })
        ->orderBy('created_at', 'asc')
        ->get();
    
    echo "Found " . $pendingWebhooks->count() . " pending webhooks\n\n";
    
    $callIds = [];
    
    foreach ($pendingWebhooks as $webhook) {
        $payload = is_string($webhook->payload) ? json_decode($webhook->payload, true) : $webhook->payload;
        
        $callId = $payload['call']['call_id'] ?? $payload['call_id'] ?? null;
        if ($callId) {
            $callIds[] = $callId;
        }
        
        echo "Webhook " . $webhook->id . ":\n";
        echo "  - Event: " . $webhook->event_type . "\n";
        echo "  - Call ID: " . ($callId ?: 'N/A') . "\n";
        echo "  - From: " . ($payload['call']['from_number'] ?? 'N/A') . "\n";
        
        // Check for appointment data in the webhook
        if (isset($payload['retell_llm_dynamic_variables'])) {
            echo "  - Has dynamic variables!\n";
            $vars = $payload['retell_llm_dynamic_variables'];
            if (isset($vars['datum']) || isset($vars['appointment_data'])) {
                echo "  - ✓ Contains appointment data!\n";
            }
        }
        
        // Mark as processed to prevent re-processing
        $webhook->update(['status' => 'processed']);
        echo "  - Marked as processed\n\n";
    }
    
    // Check if any of these calls made it to our database
    echo "\n=== CHECKING CALL DATABASE ===\n";
    
    $uniqueCallIds = array_unique($callIds);
    echo "Unique call IDs found: " . count($uniqueCallIds) . "\n\n";
    
    foreach ($uniqueCallIds as $callId) {
        $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('retell_call_id', $callId)
            ->first();
            
        if ($call) {
            echo "Call $callId: ✓ Found in database\n";
            echo "  - Created: " . $call->created_at . "\n";
            echo "  - From: " . $call->from_number . "\n";
            echo "  - Status: " . $call->status . "\n";
        } else {
            echo "Call $callId: ✗ NOT in database\n";
        }
    }
    
    // Let's also check if the calls exist in the logs
    echo "\n=== CHECKING LOGS FOR CALL DATA ===\n";
    $logFile = storage_path('logs/laravel.log');
    
    foreach (array_slice($uniqueCallIds, 0, 3) as $callId) {
        echo "\nSearching for $callId in logs...\n";
        $grepResult = `grep -i "$callId" $logFile | tail -n 5`;
        if ($grepResult) {
            echo "Found in logs:\n";
            echo substr($grepResult, 0, 500) . "...\n";
        } else {
            echo "Not found in logs\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}