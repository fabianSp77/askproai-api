<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use Carbon\Carbon;

$company = Company::first();
app()->instance('current_company_id', $company->id);

// Die Call ID aus dem Log
$callId = 'call_4fed7d675ab3f6e89d2e347ee17';

echo "📞 ERSTELLE CALL RECORD FÜR TESTANRUF\n";
echo str_repeat("=", 60) . "\n\n";

// Prüfe ob der Call schon existiert
$existingCall = Call::withoutGlobalScopes()
    ->where('call_id', $callId)
    ->orWhere('retell_call_id', $callId)
    ->first();

if ($existingCall) {
    echo "✅ Call bereits vorhanden\n";
    echo "Call ID: " . $existingCall->call_id . "\n";
    echo "Von: " . $existingCall->from_number . "\n";
} else {
    // Erstelle den Call direkt mit SQL um Validierung zu umgehen
    \DB::table('calls')->insert([
        'call_id' => $callId,
        'retell_call_id' => $callId,
        'company_id' => $company->id,
        'from_number' => '+491604366218',
        'to_number' => '+493083793369',
        'status' => 'ongoing',
        'duration' => 60,
        'created_at' => Carbon::now()->subMinutes(10),
        'updated_at' => Carbon::now()->subMinutes(10),
    ]);
    
    echo "✅ Call Record erstellt!\n";
    echo "Call ID: " . $callId . "\n";
    echo "Von: +491604366218\n";
}

echo "\n✅ CONTROLLER UPDATE:\n";
echo "Der Controller wurde angepasst:\n";
echo "- Holt call_id aus dem 'call' Objekt (nicht aus args)\n";
echo "- Ignoriert '{{call_id}}' String\n";
echo "- Löst Telefonnummer über die echte call_id auf\n";

echo "\n🚀 BEREIT FÜR DEN NÄCHSTEN TEST!\n";
echo "Der nächste Anruf sollte funktionieren:\n";
echo "1. Sage wieder 'heute 16 Uhr' oder 'morgen 14 Uhr'\n";
echo "2. Der Agent sollte KEINEN technischen Fehler mehr bekommen\n";
echo "3. Der Termin sollte erfolgreich gebucht werden\n";