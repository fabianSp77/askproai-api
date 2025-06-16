<?php
require_once 'vendor/autoload.php';

use App\Services\CalcomUnifiedService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=========================================\n";
echo "Cal.com Unified Service Test\n";
echo "=========================================\n\n";

$service = new CalcomUnifiedService();

// 1. Test API connectivity
echo "1. Testing API Connectivity...\n";
$connectionTest = $service->testConnection();
echo "   V1 API: " . ($connectionTest['v1'] ? "✅ Working" : "❌ Not working") . "\n";
echo "   V2 API: " . ($connectionTest['v2'] ? "✅ Working" : "❌ Not working") . "\n";
echo "   Recommended: " . ($connectionTest['recommended_version'] ?? 'None') . "\n\n";

// 2. Test event types retrieval
echo "2. Testing Event Types Retrieval...\n";
$eventTypes = $service->getEventTypes();
if ($eventTypes) {
    echo "   ✅ Successfully retrieved event types\n";
    $count = is_array($eventTypes) ? count($eventTypes) : 0;
    echo "   Found {$count} event types\n";
    
    // Show first event type as example
    if ($count > 0) {
        $first = is_array($eventTypes) ? reset($eventTypes) : null;
        if ($first) {
            echo "   Example: " . ($first['title'] ?? 'N/A') . " (ID: " . ($first['id'] ?? 'N/A') . ")\n";
        }
    }
} else {
    echo "   ❌ Failed to retrieve event types\n";
}
echo "\n";

// 3. Test availability checking
echo "3. Testing Availability Check...\n";
$eventTypeId = 2026302; // Replace with actual event type ID
$tomorrow = \Carbon\Carbon::now()->addDay();
$dateFrom = $tomorrow->copy()->setHour(9)->toIso8601String();
$dateTo = $tomorrow->copy()->setHour(18)->toIso8601String();

echo "   Checking availability for Event Type ID: {$eventTypeId}\n";
echo "   Date range: " . $tomorrow->format('Y-m-d') . " 09:00 - 18:00\n";

$availability = $service->checkAvailability($eventTypeId, $dateFrom, $dateTo);
if ($availability && isset($availability['slots'])) {
    $slotCount = count($availability['slots']);
    echo "   ✅ Found {$slotCount} available slots\n";
    
    if ($slotCount > 0) {
        echo "   First 3 slots:\n";
        $slots = array_slice($availability['slots'], 0, 3);
        foreach ($slots as $slot) {
            $time = isset($slot['time']) ? \Carbon\Carbon::parse($slot['time'])->format('H:i') : 'N/A';
            echo "     - {$time}\n";
        }
    }
} else {
    echo "   ❌ No availability data returned\n";
}
echo "\n";

// 4. Test booking creation (dry run - won't actually book)
echo "4. Testing Booking Creation (Dry Run)...\n";
echo "   ⚠️  Skipping actual booking to avoid creating test appointments\n";
echo "   To test booking, uncomment the code below\n\n";

/*
// Uncomment to test actual booking
$customerData = [
    'name' => 'Test Customer ' . time(),
    'email' => 'test_' . time() . '@example.com',
    'phone' => '+491234567890'
];

if ($availability && isset($availability['slots']) && count($availability['slots']) > 0) {
    $firstSlot = $availability['slots'][0];
    $startTime = $firstSlot['time'];
    
    echo "   Attempting to book slot at: " . \Carbon\Carbon::parse($startTime)->format('Y-m-d H:i') . "\n";
    
    $booking = $service->bookAppointment(
        $eventTypeId,
        $startTime,
        null,
        $customerData,
        'Test booking from unified service'
    );
    
    if ($booking) {
        echo "   ✅ Booking successful!\n";
        echo "   Booking ID: " . ($booking['id'] ?? 'N/A') . "\n";
        echo "   Booking UID: " . ($booking['uid'] ?? 'N/A') . "\n";
        echo "   API Version: " . ($booking['api_version'] ?? 'N/A') . "\n";
    } else {
        echo "   ❌ Booking failed\n";
    }
}
*/

// 5. Configuration summary
echo "5. Current Configuration:\n";
echo "   API Version: " . config('services.calcom.api_version', 'not set') . "\n";
echo "   Fallback Enabled: " . (config('services.calcom.enable_fallback', false) ? 'Yes' : 'No') . "\n";
echo "   Team Slug: " . config('services.calcom.team_slug', 'not set') . "\n";
echo "   V2 API Version: " . config('services.calcom.v2_api_version', 'not set') . "\n";

echo "\n=========================================\n";
echo "Test Complete\n";
echo "=========================================\n\n";

// Recommendations
echo "Recommendations:\n";
if ($connectionTest['v2'] && !$connectionTest['v1']) {
    echo "• Your API key only works with v2. Set CALCOM_API_VERSION=v2 in .env\n";
    echo "• Disable fallback by setting CALCOM_FALLBACK_V1=false\n";
} elseif ($connectionTest['v1'] && !$connectionTest['v2']) {
    echo "• Your API key only works with v1. Set CALCOM_API_VERSION=v1 in .env\n";
} elseif ($connectionTest['v1'] && $connectionTest['v2']) {
    echo "• Both API versions work. Recommend using v2 for future compatibility\n";
    echo "• Enable fallback for maximum reliability: CALCOM_FALLBACK_V1=true\n";
} else {
    echo "• Neither API version is working. Check your API key configuration\n";
}

echo "\n";