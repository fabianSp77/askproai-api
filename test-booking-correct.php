<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomService;

try {
    $calcomService = new CalcomService();
    
    // Hole API-Daten aus der Umgebung
    $apiKey = env('CAL_COM_API_KEY');
    $userId = env('CAL_COM_USER_ID');
    $eventTypeId = env('CAL_COM_EVENT_TYPE_ID');
    
    // Erstelle Termin für morgen um 15:00 Uhr
    $tomorrow = new DateTime('tomorrow');
    $tomorrow->setTime(15, 0, 0);
    $startDateTime = $tomorrow->format('Y-m-d\TH:i:sP');
    
    // Kundendaten
    $name = 'Test Buchung mit E-Mail';
    $email = 'fabianspitzer@icloud.com';
    
    $result = $calcomService->createBookingWithConfirmation(
        $apiKey,
        $userId,
        $eventTypeId,
        $startDateTime,
        $name,
        $email,
        1 // Company ID
    );
    
    if (isset($result['success']) && $result['success']) {
        echo "✅ Buchung erfolgreich erstellt!\n";
        echo "📧 E-Mail-Bestätigung wurde gesendet!\n";
        echo "📅 Termin: " . $tomorrow->format('d.m.Y') . " um 15:00 Uhr\n";
        if (isset($result['booking']['id'])) {
            echo "🔖 Buchungs-ID: " . $result['booking']['id'] . "\n";
        }
    } else {
        echo "❌ Fehler bei der Buchung\n";
        if (isset($result['error'])) {
            echo "Fehler: " . $result['error'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Details: " . $e->getTraceAsString() . "\n";
}
