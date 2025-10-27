#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'https://api.askproai.de';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🧪 TESTING ALL RETELL FUNCTION ENDPOINTS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Base URL: $baseUrl\n";
echo "Testing all 7 function endpoints...\n\n";

$results = [];

// Test 1: initialize_call
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "[1/7] initialize_call\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$endpoint = '/api/retell/initialize-call';
$payload = [
    'call_id' => 'test_call_' . uniqid()
];

echo "URL: $baseUrl$endpoint\n";
echo "Payload: " . json_encode($payload) . "\n\n";

try {
    $response = Http::timeout(10)->post($baseUrl . $endpoint, $payload);

    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";

    $results['initialize_call'] = [
        'status' => $response->status(),
        'success' => $response->successful(),
        'response' => $response->json()
    ];
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    $results['initialize_call'] = [
        'status' => 0,
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Test 2: check_availability_v17
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "[2/7] check_availability_v17\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$endpoint = '/api/retell/v17/check-availability';
$payload = [
    'call_id' => 'test_call_' . uniqid(),
    'datum' => '2025-10-25',
    'uhrzeit' => '09:00',
    'dienstleistung' => 'Herrenhaarschnitt'
];

echo "URL: $baseUrl$endpoint\n";
echo "Payload: " . json_encode($payload) . "\n\n";

try {
    $response = Http::timeout(10)->post($baseUrl . $endpoint, $payload);

    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";

    $results['check_availability_v17'] = [
        'status' => $response->status(),
        'success' => $response->successful(),
        'response' => $response->json()
    ];
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    $results['check_availability_v17'] = [
        'status' => 0,
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Test 3: book_appointment_v17
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "[3/7] book_appointment_v17\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$endpoint = '/api/retell/v17/book-appointment';
$payload = [
    'call_id' => 'test_call_' . uniqid(),
    'datum' => '2025-10-25',
    'uhrzeit' => '09:00',
    'dienstleistung' => 'Herrenhaarschnitt'
];

echo "URL: $baseUrl$endpoint\n";
echo "Payload: " . json_encode($payload) . "\n\n";

try {
    $response = Http::timeout(10)->post($baseUrl . $endpoint, $payload);

    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";

    $results['book_appointment_v17'] = [
        'status' => $response->status(),
        'success' => $response->successful(),
        'response' => $response->json()
    ];
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    $results['book_appointment_v17'] = [
        'status' => 0,
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Test 4: get_customer_appointments
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "[4/7] get_customer_appointments\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$endpoint = '/api/retell/get-customer-appointments';
$payload = [
    'call_id' => 'test_call_' . uniqid()
];

echo "URL: $baseUrl$endpoint\n";
echo "Payload: " . json_encode($payload) . "\n\n";

try {
    $response = Http::timeout(10)->post($baseUrl . $endpoint, $payload);

    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";

    $results['get_customer_appointments'] = [
        'status' => $response->status(),
        'success' => $response->successful(),
        'response' => $response->json()
    ];
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    $results['get_customer_appointments'] = [
        'status' => 0,
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Test 5: cancel_appointment
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "[5/7] cancel_appointment\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$endpoint = '/api/retell/cancel-appointment';
$payload = [
    'call_id' => 'test_call_' . uniqid(),
    'datum' => '2025-10-25',
    'uhrzeit' => '09:00'
];

echo "URL: $baseUrl$endpoint\n";
echo "Payload: " . json_encode($payload) . "\n\n";

try {
    $response = Http::timeout(10)->post($baseUrl . $endpoint, $payload);

    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";

    $results['cancel_appointment'] = [
        'status' => $response->status(),
        'success' => $response->successful(),
        'response' => $response->json()
    ];
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    $results['cancel_appointment'] = [
        'status' => 0,
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Test 6: reschedule_appointment
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "[6/7] reschedule_appointment\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$endpoint = '/api/retell/reschedule-appointment';
$payload = [
    'call_id' => 'test_call_' . uniqid(),
    'new_datum' => '2025-10-26',
    'new_uhrzeit' => '10:00'
];

echo "URL: $baseUrl$endpoint\n";
echo "Payload: " . json_encode($payload) . "\n\n";

try {
    $response = Http::timeout(10)->post($baseUrl . $endpoint, $payload);

    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";

    $results['reschedule_appointment'] = [
        'status' => $response->status(),
        'success' => $response->successful(),
        'response' => $response->json()
    ];
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    $results['reschedule_appointment'] = [
        'status' => 0,
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Test 7: get_available_services
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "[7/7] get_available_services\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$endpoint = '/api/retell/get-available-services';
$payload = [
    'call_id' => 'test_call_' . uniqid()
];

echo "URL: $baseUrl$endpoint\n";
echo "Payload: " . json_encode($payload) . "\n\n";

try {
    $response = Http::timeout(10)->post($baseUrl . $endpoint, $payload);

    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";

    $results['get_available_services'] = [
        'status' => $response->status(),
        'success' => $response->successful(),
        'response' => $response->json()
    ];
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    $results['get_available_services'] = [
        'status' => 0,
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Summary
echo "\n═══════════════════════════════════════════════════════════\n";
echo "📊 TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$passed = 0;
$failed = 0;

foreach ($results as $name => $result) {
    if ($result['success']) {
        echo "✅ $name: HTTP {$result['status']}\n";
        $passed++;
    } else {
        echo "❌ $name: ";
        if (isset($result['error'])) {
            echo "ERROR - {$result['error']}\n";
        } else {
            echo "HTTP {$result['status']}\n";
        }
        $failed++;
    }
}

echo "\n";
echo "Passed: $passed/7\n";
echo "Failed: $failed/7\n\n";

if ($failed > 0) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "🔍 DETAILED ERRORS\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    foreach ($results as $name => $result) {
        if (!$result['success']) {
            echo "Function: $name\n";
            echo "───────────────────────────────────────────────────────────\n";

            if (isset($result['error'])) {
                echo "Error: {$result['error']}\n";
            } else {
                echo "HTTP Status: {$result['status']}\n";
                if (isset($result['response'])) {
                    echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
                }
            }
            echo "\n";
        }
    }
}

if ($failed === 0) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "🎉 ALL ENDPOINTS WORKING!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    exit(0);
} else {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "❌ SOME ENDPOINTS FAILING\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    exit(1);
}
