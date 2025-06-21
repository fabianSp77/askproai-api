<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Check Cal.com availability
$calcomService = new \App\Services\CalcomV2Service();

echo "=== Checking Cal.com Availability ===\n\n";

$eventTypeId = 2281004;
$startDate = '2024-12-20'; // December 2024
$endDate = '2024-12-27';   // One week

echo "Event Type ID: $eventTypeId\n";
echo "Date Range: $startDate to $endDate\n\n";

try {
    // Get available slots
    $slots = $calcomService->getSlots($eventTypeId, $startDate, $endDate, 'Europe/Berlin');
    
    if ($slots) {
        echo "Available slots:\n";
        
        // Group by date
        $slotsByDate = [];
        foreach ($slots['slots'] ?? [] as $slot) {
            $date = substr($slot, 0, 10);
            if (!isset($slotsByDate[$date])) {
                $slotsByDate[$date] = [];
            }
            $slotsByDate[$date][] = $slot;
        }
        
        foreach ($slotsByDate as $date => $daySlots) {
            $dayName = \Carbon\Carbon::parse($date)->format('l, F j');
            echo "\n$dayName:\n";
            foreach ($daySlots as $slot) {
                $time = \Carbon\Carbon::parse($slot)->format('H:i');
                echo "  - $time\n";
            }
        }
    } else {
        echo "❌ No slots returned\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}