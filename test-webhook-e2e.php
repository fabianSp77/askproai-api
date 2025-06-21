<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "\n" . str_repeat('=', 60) . "\n";
echo "END-TO-END WEBHOOK TEST WITH APPOINTMENT CREATION\n";
echo str_repeat('=', 60) . "\n\n";

// Test webhook payload
$testPayload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'e2e_test_' . time(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 300) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 300000,
        'transcript' => 'Ich möchte gerne einen Termin am 29. Juni um 14 Uhr buchen.',
        'summary' => 'Kunde möchte Termin am 29.06. um 14:00 Uhr',
        'call_analysis' => [
            'appointment_requested' => true,
            'customer_name' => 'E2E Test Customer',
            'sentiment' => 'positive'
        ],
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => true,
            'name' => 'E2E Test Customer',
            'datum' => '2025-06-29',
            'uhrzeit' => '14:00',
            'dienstleistung' => 'Beratung'
        ]
    ]
];

echo "Payload:\n";
echo json_encode($testPayload, JSON_PRETTY_PRINT) . "\n\n";

// Make request to webhook endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/api/mcp/retell/webhook');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

echo "Sending webhook request...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\nResponse Code: $httpCode\n";
echo "Response: $response\n\n";

// Wait a moment for processing
sleep(2);

// Check database for results
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

echo "Checking database...\n\n";

// Find the call
$call = Call::withoutGlobalScopes()
    ->where('call_id', $testPayload['call']['call_id'])
    ->first();

if ($call) {
    echo "✅ Call found:\n";
    echo "- ID: {$call->id}\n";
    echo "- Customer ID: {$call->customer_id}\n";
    echo "- Branch ID: {$call->branch_id}\n";
    echo "- Extracted date: {$call->extracted_date}\n";
    echo "- Extracted time: {$call->extracted_time}\n";
    
    // Check for appointment
    $appointment = Appointment::withoutGlobalScopes()
        ->where('call_id', $call->id)
        ->first();
        
    if ($appointment) {
        echo "\n✅ APPOINTMENT CREATED!\n";
        echo "- Appointment ID: {$appointment->id}\n";
        echo "- Status: {$appointment->status}\n";
        echo "- Starts at: {$appointment->starts_at}\n";
        echo "- Cal.com Booking ID: {$appointment->calcom_booking_id}\n";
    } else {
        echo "\n❌ No appointment created\n";
        
        // Debug why
        echo "\nDynamic variables:\n";
        $dynamicVars = json_decode($call->retell_dynamic_variables, true);
        print_r($dynamicVars);
        
        // Check branch
        $branch = DB::table('branches')
            ->where('id', $call->branch_id)
            ->first();
            
        echo "\nBranch Cal.com event type: " . ($branch->calcom_event_type_id ?? 'NOT SET') . "\n";
    }
} else {
    echo "❌ No call found in database\n";
}

// Check for any Cal.com bookings created today
echo "\nChecking for Cal.com bookings...\n";
$bookings = DB::table('appointments')
    ->whereDate('created_at', date('Y-m-d'))
    ->whereNotNull('calcom_booking_id')
    ->count();
    
echo "Cal.com bookings created today: $bookings\n";

echo "\n" . str_repeat('=', 60) . "\n";