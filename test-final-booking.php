<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CalcomService;

try {
    $calcomService = new CalcomService();
    
    // API-Daten direkt setzen (aus Ihrer .env)
    $apiKey = 'cal_live_b7bd6c0e98104a0c92fea715566cdb8f36620525';
    $userId = '1414768';
    $eventTypeId = '2026302';
    
    // Erstelle Termin für morgen um 15:00 Uhr
    $tomorrow = new DateTime('tomorrow');
    $tomorrow->setTime(15, 0, 0);
    $startDateTime = $tomorrow->format('Y-m-d\TH:i:sP');
    
    // Kundendaten
    $name = 'Test Buchung Final';
    $email = 'fabianspitzer@icloud.com';
    
    echo "📋 Starte Buchung...\n";
    echo "📅 Datum/Zeit: " . $startDateTime . "\n";
    
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
        echo "\n✅ ERFOLG! Buchung wurde erstellt!\n";
        echo "📧 E-Mail-Bestätigung wurde gesendet an: " . $email . "\n";
        echo "📅 Termin: " . $tomorrow->format('d.m.Y') . " um 15:00 Uhr\n";
        if (isset($result['booking']['id'])) {
            echo "🔖 Buchungs-ID: " . $result['booking']['id'] . "\n";
        }
        if (isset($result['booking']['uid'])) {
            echo "🔗 Buchungs-UID: " . $result['booking']['uid'] . "\n";
        }
        echo "\n✉️  Prüfen Sie Ihr E-Mail-Postfach!\n";
    } else {
        echo "\n❌ Fehler bei der Buchung\n";
        if (isset($result['error'])) {
            echo "Fehlerdetails: " . $result['error'] . "\n";
        }
        print_r($result);
    }
    
} catch (Exception $e) {
    echo "\n❌ Fehler aufgetreten:\n";
    echo "Nachricht: " . $e->getMessage() . "\n";
    echo "Zeile: " . $e->getLine() . "\n";
}

echo "\n📝 Prüfe E-Mail-Queue...\n";
