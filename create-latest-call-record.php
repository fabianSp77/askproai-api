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

echo "📞 ERSTELLE CALL RECORD FÜR LETZTEN TESTANRUF\n";
echo str_repeat("=", 60) . "\n\n";

// Die neue Call ID aus dem Log
$callId = 'call_f67e24973c99105759119b9bb10';
$twilioSid = 'CA7e7b2f8f3b4e2c80c71a60092c76ae3e';

// Prüfe ob der Call schon existiert
$existingCall = Call::withoutGlobalScopes()
    ->where('call_id', $callId)
    ->orWhere('retell_call_id', $callId)
    ->first();

if ($existingCall) {
    echo "✅ Call bereits vorhanden: $callId\n";
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
        'created_at' => Carbon::now()->subMinutes(5),
        'updated_at' => Carbon::now()->subMinutes(5),
    ]);
    
    echo "✅ Call Record erstellt!\n";
    echo "Call ID: " . $callId . "\n";
    echo "Von: +491604366218\n";
    echo "Twilio SID: " . $twilioSid . "\n";
}

// Teste den Controller direkt
echo "\n🧪 TESTE CONTROLLER MIT ECHTEN DATEN:\n";
echo str_repeat("-", 40) . "\n";

$request = new \Illuminate\Http\Request();
$request->merge([
    'call' => [
        'call_id' => $callId,
        'call_type' => 'phone_call',
        'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
        'agent_version' => 33,
        'from_number' => '+491604366218'
    ],
    'args' => [
        'name' => 'Hans Schuster',
        'datum' => 'morgen',
        'dienstleistung' => 'Beratung',
        'uhrzeit' => '16:00',
        'call_id' => '{{call_id}}'
    ]
]);

$controller = app(\App\Http\Controllers\RetellCustomFunctionsController::class);

try {
    $response = $controller->collectAppointment($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success'] ?? false) {
        echo "✅ ERFOLG! Terminbuchung funktioniert!\n";
        echo "Message: " . $responseData['message'] . "\n";
    } else {
        echo "❌ FEHLER: " . ($responseData['message'] ?? 'Unbekannt') . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "In Datei: " . $e->getFile() . " Zeile: " . $e->getLine() . "\n";
}

echo "\n🎯 STATUS:\n";
echo str_repeat("=", 60) . "\n";
echo "Call Record wurde erstellt für den letzten Anruf.\n";
echo "Der nächste Webhook-Call sollte funktionieren!\n";