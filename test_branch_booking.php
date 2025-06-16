<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Services\CalcomService;

echo "=== Test Branch mit Event-Type ===\n\n";

// Erste Branch finden oder erstellen
$branch = Branch::first();

if (!$branch) {
    echo "Keine Branch gefunden. Erstelle Test-Branch...\n";
    
    // Prüfe ob Company existiert
    $company = \App\Models\Company::first();
    if (!$company) {
        echo "Erstelle Test-Company...\n";
        $company = \App\Models\Company::create([
            'name' => 'Test Company',
            'industry' => 'Test'
        ]);
    }
    
    $branch = Branch::create([
        'company_id' => $company->id,
        'name' => 'Test Filiale',
        'city' => 'Berlin',
        'phone_number' => '+49123456789',
        'notification_email' => 'test@example.com',
        'calcom_team_slug' => 'askproai',
        'calcom_event_type_id' => '2026302'
    ]);
} else {
    echo "Verwende Branch: " . $branch->name . "\n";
    
    // Event-Type ID setzen falls nicht vorhanden
    if (!$branch->calcom_event_type_id) {
        $branch->calcom_event_type_id = '2026302';
        $branch->save();
        echo "Event-Type ID gesetzt: 2026302\n";
    }
}

// Teste Buchung mit Branch Event-Type
$calcomService = new CalcomService();

$customerData = [
    'name' => 'Branch Test Kunde',
    'email' => 'fabianspitzer@icloud.com',
    'phone' => $branch->phone_number
];

$startTime = '2025-06-13T15:00:00+02:00';

try {
    $booking = $calcomService->createBooking(
        $branch->calcom_event_type_id, 
        $startTime, 
        $customerData,
        ['branch_id' => $branch->id]
    );
    
    if ($booking) {
        echo "\n✅ Buchung über Branch erfolgreich!\n";
        echo "Booking ID: " . $booking['id'] . "\n";
        echo "Branch: " . $branch->name . "\n";
        echo "Event-Type: " . $branch->calcom_event_type_id . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}
