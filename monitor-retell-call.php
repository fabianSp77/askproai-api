<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== Monitoring Retell Calls in Real-Time ===\n\n";
echo "Watching for webhook events and appointment data...\n";
echo "Press Ctrl+C to stop\n\n";

$lastWebhookId = \App\Models\WebhookEvent::max('id') ?? 0;
$lastCallId = \App\Models\Call::max('id') ?? 0;

while (true) {
    // Check for new webhook events
    $newWebhooks = \App\Models\WebhookEvent::where('id', '>', $lastWebhookId)
        ->where('provider', 'retell')
        ->orderBy('id', 'asc')
        ->get();
    
    foreach ($newWebhooks as $webhook) {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📨 NEW WEBHOOK EVENT: " . $webhook->event_type . "\n";
        echo "Time: " . $webhook->created_at . "\n";
        
        $payload = $webhook->payload;
        
        if ($webhook->event_type === 'call_inbound') {
            echo "📞 INBOUND CALL:\n";
            echo "   From: " . ($payload['call_inbound']['from_number'] ?? 'unknown') . "\n";
            echo "   To: " . ($payload['call_inbound']['to_number'] ?? 'unknown') . "\n";
        } elseif ($webhook->event_type === 'call_ended') {
            echo "📵 CALL ENDED:\n";
            $call = $payload['call'] ?? [];
            echo "   Call ID: " . ($call['call_id'] ?? 'unknown') . "\n";
            echo "   Duration: " . ($call['call_duration'] ?? 0) . " seconds\n";
            echo "   From: " . ($call['from_number'] ?? 'unknown') . "\n";
            
            // Check for dynamic variables
            if (isset($call['retell_llm_dynamic_variables'])) {
                echo "\n   🔧 DYNAMIC VARIABLES:\n";
                foreach ($call['retell_llm_dynamic_variables'] as $key => $value) {
                    echo "      - $key: $value\n";
                }
            }
            
            // Check for custom function calls
            if (isset($call['transcript_object'])) {
                $functionCalls = [];
                foreach ($call['transcript_object'] as $item) {
                    if (isset($item['tool_calls'])) {
                        foreach ($item['tool_calls'] as $toolCall) {
                            $functionCalls[] = $toolCall['function']['name'] ?? 'unknown';
                        }
                    }
                }
                if (!empty($functionCalls)) {
                    echo "\n   🛠️  FUNCTIONS CALLED: " . implode(', ', array_unique($functionCalls)) . "\n";
                }
            }
        }
        
        $lastWebhookId = $webhook->id;
    }
    
    // Check for new calls
    $newCalls = \App\Models\Call::where('id', '>', $lastCallId)
        ->orderBy('id', 'asc')
        ->get();
    
    foreach ($newCalls as $call) {
        echo "\n" . str_repeat('-', 60) . "\n";
        echo "📞 NEW CALL RECORD:\n";
        echo "   DB ID: " . $call->id . "\n";
        echo "   Retell ID: " . $call->retell_call_id . "\n";
        echo "   From: " . $call->from_number . "\n";
        echo "   Status: " . $call->status . "\n";
        
        if ($call->appointment_id) {
            echo "   ✅ APPOINTMENT CREATED: ID " . $call->appointment_id . "\n";
        }
        
        $lastCallId = $call->id;
    }
    
    // Check Redis for appointment data
    $redis = \Illuminate\Support\Facades\Redis::connection();
    $appointmentKeys = $redis->keys('retell_appointment_data:*');
    
    if (!empty($appointmentKeys)) {
        echo "\n" . str_repeat('-', 60) . "\n";
        echo "📋 CACHED APPOINTMENT DATA:\n";
        foreach ($appointmentKeys as $key) {
            $data = $redis->get($key);
            if ($data) {
                $appointmentData = json_decode($data, true);
                echo "   Call: " . ($appointmentData['call_id'] ?? 'unknown') . "\n";
                echo "   Name: " . ($appointmentData['name'] ?? 'N/A') . "\n";
                echo "   Phone: " . ($appointmentData['telefonnummer'] ?? 'N/A') . "\n";
                echo "   Date: " . ($appointmentData['datum'] ?? 'N/A') . "\n";
                echo "   Time: " . ($appointmentData['uhrzeit'] ?? 'N/A') . "\n";
                echo "   Service: " . ($appointmentData['dienstleistung'] ?? 'N/A') . "\n";
            }
        }
    }
    
    // Sleep for 1 second before checking again
    sleep(1);
    
    // Clear screen every 30 seconds to keep output clean
    static $counter = 0;
    $counter++;
    if ($counter >= 30) {
        echo "\n\n" . str_repeat('=', 60) . "\n";
        echo "Still monitoring... (cleared old output)\n";
        echo str_repeat('=', 60) . "\n";
        $counter = 0;
    }
}