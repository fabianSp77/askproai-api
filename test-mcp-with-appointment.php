<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Enable debug logging
\Illuminate\Support\Facades\Log::channel('single')->info('=== Starting MCP Webhook Test with Appointment ===');

try {
    $webhookMCP = $app->make(\App\Services\MCP\WebhookMCPServer::class);
    
    echo "=== Testing MCP Webhook with Appointment Booking ===\n\n";
    
    // Test webhook data with appointment booking
    $webhookData = [
        'event' => 'call_ended',
        'call' => [
            'call_id' => 'appointment-test-' . uniqid(),
            'agent_id' => 'agent_9a8202a740cd3120d96fc27bb40b2c',
            'from_number' => '+491234567890',
            'to_number' => '+493083793369',
            'direction' => 'inbound',
            'duration_ms' => 180000,
            'cost' => 0.30,
            'status' => 'ended',
            'transcript' => 'Kunde möchte Termin am Donnerstag um 15 Uhr',
            'summary' => 'Terminbuchung für Haarschnitt',
            'end_timestamp' => time() * 1000,
            'start_timestamp' => (time() - 180) * 1000,
            'call_analysis' => [
                'custom_analysis_data' => [
                    '_name' => 'Test Kunde MCP',
                    '_datum__termin' => '2025-06-27',
                    '_uhrzeit__termin' => '18:00',
                    '_email' => 'test-mcp@example.com'
                ]
            ],
            'retell_llm_dynamic_variables' => [
                'booking_confirmed' => 'true',
                'datum' => '2025-06-27',
                'uhrzeit' => '18:00',
                'name' => 'Test Kunde MCP',
                'dienstleistung' => 'Haarschnitt',
                'kundenwunsch' => 'Kurzer moderner Schnitt'
            ]
        ]
    ];
    
    echo "Processing webhook with appointment booking...\n";
    echo "Date/Time: 2025-06-27 18:00\n";
    echo "Customer: Test Kunde MCP\n\n";
    
    // Process the webhook
    $result = $webhookMCP->processRetellWebhook($webhookData);
    
    echo "Result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($result['success'] ?? false) {
        echo "✅ Webhook processed successfully!\n";
        echo "   - Call ID: " . ($result['call_id'] ?? 'N/A') . "\n";
        echo "   - Customer ID: " . ($result['customer_id'] ?? 'N/A') . "\n";
        
        if ($result['appointment_created'] ?? false) {
            echo "✅ APPOINTMENT CREATED!\n";
            if (isset($result['appointment_data'])) {
                echo "   - Appointment ID: " . $result['appointment_data']['appointment_id'] . "\n";
                echo "   - Cal.com Booking ID: " . ($result['appointment_data']['calcom_booking_id'] ?? 'N/A') . "\n";
                echo "   - Start Time: " . $result['appointment_data']['start_time'] . "\n";
            }
        } else {
            echo "❌ Appointment NOT created\n";
            
            // Check the call record for more details
            if (isset($result['call_id'])) {
                $call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->find($result['call_id']);
                if ($call) {
                    echo "\nCall details:\n";
                    echo "   - Dynamic vars: " . $call->retell_dynamic_variables . "\n";
                    echo "   - Extracted date: " . $call->extracted_date . "\n";
                    echo "   - Extracted time: " . $call->extracted_time . "\n";
                }
            }
        }
    } else {
        echo "❌ FAILED! " . ($result['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}