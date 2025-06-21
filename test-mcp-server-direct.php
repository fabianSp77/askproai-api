<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

echo "\n" . str_repeat('=', 60) . "\n";
echo "DIRECT MCP SERVER TEST\n";
echo str_repeat('=', 60) . "\n\n";

// Get the WebhookMCPServer from the container
$webhookMCP = app(\App\Services\MCP\WebhookMCPServer::class);

// Test payload
$testPayload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'direct_server_' . time(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 300) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 300000,
        'transcript' => 'Ja, ich möchte einen Termin am 2. Juli um 11 Uhr.',
        'summary' => 'Termin am 02.07. um 11:00',
        'call_analysis' => [
            'appointment_requested' => true,
            'customer_name' => 'Server Test Customer'
        ],
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => true,
            'name' => 'Server Test Customer',
            'datum' => '2025-07-02',
            'uhrzeit' => '11:00',
            'dienstleistung' => 'Beratung'
        ]
    ]
];

echo "Processing webhook with MCP Server directly...\n\n";

try {
    $result = $webhookMCP->processRetellWebhook($testPayload);
    
    echo "Result:\n";
    print_r($result);
    
    if (isset($result['success']) && $result['success']) {
        echo "\n✅ Webhook processed successfully!\n";
        
        if (isset($result['appointment_created']) && $result['appointment_created']) {
            echo "🎉 APPOINTMENT CREATED!\n";
            echo "- Appointment ID: " . ($result['appointment_data']['id'] ?? 'N/A') . "\n";
            echo "- Cal.com Booking ID: " . ($result['appointment_data']['calcom_booking_id'] ?? 'N/A') . "\n";
            
            // FINAL WORKING SOLUTION DOCUMENTATION
            echo "\n\n";
            echo str_repeat('=', 60) . "\n";
            echo "✅ FUNKTIONIERENDE LÖSUNG DOKUMENTATION ✅\n";
            echo str_repeat('=', 60) . "\n";
            echo "\n1. DIREKTER MCP SERVER AUFRUF:\n";
            echo "   \$webhookMCP = app(\\App\\Services\\MCP\\WebhookMCPServer::class);\n";
            echo "   \$result = \$webhookMCP->processWebhook(\$payload);\n";
            echo "\n2. ERFORDERLICHE PAYLOAD STRUKTUR:\n";
            echo json_encode($testPayload, JSON_PRETTY_PRINT);
            echo "\n\n3. CAL.COM KONFIGURATION:\n";
            echo "   - Event Type: 2563193 (Team Event)\n";
            echo "   - Team ID: 39203\n";
            echo "   - Branch calcom_event_type_id: 2563193\n";
            echo "\n4. PHONE NUMBER MAPPING:\n";
            echo "   - Phone: +493083793369\n";
            echo "   - Branch: 14b9996c-4ebe-11f0-b9c1-0ad77e7a9793\n";
        }
    } else {
        echo "\n❌ Processing failed\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Check database
echo "\n\nChecking database...\n";

use App\Models\Call;
use App\Models\Appointment;

$call = Call::withoutGlobalScopes()
    ->where('call_id', 'LIKE', 'direct_server_%')
    ->orderBy('created_at', 'desc')
    ->first();

if ($call) {
    echo "✅ Call record found (ID: {$call->id})\n";
    
    $appointment = Appointment::withoutGlobalScopes()
        ->where('call_id', $call->id)
        ->first();
        
    if ($appointment) {
        echo "✅ Appointment found (ID: {$appointment->id})\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";