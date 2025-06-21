<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test Cal.com V2 booking directly
$calcomService = new \App\Services\CalcomV2Service();

echo "=== Testing Cal.com V2 Booking ===\n\n";

$eventTypeId = 2281004;
$startTime = \Carbon\Carbon::parse('2025-06-27 15:00')->toIso8601String();
$endTime = \Carbon\Carbon::parse('2025-06-27 15:30')->toIso8601String();

$customerData = [
    'name' => 'Test V2 Customer',
    'email' => 'test-v2@example.com',
    'phone' => '+491234567890',
    'timeZone' => 'Europe/Berlin'
];

echo "Event Type ID: $eventTypeId\n";
echo "Start Time: $startTime\n";
echo "End Time: $endTime\n";
echo "Customer: {$customerData['name']}\n\n";

try {
    echo "Attempting booking via CalcomV2Service...\n";
    $result = $calcomService->bookAppointment(
        $eventTypeId,
        $startTime,
        $endTime,
        $customerData,
        'Test booking via V2 API'
    );
    
    if ($result) {
        echo "✅ BOOKING SUCCESS!\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ BOOKING FAILED - API returned null\n";
        
        // Check logs
        $logs = file_get_contents('/var/www/api-gateway/storage/logs/laravel-' . date('Y-m-d') . '.log');
        if (preg_match('/Cal\.com.*error.*403/i', $logs)) {
            echo "\n⚠️  V1 API returned 403 Forbidden - need to use V2 API for bookings\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}