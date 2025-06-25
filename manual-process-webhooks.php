<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\WebhookEvent;
use App\Services\WebhookProcessor;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== MANUALLY PROCESSING WEBHOOKS ===\n\n";

try {
    // Get pending webhooks for test number
    $pendingWebhooks = WebhookEvent::where('status', 'pending')
        ->where('provider', 'retell')
        ->where(function($query) {
            $query->whereJsonContains('payload->call->to_number', '+493083793369')
                  ->orWhereJsonContains('payload->call->to_number', '+49 30 837 93 369');
        })
        ->orderBy('created_at', 'asc')
        ->get();
    
    echo "Found " . $pendingWebhooks->count() . " pending webhooks\n\n";
    
    $webhookProcessor = app(WebhookProcessor::class);
    
    foreach ($pendingWebhooks as $webhook) {
        $payload = is_string($webhook->payload) ? json_decode($webhook->payload, true) : $webhook->payload;
        
        echo "Processing Webhook " . $webhook->id . ":\n";
        echo "  - Created: " . $webhook->created_at . "\n";
        echo "  - Event: " . ($webhook->event_type ?: 'unknown') . "\n";
        echo "  - Call ID: " . ($payload['call']['call_id'] ?? $payload['call_id'] ?? 'N/A') . "\n";
        
        try {
            // Mark as processing
            $webhook->update(['status' => 'processing']);
            
            // Process through the webhook processor
            $result = $webhookProcessor->process(
                'retell',
                $payload,
                [],
                $webhook->correlation_id ?? \Str::uuid()
            );
            
            echo "  ✓ Processed successfully\n";
            
            // Check if appointment was created
            if (isset($result['result']['booking_result'])) {
                echo "  - Booking result: " . json_encode($result['result']['booking_result']) . "\n";
            }
            
        } catch (\Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            $webhook->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
        
        echo "\n";
    }
    
    // Check results
    echo "\n=== CHECKING RESULTS ===\n";
    
    $calls = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('to_number', '+493083793369')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
        
    echo "Recent calls to test number: " . $calls->count() . "\n";
    
    foreach ($calls as $call) {
        echo "\nCall " . $call->id . ":\n";
        echo "  - Time: " . $call->created_at . "\n";
        echo "  - From: " . $call->from_number . "\n";
        echo "  - Status: " . $call->status . "\n";
        echo "  - Has Appointment: " . ($call->appointment_id ? "YES" : "NO") . "\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}