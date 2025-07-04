<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Appointment;
use App\Models\Company;

$company = Company::first();
app()->instance('current_company_id', $company->id);

echo "🗑️ LÖSCHE UNGÜLTIGE TERMINE (ohne Cal.com Booking)\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Finde alle Termine ohne Cal.com Booking
$invalidAppointments = Appointment::withoutGlobalScopes()
    ->whereNull('calcom_booking_id')
    ->whereNull('calcom_v2_booking_id')
    ->where('status', 'scheduled')
    ->get();

echo "Gefundene ungültige Termine: " . $invalidAppointments->count() . "\n\n";

foreach ($invalidAppointments as $appointment) {
    echo "❌ Lösche Termin ID: " . $appointment->id . "\n";
    echo "   Datum: " . ($appointment->starts_at ? $appointment->starts_at->format('Y-m-d H:i') : 'NULL') . "\n";
    echo "   Kunde: " . ($appointment->customer_name ?? 'Unbekannt') . "\n";
    echo "   Erstellt: " . $appointment->created_at->format('Y-m-d H:i:s') . "\n";
    
    // Lösche den Termin
    $appointment->delete();
    echo "   ✅ Gelöscht!\n\n";
}

echo "\n✅ BEREINIGUNG ABGESCHLOSSEN\n";
echo str_repeat("=", 60) . "\n";
echo "Alle Termine ohne Cal.com Booking wurden entfernt.\n";
echo "\n⚠️ WICHTIG:\n";
echo "Cal.com muss korrekt konfiguriert werden:\n";
echo "1. Gültiger API Key in Company Settings\n";
echo "2. Event Type ID in Branch Settings\n";
echo "\nOhne diese Konfiguration können keine Termine gebucht werden!\n";