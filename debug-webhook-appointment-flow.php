<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Services\MCP\WebhookMCPServer;
use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\RetellMCPServer;
use App\Services\MCP\DatabaseMCPServer;
use App\Services\MCP\QueueMCPServer;
use App\Models\Call;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

echo "\n" . str_repeat('=', 60) . "\n";
echo "DEBUG WEBHOOK APPOINTMENT FLOW\n";
echo str_repeat('=', 60) . "\n\n";

// Initialize MCP servers
$calcomMCP = new CalcomMCPServer();
$retellMCP = new RetellMCPServer();
$databaseMCP = new DatabaseMCPServer();
$queueMCP = new QueueMCPServer();

// Create WebhookMCPServer with dependencies
$webhookMCP = new WebhookMCPServer($calcomMCP, $retellMCP, $databaseMCP, $queueMCP);

// Test data
$testCallData = [
    'call_id' => 'debug_test_' . time(),
    'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
    'from_number' => '+491234567890',
    'to_number' => '+493083793369',
    'direction' => 'inbound',
    'call_status' => 'ended',
    'start_timestamp' => now()->subMinutes(5)->timestamp * 1000,
    'end_timestamp' => now()->timestamp * 1000,
    'duration_ms' => 300000,
    'transcript' => 'Test transcript',
    'summary' => 'Customer wants appointment',
    'call_analysis' => [
        'appointment_requested' => true,
        'customer_name' => 'Debug Test Customer'
    ],
    'retell_llm_dynamic_variables' => [
        'booking_confirmed' => true,
        'name' => 'Debug Test Customer',
        'datum' => '2025-06-28',
        'uhrzeit' => '10:00',
        'dienstleistung' => 'Beratung'
    ]
];

echo "1. Test shouldCreateAppointment()...\n";
$reflection = new ReflectionClass($webhookMCP);
$method = $reflection->getMethod('shouldCreateAppointment');
$method->setAccessible(true);

$shouldCreate = $method->invoke($webhookMCP, $testCallData);
echo "Should create appointment: " . ($shouldCreate ? "YES" : "NO") . "\n\n";

if (!$shouldCreate) {
    echo "❌ shouldCreateAppointment returned false!\n";
    echo "Dynamic vars:\n";
    print_r($testCallData['retell_llm_dynamic_variables']);
    exit;
}

echo "2. Creating test call record...\n";
// Create a test customer first
$customer = Customer::withoutGlobalScopes()->firstOrCreate(
    ['phone' => '+491234567890'],
    [
        'name' => 'Debug Test Customer',
        'email' => 'debug@test.com',
        'company_id' => 1
    ]
);

// Create call record
$call = new Call();
$call->call_id = $testCallData['call_id'];
$call->company_id = 1;
$call->branch_id = '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793';
$call->customer_id = $customer->id;
$call->from_number = $testCallData['from_number'];
$call->to_number = $testCallData['to_number'];
$call->extracted_date = '2025-06-28';
$call->extracted_time = '10:00';
$call->retell_dynamic_variables = json_encode($testCallData['retell_llm_dynamic_variables']);
$call->saveQuietly();

echo "Call created with ID: {$call->id}\n\n";

echo "3. Testing createAppointmentViaMCP()...\n";

$phoneResolution = [
    'company_id' => 1,
    'branch_id' => '14b9996c-4ebe-11f0-b9c1-0ad77e7a9793',
    'calcom_event_type_id' => 2563193
];

try {
    $createMethod = $reflection->getMethod('createAppointmentViaMCP');
    $createMethod->setAccessible(true);
    
    // Enable detailed logging
    Log::info('Starting createAppointmentViaMCP test', [
        'call_id' => $call->id,
        'phone_resolution' => $phoneResolution
    ]);
    
    $result = $createMethod->invoke($webhookMCP, $call, $testCallData, $phoneResolution);
    
    echo "\nResult:\n";
    print_r($result);
    
    if ($result && isset($result['id'])) {
        echo "\n✅ APPOINTMENT CREATED SUCCESSFULLY!\n";
        echo "Appointment ID: " . $result['id'] . "\n";
        echo "Cal.com Booking ID: " . ($result['calcom_booking_id'] ?? 'N/A') . "\n";
    } else {
        echo "\n❌ APPOINTMENT CREATION FAILED\n";
        
        // Check if there's a Cal.com booking despite local failure
        echo "\nChecking for orphaned Cal.com bookings...\n";
        $bookings = $calcomMCP->getBookings([
            'company_id' => 1,
            'date_from' => '2025-06-28',
            'date_to' => '2025-06-28'
        ]);
        
        if (!empty($bookings['bookings'])) {
            echo "Found " . count($bookings['bookings']) . " bookings on that date\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Cleanup
echo "\n4. Cleaning up test data...\n";
$call->delete();

echo "\n" . str_repeat('=', 60) . "\n";