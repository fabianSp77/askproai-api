<?php

echo "\n" . str_repeat('=', 60) . "\n";
echo "FINAL MCP WEBHOOK TEST\n";
echo str_repeat('=', 60) . "\n\n";

// Test webhook payload with all required fields
$testPayload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'final_test_' . time(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 300) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 300000,
        'transcript' => 'Ja, ich möchte gerne einen Termin am 1. Juli um 16 Uhr buchen.',
        'summary' => 'Kunde bestätigt Termin am 01.07. um 16:00 Uhr',
        'call_analysis' => [
            'appointment_requested' => true,
            'customer_name' => 'Final Test Customer',
            'sentiment' => 'positive'
        ],
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => true,
            'name' => 'Final Test Customer',
            'datum' => '2025-07-01',
            'uhrzeit' => '16:00',
            'dienstleistung' => 'Beratung'
        ]
    ]
];

echo "Payload Summary:\n";
echo "- Call ID: " . $testPayload['call']['call_id'] . "\n";
echo "- To Number: " . $testPayload['call']['to_number'] . "\n";
echo "- Booking Confirmed: " . ($testPayload['call']['retell_llm_dynamic_variables']['booking_confirmed'] ? 'YES' : 'NO') . "\n";
echo "- Date: " . $testPayload['call']['retell_llm_dynamic_variables']['datum'] . "\n";
echo "- Time: " . $testPayload['call']['retell_llm_dynamic_variables']['uhrzeit'] . "\n\n";

// Make request to test endpoint
echo "Sending to /api/test/mcp-webhook...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/test/mcp-webhook');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nResponse Code: $httpCode\n";

if ($httpCode == 200) {
    echo "✅ Webhook processed successfully!\n";
    $responseData = json_decode($response, true);
    echo "Response:\n";
    print_r($responseData);
} else {
    echo "❌ Webhook failed\n";
    echo "Response: $response\n";
}

// Wait for processing
sleep(3);

// Check database
echo "\n\nChecking database...\n";

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Call;
use App\Models\Appointment;

$call = Call::withoutGlobalScopes()
    ->where('call_id', $testPayload['call']['call_id'])
    ->first();

if ($call) {
    echo "\n✅ Call Record Created:\n";
    echo "- Database ID: {$call->id}\n";
    echo "- Call ID: {$call->call_id}\n";
    echo "- Customer ID: {$call->customer_id}\n";
    echo "- Branch ID: {$call->branch_id}\n";
    echo "- Company ID: {$call->company_id}\n";
    echo "- Extracted Date: {$call->extracted_date}\n";
    echo "- Extracted Time: {$call->extracted_time}\n";
    
    // Check appointment
    $appointment = Appointment::withoutGlobalScopes()
        ->where('call_id', $call->id)
        ->first();
        
    if ($appointment) {
        echo "\n🎉 APPOINTMENT CREATED SUCCESSFULLY! 🎉\n";
        echo "- Appointment ID: {$appointment->id}\n";
        echo "- Status: {$appointment->status}\n";
        echo "- Starts At: {$appointment->starts_at}\n";
        echo "- Cal.com Booking ID: {$appointment->calcom_booking_id}\n";
        
        // FINAL SUCCESS DOCUMENTATION
        echo "\n\n";
        echo str_repeat('=', 60) . "\n";
        echo "🚀 ERFOLGREICHE END-TO-END KONFIGURATION 🚀\n";
        echo str_repeat('=', 60) . "\n";
        echo "\nFUNKTIONIERENDE WEBHOOK URL:\n";
        echo "https://api.askproai.de/api/test/mcp-webhook (für Tests)\n";
        echo "\nERFORDERLICHE FELDER:\n";
        echo "- event: 'call_ended'\n";
        echo "- call.to_number: '+493083793369' (mapped to branch)\n";
        echo "- call.retell_llm_dynamic_variables.booking_confirmed: true\n";
        echo "- call.retell_llm_dynamic_variables.datum: 'YYYY-MM-DD'\n";
        echo "- call.retell_llm_dynamic_variables.uhrzeit: 'HH:MM'\n";
        echo "- call.retell_llm_dynamic_variables.name: 'Customer Name'\n";
        echo "\nCAL.COM KONFIGURATION:\n";
        echo "- Event Type ID: 2563193 (Team Event)\n";
        echo "- Team ID: 39203\n";
        echo "- API Endpoint: https://api.cal.com/v1/bookings\n";
        
    } else {
        echo "\n❌ No appointment created\n";
        
        // Debug info
        $dynamicVars = json_decode($call->retell_dynamic_variables, true);
        echo "\nDebug Info:\n";
        echo "- Dynamic Variables:\n";
        print_r($dynamicVars);
    }
} else {
    echo "\n❌ No call record created\n";
}

echo "\n" . str_repeat('=', 60) . "\n";