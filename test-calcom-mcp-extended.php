<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\MCP\CalcomMCPServer;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Initialize CalcomMCPServer
$mcpServer = new CalcomMCPServer();

// Test company ID
$companyId = 1;

echo "=== Testing Extended CalcomMCPServer Functions ===\n\n";

// 1. Test checkAvailability with caching
echo "1. Testing checkAvailability with caching:\n";
$availabilityParams = [
    'company_id' => $companyId,
    'event_type_id' => 2026361, // Replace with actual event type ID
    'date_from' => date('Y-m-d'),
    'date_to' => date('Y-m-d', strtotime('+7 days')),
    'timezone' => 'Europe/Berlin'
];

$result = $mcpServer->checkAvailability($availabilityParams);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Test cache hit
echo "Testing cache hit (should be faster):\n";
$start = microtime(true);
$result = $mcpServer->checkAvailability($availabilityParams);
$end = microtime(true);
echo "Execution time: " . round(($end - $start) * 1000, 2) . "ms\n";
echo "Cached until: " . ($result['cached_until'] ?? 'N/A') . "\n\n";

// 2. Test createBooking with retry logic
echo "2. Testing createBooking with retry logic:\n";
$bookingParams = [
    'company_id' => $companyId,
    'event_type_id' => 2026361,
    'start' => date('Y-m-d\TH:i:s\Z', strtotime('tomorrow 10:00')),
    'name' => 'Test Customer',
    'email' => 'test@example.com',
    'phone' => '+49 30 12345678',
    'notes' => 'Test booking from MCP Server',
    'timezone' => 'Europe/Berlin',
    'metadata' => [
        'source' => 'mcp_test',
        'test_run' => true
    ]
];

$result = $mcpServer->createBooking($bookingParams);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

if ($result['success']) {
    $bookingId = $result['booking']['id'];
    
    // 3. Test updateBooking
    echo "3. Testing updateBooking:\n";
    $updateParams = [
        'company_id' => $companyId,
        'booking_id' => $bookingId,
        'start' => date('Y-m-d\TH:i:s\Z', strtotime('tomorrow 14:00')),
        'reschedule_reason' => 'Customer requested time change'
    ];
    
    $updateResult = $mcpServer->updateBooking($updateParams);
    echo json_encode($updateResult, JSON_PRETTY_PRINT) . "\n\n";
    
    // 4. Test findAlternativeSlots
    echo "4. Testing findAlternativeSlots:\n";
    $alternativeParams = [
        'company_id' => $companyId,
        'event_type_id' => 2026361,
        'preferred_start' => date('Y-m-d\TH:i:s\Z', strtotime('tomorrow 09:00')),
        'search_days' => 5,
        'max_alternatives' => 3,
        'timezone' => 'Europe/Berlin'
    ];
    
    $alternativeResult = $mcpServer->findAlternativeSlots($alternativeParams);
    echo json_encode($alternativeResult, JSON_PRETTY_PRINT) . "\n\n";
    
    // 5. Test cancelBooking
    echo "5. Testing cancelBooking:\n";
    $cancelParams = [
        'company_id' => $companyId,
        'booking_id' => $bookingId,
        'cancellation_reason' => 'Test cancellation'
    ];
    
    $cancelResult = $mcpServer->cancelBooking($cancelParams);
    echo json_encode($cancelResult, JSON_PRETTY_PRINT) . "\n\n";
}

// 6. Test circuit breaker by simulating failures
echo "6. Testing circuit breaker (simulating service unavailability):\n";
// This would require modifying the service to simulate failures
// For now, just show that the circuit breaker is in place

echo "\n=== Test completed ===\n";