<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;

echo "\n" . str_repeat('=', 60) . "\n";
echo "SIMPLE MCP WEBHOOK TEST\n";
echo str_repeat('=', 60) . "\n\n";

// Test webhook payload with appointment booking
$payload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'test_appt_' . time(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => now()->subMinutes(5)->timestamp * 1000,
        'end_timestamp' => now()->timestamp * 1000,
        'duration_ms' => 300000,
        'transcript' => 'Agent: Hallo, wie kann ich Ihnen helfen?\nCaller: Ich möchte einen Termin buchen.\nAgent: Gerne, für wann?\nCaller: Dienstag 14 Uhr\nAgent: Perfekt, Termin ist gebucht!',
        'summary' => 'Kunde bucht Termin für Dienstag 14 Uhr',
        'call_analysis' => [
            'appointment_requested' => true,
            'customer_name' => 'Test Kunde',
            'sentiment' => 'positive'
        ],
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => 'true', // Try as string
            'name' => 'Test Kunde',
            'datum' => '2025-06-25',
            'uhrzeit' => '14:00',
            'dienstleistung' => 'Beratung'
        ]
    ]
];

echo "Payload:\n";
echo "- Call ID: " . $payload['call']['call_id'] . "\n";
echo "- booking_confirmed: " . $payload['call']['retell_llm_dynamic_variables']['booking_confirmed'] . "\n";
echo "- datum: " . $payload['call']['retell_llm_dynamic_variables']['datum'] . "\n";
echo "- uhrzeit: " . $payload['call']['retell_llm_dynamic_variables']['uhrzeit'] . "\n\n";

// Generate signature
$secret = 'key_6ff998ba48e842092e04a5455d19';
$timestamp = time();
$body = json_encode($payload);
$signature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

// Make local request
$response = Http::withHeaders([
    'x-retell-signature' => $signature,
    'x-retell-timestamp' => $timestamp,
    'Content-Type' => 'application/json'
])->post('http://localhost/api/retell/mcp-webhook', $payload);

echo "Response: " . $response->status() . "\n";
$responseData = $response->json();
print_r($responseData);

// Check database
echo "\nChecking database...\n";
$call = \App\Models\Call::withoutGlobalScopes()
    ->where('retell_call_id', $payload['call']['call_id'])
    ->first();

if ($call) {
    echo "\n✅ Call found:\n";
    echo "- ID: " . $call->id . "\n";
    echo "- Customer ID: " . $call->customer_id . "\n";
    echo "- Branch ID: " . $call->branch_id . "\n";
    echo "- Company ID: " . $call->company_id . "\n";
    echo "- Extracted date: " . $call->extracted_date . "\n";
    echo "- Extracted time: " . $call->extracted_time . "\n";
    echo "- Appointment ID: " . ($call->appointment_id ?: 'None') . "\n";
    
    // Check dynamic variables
    $dynamicVars = json_decode($call->retell_dynamic_variables, true);
    echo "\nDynamic Variables:\n";
    echo "- booking_confirmed: " . ($dynamicVars['booking_confirmed'] ?? 'not set') . "\n";
    echo "- Type: " . gettype($dynamicVars['booking_confirmed'] ?? null) . "\n";
    
    if ($call->appointment_id) {
        $appointment = \App\Models\Appointment::withoutGlobalScopes()->find($call->appointment_id);
        if ($appointment) {
            echo "\n✅ Appointment found:\n";
            echo "- Date: " . $appointment->appointment_date . "\n";
            echo "- Time: " . $appointment->start_time . "\n";
            echo "- Status: " . $appointment->status . "\n";
        }
    } else {
        echo "\n❌ No appointment created\n";
        
        // Let's debug why
        $branch = \App\Models\Branch::withoutGlobalScopes()->find($call->branch_id);
        echo "\nBranch Cal.com event type: " . ($branch->calcom_event_type_id ?: 'Not set') . "\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";