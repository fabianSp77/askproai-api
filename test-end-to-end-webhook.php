<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;

echo "\n" . str_repeat('=', 60) . "\n";
echo "END-TO-END WEBHOOK TEST\n";
echo str_repeat('=', 60) . "\n\n";

// Test webhook payload for a completed call
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_call_' . time(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => now()->subMinutes(5)->timestamp * 1000,
        'end_timestamp' => now()->timestamp * 1000,
        'duration_ms' => 300000, // 5 minutes
        'recording_available' => false,
        'transcript' => 'Agent: Guten Tag, Fabian Spitzer Rechtliches. Wie kann ich Ihnen helfen?\nCaller: Hallo, ich möchte gerne einen Termin vereinbaren.\nAgent: Gerne! Für wann hätten Sie denn Zeit?\nCaller: Nächsten Dienstag um 14 Uhr?\nAgent: Das passt sehr gut. Darf ich noch Ihren Namen haben?\nCaller: Max Mustermann\nAgent: Und Ihre Telefonnummer für Rückfragen?\nCaller: Die haben Sie ja schon.\nAgent: Perfekt, dann habe ich für Sie einen Termin am nächsten Dienstag um 14 Uhr eingetragen. Sie erhalten gleich eine Bestätigung per E-Mail.\nCaller: Vielen Dank!\nAgent: Gerne! Auf Wiederhören.',
        'summary' => 'Kunde möchte Beratungstermin am nächsten Dienstag um 14 Uhr. Name: Max Mustermann.',
        'call_analysis' => [
            'appointment_requested' => true,
            'customer_name' => 'Max Mustermann',
            'preferred_date' => 'nächsten Dienstag',
            'preferred_time' => '14:00',
            'service_type' => 'Beratungsgespräch',
            'sentiment' => 'positive'
        ],
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => true,
            'name' => 'Max Mustermann',
            'datum' => '2025-06-25',
            'uhrzeit' => '14:00',
            'dienstleistung' => 'Beratungsgespräch'
        ]
    ],
    'test' => true
];

echo "Sending test webhook to MCP endpoint...\n\n";
echo "Payload:\n";
echo "- Event: call_ended\n";
echo "- Call ID: " . $payload['call']['call_id'] . "\n";
echo "- Agent: agent_9a8202a740cd3120d96fcfda1e\n";
echo "- From: +491234567890\n";
echo "- To: +493083793369\n";
echo "- Duration: 5 minutes\n";
echo "- Customer: Max Mustermann\n";
echo "- Appointment: Next Tuesday at 14:00\n\n";

try {
    // Generate a test signature using actual webhook secret
    $secret = 'key_6ff998ba48e842092e04a5455d19'; // From .env RETELL_WEBHOOK_SECRET
    $timestamp = time();
    $body = json_encode($payload);
    $signature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    
    $response = Http::withHeaders([
        'x-retell-signature' => $signature,
        'x-retell-timestamp' => $timestamp,
        'Content-Type' => 'application/json'
    ])->post('https://api.askproai.de/api/retell/mcp-webhook', $payload);
    
    echo "Response Status: " . $response->status() . "\n";
    echo "Response Body:\n" . $response->body() . "\n\n";
    
    if ($response->successful()) {
        echo "✅ Webhook processed successfully!\n\n";
        
        // Check if appointment was created
        echo "Checking database for created records...\n";
        
        // Check for call record
        $call = \App\Models\Call::where('retell_call_id', $payload['call']['call_id'])->first();
        if ($call) {
            echo "✅ Call record created: ID " . $call->id . "\n";
            echo "  - Phone: " . $call->phone_number . "\n";
            echo "  - Duration: " . $call->duration_minutes . " minutes\n";
            
            if ($call->customer_id) {
                $customer = $call->customer;
                echo "✅ Customer linked: " . $customer->name . "\n";
            }
            
            if ($call->appointment_id) {
                $appointment = $call->appointment;
                echo "✅ Appointment created:\n";
                echo "  - Date: " . $appointment->appointment_date . "\n";
                echo "  - Time: " . $appointment->start_time . "\n";
                echo "  - Service: " . ($appointment->service->name ?? 'N/A') . "\n";
            }
        } else {
            echo "⚠️  No call record found\n";
        }
    } else {
        echo "❌ Webhook failed with status " . $response->status() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "TEST COMPLETE\n";
echo str_repeat('=', 60) . "\n";