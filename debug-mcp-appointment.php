<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\MCP\CalcomMCPServer;
use App\Models\Company;

echo "\n" . str_repeat('=', 60) . "\n";
echo "DEBUG MCP APPOINTMENT CREATION\n";
echo str_repeat('=', 60) . "\n\n";

// Create CalcomMCPServer instance
$calcomMCP = new CalcomMCPServer();

// Test booking data
$bookingData = [
    'company_id' => 1,
    'event_type_id' => 2563193,
    'start' => '2025-06-25T14:00:00+02:00',  // Wednesday June 25, 2025
    'end' => '2025-06-25T14:30:00+02:00',
    'name' => 'Test MCP Customer',
    'email' => 'test@example.com',
    'phone' => '+491234567890',
    'notes' => 'Test booking via MCP debug',
    'metadata' => [
        'call_id' => 999,
        'source' => 'mcp_debug'
    ]
];

echo "Booking data:\n";
print_r($bookingData);
echo "\n";

try {
    // Enable all error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "Calling CalcomMCPServer->createBooking()...\n\n";
    
    $result = $calcomMCP->createBooking($bookingData);
    
    echo "Result:\n";
    print_r($result);
    
} catch (\Exception $e) {
    echo "Exception caught:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";