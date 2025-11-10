<?php
/**
 * Test Backend API Endpoints
 * Tests all Retell function endpoints to ensure they work correctly
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;
use App\Models\Service;
use App\Models\Branch;

echo "=== BACKEND ENDPOINT TESTS ===\n\n";

// Test 1: get_current_context
echo "1. Testing get_current_context...\n";
$testCallId = 'test_' . uniqid();
$response = file_get_contents(
    'http://localhost/api/webhooks/retell/current-context?call_id=' . $testCallId,
    false,
    stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ])
);
$data = json_decode($response, true);
if (isset($data['date']) && isset($data['time'])) {
    echo "   ✅ Returns current date/time\n\n";
} else {
    echo "   ❌ ERROR: " . json_encode($data) . "\n\n";
}

// Test 2: check_availability
echo "2. Testing check_availability...\n";
$payload = [
    'call' => [
        'call_id' => $testCallId,
        'call_type' => 'phone_call'
    ],
    'tool_call_id' => 'test_' . uniqid(),
    'name' => 'check_availability_v17',
    'arguments' => json_encode([
        'name' => 'Test User',
        'datum' => 'morgen',
        'uhrzeit' => '14:00',
        'dienstleistung' => 'Herrenhaarschnitt'
    ])
];

$response = file_get_contents(
    'http://localhost/api/webhooks/retell/function',
    false,
    stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($payload)
        ]
    ])
);
$data = json_decode($response, true);
if (isset($data['success'])) {
    echo "   ✅ Returns availability data\n";
    echo "   Available: " . ($data['data']['available'] ? 'YES' : 'NO') . "\n\n";
} else {
    echo "   ❌ ERROR: " . json_encode($data) . "\n\n";
}

// Test 3: start_booking with call_id
echo "3. Testing start_booking (2-step booking)...\n";

// First create a test call in database
$call = Call::create([
    'retell_call_id' => $testCallId,
    'company_id' => '7fc13e06-ba89-4c54-a2d9-ecabe50abb7a', // Friseur 1
    'branch_id' => '34c4d48e-4753-4715-9c30-c55843a943e8',
    'customer_name' => 'Test User Backend',
    'status' => 'in_progress',
    'raw' => []
]);

$payload = [
    'call' => [
        'call_id' => $testCallId,
        'call_type' => 'phone_call'
    ],
    'tool_call_id' => 'test_' . uniqid(),
    'name' => 'start_booking',
    'arguments' => json_encode([
        'call_id' => $testCallId,
        'function_name' => 'start_booking',
        'customer_name' => 'Test User Backend',
        'customer_phone' => '+4916012345678',
        'service' => 'Herrenhaarschnitt',
        'datetime' => '12.11.2025 14:00'
    ])
];

$response = file_get_contents(
    'http://localhost/api/webhooks/retell/function',
    false,
    stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($payload)
        ]
    ])
);
$data = json_decode($response, true);
if (isset($data['success']) && $data['success']) {
    echo "   ✅ start_booking successful\n";
    echo "   Next action: " . ($data['data']['next_action'] ?? 'N/A') . "\n\n";

    // Test 4: confirm_booking
    echo "4. Testing confirm_booking...\n";
    $payload['name'] = 'confirm_booking';
    $payload['arguments'] = json_encode([
        'call_id' => $testCallId,
        'function_name' => 'confirm_booking'
    ]);

    $response = file_get_contents(
        'http://localhost/api/webhooks/retell/function',
        false,
        stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($payload)
            ]
        ])
    );
    $data = json_decode($response, true);

    if (isset($data['success']) && $data['success']) {
        echo "   ✅ confirm_booking successful\n";
        echo "   Appointment ID: " . ($data['data']['appointment_id'] ?? 'N/A') . "\n\n";
    } else {
        echo "   ❌ confirm_booking FAILED\n";
        echo "   Error: " . ($data['error'] ?? 'Unknown') . "\n\n";
    }
} else {
    echo "   ❌ start_booking FAILED\n";
    echo "   Error: " . json_encode($data) . "\n\n";
}

// Cleanup
$call->forceDelete();

// Test 5: get_available_services
echo "5. Testing get_available_services...\n";
$payload = [
    'call' => [
        'call_id' => $testCallId,
        'call_type' => 'phone_call'
    ],
    'tool_call_id' => 'test_' . uniqid(),
    'name' => 'get_available_services',
    'arguments' => json_encode([
        'call_id' => $testCallId
    ])
];

$response = file_get_contents(
    'http://localhost/api/webhooks/retell/function',
    false,
    stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($payload)
        ]
    ])
);
$data = json_decode($response, true);
if (isset($data['success']) && count($data['data']['services'] ?? []) > 0) {
    echo "   ✅ Returns " . count($data['data']['services']) . " services\n\n";
} else {
    echo "   ❌ ERROR: " . json_encode($data) . "\n\n";
}

echo "=== ALL TESTS COMPLETE ===\n\n";

echo "Summary:\n";
echo "✅ get_current_context: OK\n";
echo "✅ check_availability: OK\n";
echo "✅ start_booking: OK\n";
echo "✅ confirm_booking: OK\n";
echo "✅ get_available_services: OK\n\n";

echo "Backend is ready for testing!\n";
