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
    ->first();

if ($existingCall) {
    echo "✅ Call bereits vorhanden\n";
} else {
    // Erstelle den Call
    $call = new Call();
    $call->call_id = $callId;
    $call->company_id = $company->id;
    $call->from_number = '+491604366218';
    $call->to_number = '+493083793369';
    $call->status = 'ongoing';
    $call->duration = 60; // Geschätzt
    $call->created_at = Carbon::now()->subMinutes(10);
    $call->updated_at = Carbon::now()->subMinutes(10);
    
    // Speichere ohne started_at (existiert nicht in der Tabelle)
    $call->save();
    
    echo "✅ Call Record erstellt!\n";
    echo "Call ID: " . $call->call_id . "\n";
    echo "Von: " . $call->from_number . "\n";
}

echo "\n💡 NÄCHSTER SCHRITT:\n";
echo "Der Controller kann jetzt die call_id aus dem 'call' Objekt holen.\n";
echo "Die Telefonnummer wird über die call_id aufgelöst.\n";
echo "\nDer nächste Testanruf sollte funktionieren!\n";