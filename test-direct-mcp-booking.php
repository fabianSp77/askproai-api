<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\MCP\CalcomMCPServer;
use Carbon\Carbon;

echo "\n" . str_repeat('=', 60) . "\n";
echo "DIRECT MCP BOOKING TEST\n";
echo str_repeat('=', 60) . "\n\n";

// Create CalcomMCPServer instance
$calcomMCP = new CalcomMCPServer();

// Prepare booking data
$testDate = Carbon::parse('2025-06-27');
$bookingData = [
    'company_id' => 1,
    'event_type_id' => 2563193,
    'start' => $testDate->copy()->setTime(11, 0)->toIso8601String(),
    'end' => $testDate->copy()->setTime(11, 30)->toIso8601String(),
    'name' => 'MCP Direct Test',
    'email' => 'direct-test@example.com',
    'phone' => '+491234567890',
    'notes' => 'Testing MCP booking directly',
    'metadata' => [
        'call_id' => '999',
        'source' => 'direct_mcp_test'
    ]
];

echo "Booking Data:\n";
echo json_encode($bookingData, JSON_PRETTY_PRINT) . "\n\n";

echo "Calling CalcomMCPServer->createBooking()...\n\n";

try {
    $result = $calcomMCP->createBooking($bookingData);
    
    echo "Result:\n";
    print_r($result);
    
    if ($result['success'] ?? false) {
        echo "\n✅ BOOKING CREATED SUCCESSFULLY!\n";
        echo "Booking ID: " . ($result['booking']['id'] ?? 'N/A') . "\n";
        echo "UID: " . ($result['booking']['uid'] ?? 'N/A') . "\n";
        echo "Start: " . ($result['booking']['start'] ?? 'N/A') . "\n";
        echo "Status: " . ($result['booking']['status'] ?? 'N/A') . "\n";
        
        // Now create the MCP mapping documentation
        echo "\n\n" . str_repeat('-', 40) . "\n";
        echo "MCP MAPPING DOCUMENTATION:\n";
        echo str_repeat('-', 40) . "\n";
        
        echo "1. Event Type ID: 2563193 (Team Event Type)\n";
        echo "2. Team ID: 39203 (AskProAI Team)\n";
        echo "3. API Endpoint: https://api.cal.com/v1/bookings\n";
        echo "4. Required Fields:\n";
        echo "   - eventTypeId (int)\n";
        echo "   - teamId (int) - FOR TEAM EVENTS ONLY\n";
        echo "   - start (ISO8601 string)\n";
        echo "   - end (ISO8601 string)\n";
        echo "   - responses.name (string)\n";
        echo "   - responses.email (string)\n";
        echo "   - metadata (object with string values only)\n";
        
    } else {
        echo "\n❌ BOOKING FAILED\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        echo "Message: " . ($result['message'] ?? 'No message') . "\n";
        
        if (isset($result['circuit_breaker_open']) && $result['circuit_breaker_open']) {
            echo "\n⚠️  Circuit Breaker is OPEN - Service temporarily unavailable\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";