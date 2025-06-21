<?php

// Test Cal.com V1 booking directly

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;

$company = Company::find(85);
$branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->find('7362c5a9-7d2b-46cd-9bcb-d69f6a60c73b');

// Use direct API key from .env as company key is encrypted
$apiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920';
$eventTypeId = $branch->calcom_event_type_id;

echo "=== Testing Cal.com V1 Booking ===\n\n";
echo "Company: " . $company->name . "\n";
echo "Branch: " . $branch->name . "\n";
echo "API Key: " . substr($apiKey, 0, 20) . "...\n";
echo "Event Type ID: " . $eventTypeId . "\n\n";

// 1. First check event type exists
echo "1. Checking Event Type...\n";
$ch = curl_init("https://api.cal.com/v1/event-types?apiKey=$apiKey");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    $found = false;
    foreach (($data['event_types'] ?? []) as $et) {
        if ($et['id'] == $eventTypeId) {
            echo "✅ Event Type found: " . $et['title'] . "\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "❌ Event Type $eventTypeId not found!\n";
    }
} else {
    echo "❌ Failed to get event types: HTTP $httpCode\n";
}

// 2. Get availability
echo "\n2. Getting Available Slots...\n";
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$nextWeek = date('Y-m-d', strtotime('+7 days'));

$availUrl = "https://api.cal.com/v1/availability?" . http_build_query([
    'apiKey' => $apiKey,
    'eventTypeId' => $eventTypeId,
    'dateFrom' => $tomorrow,
    'dateTo' => $nextWeek,
    'timeZone' => 'Europe/Berlin'
]);

$ch = curl_init($availUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$firstSlot = null;
if ($httpCode === 200) {
    $data = json_decode($response, true);
    $slots = $data['slots'] ?? [];
    echo "✅ Got availability data\n";
    
    // Find first available slot
    foreach ($slots as $date => $times) {
        if (count($times) > 0) {
            $firstSlot = ['date' => $date, 'time' => $times[0]];
            echo "  - First available slot: $date at " . $times[0] . "\n";
            break;
        }
    }
} else {
    echo "❌ Failed to get availability: HTTP $httpCode\n";
}

// 3. Try booking
if ($firstSlot) {
    echo "\n3. Attempting Booking...\n";
    
    $bookingData = [
        'eventTypeId' => (int)$eventTypeId,
        'start' => $firstSlot['date'] . 'T' . $firstSlot['time'] . ':00+02:00',
        'responses' => [
            'name' => 'Test Kunde von MCP',
            'email' => 'test-mcp@example.com',
            'phone' => '+491234567890',
            'notes' => 'Automatischer Test-Termin'
        ],
        'timeZone' => 'Europe/Berlin',
        'language' => 'de',
        'metadata' => [
            'source' => 'mcp_test',
            'branch_id' => $branch->id
        ]
    ];
    
    echo "Booking data:\n";
    echo "  - Date/Time: " . $bookingData['start'] . "\n";
    echo "  - Customer: " . $bookingData['responses']['name'] . "\n";
    
    $ch = curl_init("https://api.cal.com/v1/bookings?apiKey=$apiKey");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bookingData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "\nResponse (HTTP $httpCode):\n";
    
    if ($httpCode === 200 || $httpCode === 201) {
        $result = json_decode($response, true);
        echo "✅ BOOKING SUCCESSFUL!\n";
        echo "  - Booking ID: " . ($result['id'] ?? 'unknown') . "\n";
        echo "  - UID: " . ($result['uid'] ?? 'unknown') . "\n";
    } else {
        echo "❌ Booking failed!\n";
        echo substr($response, 0, 500) . "\n";
    }
} else {
    echo "\n❌ No available slots found for booking test\n";
}