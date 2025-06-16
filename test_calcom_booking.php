<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomService;

// Test verschiedene Kombinationen
$tests = [
    [
        'name' => 'Test 1: Mit User-ID',
        'includeUserId' => true
    ],
    [
        'name' => 'Test 2: Ohne User-ID',
        'includeUserId' => false
    ]
];

foreach ($tests as $test) {
    echo "\n=== {$test['name']} ===\n";
    
    try {
        $calcomService = new CalcomService();
        
        $apiKey = env('CALCOM_API_KEY');
        $userId = $test['includeUserId'] ? env('CALCOM_USER_ID') : null;
        $eventTypeId = env('CALCOM_EVENT_TYPE_ID');
        
        // Montag, 9. Juni 2025 um 14:00 Uhr
        $bookingDate = new DateTime('2025-06-09');
        $bookingDate->setTime(14, 0, 0);
        $startDateTime = $bookingDate->format('Y-m-d\TH:i:sP');
        
        $name = 'Test Buchung ' . date('H:i:s');
        $email = 'fabianspitzer@icloud.com';
        
        echo "UserId: " . ($userId ?: 'NICHT GESETZT') . "\n";
        echo "EventTypeId: $eventTypeId\n";
        echo "Zeit: $startDateTime\n\n";
        
        $result = $calcomService->createBookingWithConfirmation(
            $apiKey,
            $userId,
            $eventTypeId,
            $startDateTime,
            $name,
            $email,
            1
        );
        
        if (isset($result['success']) && $result['success']) {
            echo "✅ ERFOLG!\n";
            echo "Buchungs-ID: " . $result['booking']['id'] . "\n";
        } else {
            echo "❌ FEHLER: " . ($result['message'] ?? 'Unbekannt') . "\n";
            if (isset($result['details'])) {
                echo "Details: " . json_encode($result['details'], JSON_PRETTY_PRINT) . "\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    sleep(2); // Kurze Pause zwischen Tests
}
