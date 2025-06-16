<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Services\CalcomService;

echo "=== Test Branch mit Event-Type (Fixed) ===\n\n";

// Verwende die existierende Branch
$branch = Branch::first();

if ($branch) {
    echo "Verwende Branch: " . $branch->name . "\n";
    echo "Event-Type ID: " . $branch->calcom_event_type_id . "\n\n";
    
    $calcomService = new CalcomService();
    
    $customerData = [
        'name' => 'Branch Test Fixed',
        'email' => 'fabianspitzer@icloud.com',
        'phone' => $branch->phone_number
    ];
    
    $startTime = '2025-06-14T11:00:00+02:00';
    
    try {
        // Stelle sicher, dass branch_id als String übergeben wird
        $booking = $calcomService->createBooking(
            $branch->calcom_event_type_id,
            $startTime,
            $customerData,
            ['branch_id' => (string)$branch->id]
        );
        
        if ($booking) {
            echo "✅ Buchung über Branch erfolgreich!\n";
            echo "Booking ID: " . $booking['id'] . "\n";
            echo "UID: " . $booking['uid'] . "\n";
            echo "Branch: " . $branch->name . "\n";
            echo "Event-Type: " . $branch->calcom_event_type_id . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Fehler: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Keine Branch gefunden\n";
}
