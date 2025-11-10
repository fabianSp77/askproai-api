<?php
/**
 * Debug E2E Flow - Alternative Selection Issue
 * Purpose: Trace exactly what's happening when we use alternative times in start_booking
 *
 * Steps:
 * 1. Create a call_id
 * 2. Initialize the call
 * 3. Check availability (get alternatives)
 * 4. Use alternative time in start_booking
 * 5. Monitor the logs for service lookup
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

echo "\n=== DEBUG E2E FLOW - Alternative Selection Issue ===\n\n";

// Test configuration
$baseUrl = 'http://localhost:8000';
$callId = 'debug_flow_' . uniqid();
$testData = [
    'service' => 'Herrenhaarschnitt',
    'date' => '2025-11-10', // Today
    'time' => '10:00',
    'customer_name' => 'Test Kunde',
    'phone' => '+491234567890',
];

echo "[1] Call ID: $callId\n\n";

// Step 1: Check Availability
echo "[2] Running check_availability...\n";

$payload = [
    'name' => 'check_availability',
    'args' => [
        'service_name' => $testData['service'],
        'appointment_date' => $testData['date'],
        'appointment_time' => $testData['time'],
        'call_id' => $callId
    ],
    'call' => ['call_id' => $callId]
];

echo "  Payload:\n";
echo "    - service: " . $testData['service'] . "\n";
echo "    - date: " . $testData['date'] . "\n";
echo "    - time: " . $testData['time'] . "\n";

$response = Http::post("$baseUrl/api/webhooks/retell/function", $payload);
$availabilityResult = $response->json();

echo "  Response:\n";
echo "    - success: " . json_encode($availabilityResult['success']) . "\n";
echo "    - available: " . json_encode($availabilityResult['data']['available'] ?? 'N/A') . "\n";
echo "    - alternatives count: " . count($availabilityResult['data']['alternatives'] ?? []) . "\n";

if (!empty($availabilityResult['data']['alternatives'])) {
    echo "    - alternatives:\n";
    foreach ($availabilityResult['data']['alternatives'] as $alt) {
        echo "      * " . $alt['time'] . " (available: " . json_encode($alt['available']) . ")\n";
    }
}

// Extract alternative
$alternative = $availabilityResult['data']['alternatives'][0] ?? null;
if (!$alternative) {
    echo "\n❌ No alternatives found! Cannot proceed with alternative selection test.\n";
    exit(1);
}

$alternativeTime = $alternative['time'];
echo "\n✅ Alternative selected: $alternativeTime\n\n";

// Step 2: Use alternative in start_booking
echo "[3] Running start_booking with ALTERNATIVE time...\n";

$payload = [
    'name' => 'start_booking',
    'args' => [
        'service_name' => $testData['service'],
        'datetime' => $alternativeTime,  // ← Using alternative
        'customer_name' => $testData['customer_name'],
        'customer_phone' => $testData['phone'],
        'call_id' => $callId
    ],
    'call' => ['call_id' => $callId]
];

echo "  Payload:\n";
echo "    - service: " . $testData['service'] . "\n";
echo "    - datetime: " . $alternativeTime . "\n";
echo "    - customer_name: " . $testData['customer_name'] . "\n";

$response = Http::post("$baseUrl/api/webhooks/retell/function", $payload);
$bookingResult = $response->json();

echo "  Response:\n";
echo "    - success: " . json_encode($bookingResult['success']) . "\n";
echo "    - status: " . ($bookingResult['data']['status'] ?? 'N/A') . "\n";
echo "    - error: " . ($bookingResult['data']['error'] ?? 'N/A') . "\n";

// Result
echo "\n";
if ($bookingResult['success'] && $bookingResult['data']['status'] === 'validating') {
    echo "✅ SUCCESS: Alternative selection worked!\n";
} else {
    echo "❌ FAILED: Alternative selection did not work.\n";
    echo "\nFull Response:\n";
    echo json_encode($bookingResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

// Step 3: Check logs
echo "\n[4] Checking logs...\n";

sleep(1); // Wait for logs to flush

$logFile = __DIR__ . '/../storage/logs/laravel.log';
$logs = shell_exec("tail -500 $logFile | grep -A 10 'STEP 4\\|Service lookup'");

if ($logs) {
    echo "  Found relevant logs:\n";
    echo $logs;
} else {
    echo "  No logs found yet (logs may take a moment to appear)\n";
}

echo "\n=== End of Debug ===\n\n";
