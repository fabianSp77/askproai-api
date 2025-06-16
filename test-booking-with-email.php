<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomService;

try {
    $calcomService = new CalcomService();
    
    // Test-Buchungsdaten
    $bookingData = [
        'name' => 'E-Mail Test Komplett',
        'email' => 'fabianspitzer@icloud.com',
        'notes' => 'Test mit korrigierter E-Mail-Funktion',
        'date' => '2025-06-06',
        'time' => '10:00'
    ];
    
    $result = $calcomService->createBookingWithConfirmation(
        $bookingData,
        1 // Company ID (erste Company in der Datenbank)
    );
    
    if (isset($result['success']) && $result['success']) {
        echo "✅ Buchung erfolgreich erstellt!\n";
        echo "📧 E-Mail-Bestätigung wurde gesendet!\n";
        echo "📅 Termin: 6. Juni 2025, 10:00 Uhr\n";
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
}
