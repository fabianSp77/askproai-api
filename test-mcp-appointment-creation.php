<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\WebhookMCPServer;
use App\Models\Call;
use Carbon\Carbon;

echo "\n" . str_repeat('=', 60) . "\n";
echo "TEST MCP APPOINTMENT CREATION DIRECTLY\n";
echo str_repeat('=', 60) . "\n\n";

// Get the latest call
$call = Call::withoutGlobalScopes()->latest()->first();
if (!$call) {
    echo "No calls found\n";
    exit;
}

echo "Using latest call: ID {$call->id}\n";

// Create MCP servers
$calcomMCP = new CalcomMCPServer();
$webhookMCP = new WebhookMCPServer();

// Test data
$testDate = Carbon::now()->addDays(3);
$bookingData = [
    'company_id' => 1,
    'event_type_id' => 2563193,
    'start' => $testDate->copy()->setTime(10, 0)->toIso8601String(),
    'end' => $testDate->copy()->setTime(10, 30)->toIso8601String(),
    'name' => 'MCP Test Customer',
    'email' => 'mcp-test@example.com',
    'phone' => '+491234567890',
    'notes' => 'Direct MCP test',
    'metadata' => [
        'call_id' => (string)$call->id,
        'source' => 'direct_test'
    ]
];

echo "\nTesting CalcomMCP->createBooking()...\n";
echo "Data: " . json_encode($bookingData, JSON_PRETTY_PRINT) . "\n\n";

try {
    $result = $calcomMCP->createBooking($bookingData);
    
    echo "Result:\n";
    print_r($result);
    
    if ($result['success'] ?? false) {
        echo "\n✅ BOOKING CREATED SUCCESSFULLY!\n";
        echo "Booking ID: " . ($result['booking']['id'] ?? 'N/A') . "\n";
        
        // Now test the webhook MCP appointment creation
        echo "\n\nTesting WebhookMCP appointment creation...\n";
        
        $callData = [
            'retell_llm_dynamic_variables' => [
                'booking_confirmed' => true,
                'name' => 'Webhook Test Customer',
                'datum' => '2025-06-26',
                'uhrzeit' => '15:00',
                'dienstleistung' => 'Beratung'
            ]
        ];
        
        $phoneResolution = [
            'company_id' => 1,
            'branch_id' => '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793',
            'calcom_event_type_id' => 2563193
        ];
        
        // Call the protected method via reflection
        $reflection = new ReflectionClass($webhookMCP);
        $method = $reflection->getMethod('createAppointmentViaMCP');
        $method->setAccessible(true);
        
        $appointmentResult = $method->invoke($webhookMCP, $call, $callData, $phoneResolution);
        
        echo "\nAppointment creation result:\n";
        print_r($appointmentResult);
        
    } else {
        echo "\n❌ BOOKING FAILED\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        echo "Message: " . ($result['message'] ?? 'No message') . "\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat('=', 60) . "\n";