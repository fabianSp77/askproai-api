<?php

echo "\n" . str_repeat('=', 60) . "\n";
echo "DIRECT MCP WEBHOOK TEST (NO SIGNATURE)\n";
echo str_repeat('=', 60) . "\n\n";

// Test webhook payload
$testPayload = [
    'event' => 'call_ended',
    'call' => [
        'call_id' => 'mcp_direct_' . time(),
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'from_number' => '+491234567890',
        'to_number' => '+493083793369',
        'direction' => 'inbound',
        'call_status' => 'ended',
        'start_timestamp' => (time() - 300) * 1000,
        'end_timestamp' => time() * 1000,
        'duration_ms' => 300000,
        'transcript' => 'Ich möchte gerne einen Termin am 30. Juni um 15 Uhr buchen.',
        'summary' => 'Kunde möchte Termin am 30.06. um 15:00 Uhr',
        'call_analysis' => [
            'appointment_requested' => true,
            'customer_name' => 'MCP Direct Test',
            'sentiment' => 'positive'
        ],
        'retell_llm_dynamic_variables' => [
            'booking_confirmed' => true,
            'name' => 'MCP Direct Test',
            'datum' => '2025-06-30',
            'uhrzeit' => '15:00',
            'dienstleistung' => 'Beratung'
        ]
    ]
];

echo "Testing routes:\n";
echo "1. /api/mcp/retell/webhook (MCP route)\n";
echo "2. /api/retell/webhook (Legacy route)\n";
echo "3. /api/retell/debug-webhook (Debug route)\n\n";

// Try each route
$routes = [
    '/api/mcp/retell/webhook' => 'MCP Route',
    '/api/retell/webhook' => 'Legacy Route',
    '/api/retell/debug-webhook' => 'Debug Route'
];

foreach ($routes as $route => $name) {
    echo "\nTesting $name ($route)...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost' . $route);
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
    
    echo "Response Code: $httpCode\n";
    if ($httpCode == 200) {
        echo "✅ Success!\n";
        $responseData = json_decode($response, true);
        if (isset($responseData['appointment_created']) && $responseData['appointment_created']) {
            echo "🎉 APPOINTMENT CREATED!\n";
        }
        break;
    } else {
        echo "Response: " . substr($response, 0, 100) . "...\n";
    }
}

// Check database for results
sleep(2);

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Call;
use App\Models\Appointment;

echo "\n\nChecking database...\n";

// Find the call
$call = Call::withoutGlobalScopes()
    ->where('call_id', 'LIKE', 'mcp_direct_%')
    ->orderBy('created_at', 'desc')
    ->first();

if ($call) {
    echo "✅ Call found:\n";
    echo "- ID: {$call->id}\n";
    echo "- Call ID: {$call->call_id}\n";
    echo "- Customer ID: {$call->customer_id}\n";
    
    // Check for appointment
    $appointment = Appointment::withoutGlobalScopes()
        ->where('call_id', $call->id)
        ->first();
        
    if ($appointment) {
        echo "\n✅ APPOINTMENT CREATED!\n";
        echo "- Appointment ID: {$appointment->id}\n";
        echo "- Cal.com Booking ID: {$appointment->calcom_booking_id}\n";
        
        // Create success documentation
        echo "\n\n";
        echo str_repeat('=', 60) . "\n";
        echo "SUCCESS DOCUMENTATION\n";
        echo str_repeat('=', 60) . "\n";
        echo "Working Configuration:\n";
        echo "- Webhook URL: /api/retell/debug-webhook (no signature required)\n";
        echo "- Event Type ID: 2563193\n";
        echo "- Team ID: 39203\n";
        echo "- Phone Number: +493083793369\n";
        echo "- Branch ID: 14b9996c-4ebe-11f0-b9c1-0ad77e7a9793\n";
        echo "\nRequired Dynamic Variables:\n";
        echo "- booking_confirmed: true\n";
        echo "- datum: YYYY-MM-DD format\n";
        echo "- uhrzeit: HH:MM format\n";
        echo "- name: Customer name\n";
    } else {
        echo "\n❌ No appointment created\n";
    }
} else {
    echo "❌ No call found in database\n";
}

echo "\n" . str_repeat('=', 60) . "\n";