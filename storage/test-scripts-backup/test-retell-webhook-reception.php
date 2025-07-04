<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

echo "=== Retell Webhook Reception Test ===\n\n";

// Configuration
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';
$webhookSecret = env('RETELL_WEBHOOK_SECRET', 'key_6ff998ba48e842092e04a5455d19');

// Test data
$callId = 'test_call_' . uniqid();
$timestamp = round(microtime(true) * 1000); // Milliseconds

// Create test payload for call_ended event
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => $callId,
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'call_type' => 'web_call',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'call_status' => 'ended',
        'metadata' => [
            'test' => true,
            'source' => 'webhook_test_script'
        ],
        'retell_llm_dynamic_variables' => [
            'customer_name' => 'Test Customer',
            'appointment_time' => '2025-06-27 14:00'
        ],
        'transcript' => 'Agent: Guten Tag, wie kann ich Ihnen helfen?\nCustomer: Ich möchte einen Termin vereinbaren.\nAgent: Gerne, wann hätten Sie Zeit?\nCustomer: Morgen um 14 Uhr.\nAgent: Perfekt, ich habe Sie für morgen 14 Uhr eingetragen.',
        'transcript_with_tool_calls' => 'Agent: Guten Tag, wie kann ich Ihnen helfen?\nCustomer: Ich möchte einen Termin vereinbaren.\n[Tool Call: check_availability]\nAgent: Gerne, wann hätten Sie Zeit?\nCustomer: Morgen um 14 Uhr.\n[Tool Call: book_appointment]\nAgent: Perfekt, ich habe Sie für morgen 14 Uhr eingetragen.',
        'call_analysis' => [
            'call_summary' => 'Customer called to book an appointment for tomorrow at 2 PM.',
            'in_voicemail' => false,
            'user_sentiment' => 'positive',
            'call_successful' => true,
            'custom_analysis_data' => [
                'call_successful' => true,
                'appointment_made' => true,
                'appointment_date_time' => '2025-06-27 14:00',
                'caller_full_name' => 'Test Customer',
                'caller_phone' => '+491234567890',
                'patient_full_name' => 'Test Customer',
                'patient_birth_date' => '1990-01-01',
                'insurance_type' => 'gesetzlich',
                'health_insurance_company' => 'AOK',
                'reason_for_visit' => 'Routine Checkup',
                'first_visit' => false,
                'urgency_level' => 'routine',
                'additional_notes' => 'Test appointment from webhook test',
                'no_show_count' => '0',
                'reschedule_count' => '0'
            ]
        ],
        'recording_url' => null,
        'public_log_url' => 'https://app.retellai.com/calls/' . $callId,
        'e2e_latency' => [
            'p50' => 800,
            'p90' => 1200,
            'p95' => 1500,
            'p99' => 2000,
            'max' => 2500,
            'min' => 500,
            'num' => 10
        ],
        'disconnection_reason' => 'user_hung_up',
        'call_cost' => 0.5,
        'call_cost_breakdown' => [
            'llm' => 0.3,
            'tts' => 0.1,
            'stt' => 0.05,
            'telephony' => 0.05,
            'vapi' => 0
        ],
        'duration_ms' => 120000, // 2 minutes
        'start_timestamp' => $timestamp - 120000,
        'end_timestamp' => $timestamp
    ]
];

// Create signature
$body = json_encode($payload);
$signaturePayload = "{$timestamp}.{$body}";
$signature = hash_hmac('sha256', $signaturePayload, $webhookSecret);

// Combined format that Retell uses
$signatureHeader = "v={$timestamp},d={$signature}";

echo "1. Test Configuration:\n";
echo "   Webhook URL: $webhookUrl\n";
echo "   Call ID: $callId\n";
echo "   Timestamp: $timestamp\n";
echo "   Signature: $signatureHeader\n\n";

echo "2. Sending webhook request...\n";

try {
    // Send webhook request
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'X-Retell-Signature' => $signatureHeader,
        'X-Retell-Timestamp' => (string)$timestamp,
        'User-Agent' => 'Retell-Webhook/1.0'
    ])->post($webhookUrl, $payload);
    
    echo "   Response Status: " . $response->status() . "\n";
    echo "   Response Body: " . $response->body() . "\n\n";
    
    if ($response->successful()) {
        echo "✅ Webhook request successful!\n\n";
        
        // Check database for the call record
        echo "3. Checking database for call record...\n";
        
        sleep(2); // Give it time to process
        
        $call = \App\Models\Call::where('retell_call_id', $callId)->first();
        
        if ($call) {
            echo "✅ Call record found in database!\n";
            echo "   ID: " . $call->id . "\n";
            echo "   Status: " . $call->status . "\n";
            echo "   From: " . $call->from_number . "\n";
            echo "   To: " . $call->to_number . "\n";
            echo "   Duration: " . $call->duration . " seconds\n";
            echo "   Created: " . $call->created_at . "\n";
            
            // Check for analysis data
            if ($call->analysis_data) {
                echo "\n   Analysis Data:\n";
                $analysisData = is_string($call->analysis_data) ? json_decode($call->analysis_data, true) : $call->analysis_data;
                foreach ($analysisData as $key => $value) {
                    echo "     - $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
                }
            }
        } else {
            echo "❌ Call record NOT found in database\n";
            echo "   This might mean the webhook was not processed correctly\n";
        }
        
        // Check webhook_events table
        echo "\n4. Checking webhook_events table...\n";
        
        $webhookEvent = \App\Models\WebhookEvent::where('provider', 'retell')
            ->whereJsonContains('payload->call->call_id', $callId)
            ->first();
            
        if ($webhookEvent) {
            echo "✅ Webhook event found!\n";
            echo "   ID: " . $webhookEvent->id . "\n";
            echo "   Status: " . $webhookEvent->status . "\n";
            echo "   Event Type: " . $webhookEvent->event_type . "\n";
            echo "   Processed At: " . $webhookEvent->processed_at . "\n";
        } else {
            echo "❌ Webhook event NOT found in webhook_events table\n";
        }
        
    } else {
        echo "❌ Webhook request failed!\n";
        echo "   This could mean:\n";
        echo "   - Signature verification failed\n";
        echo "   - Server error\n";
        echo "   - Invalid payload\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error sending webhook: " . $e->getMessage() . "\n";
}

echo "\n5. Recent logs check...\n";
$recentLogs = shell_exec("tail -30 /var/www/api-gateway/storage/logs/laravel.log | grep -i 'retell\\|webhook' | tail -10");
if ($recentLogs) {
    echo "Recent webhook logs:\n";
    echo $recentLogs;
} else {
    echo "No recent webhook logs found\n";
}

echo "\n=== Test Complete ===\n";