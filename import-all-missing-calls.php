<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Services\RetellV2Service;
use Carbon\Carbon;

$company = Company::first();
app()->instance('current_company_id', $company->id);

echo "📞 IMPORTIERE ALLE FEHLENDEN RETELL CALLS\n";
echo str_repeat("=", 60) . "\n\n";

// Initialisiere Retell Service
$apiKey = $company->retell_api_key;
// Try to decrypt if encrypted
try {
    $apiKey = decrypt($apiKey);
} catch (\Exception $e) {
    // API key is not encrypted, use as is
}

if (!$apiKey) {
    echo "❌ Keine Retell API Key für Company gefunden\n";
    exit(1);
}

$retellService = new RetellV2Service($apiKey);

echo "1. Hole alle Calls von Retell API...\n";

try {
    // Hole die letzten 50 Calls
    $response = $retellService->listCalls(50);
    
    if (!$response['success']) {
        echo "❌ Fehler beim Abrufen der Calls: " . ($response['error'] ?? 'Unbekannt') . "\n";
        exit(1);
    }
    
    $calls = $response['data'] ?? [];
    echo "✅ " . count($calls) . " Calls von Retell abgerufen\n\n";
    
    $imported = 0;
    $skipped = 0;
    
    foreach ($calls as $retellCall) {
        $callId = $retellCall['call_id'] ?? null;
        
        if (!$callId) {
            continue;
        }
        
        // Prüfe ob Call bereits existiert
        $existingCall = Call::withoutGlobalScopes()
            ->where('call_id', $callId)
            ->orWhere('retell_call_id', $callId)
            ->first();
            
        if ($existingCall) {
            $skipped++;
            continue;
        }
        
        // Importiere den Call
        try {
            \DB::table('calls')->insert([
                'call_id' => $callId,
                'retell_call_id' => $callId,
                'company_id' => $company->id,
                'from_number' => $retellCall['from_number'] ?? 'unknown',
                'to_number' => $retellCall['to_number'] ?? 'unknown',
                'status' => $retellCall['call_status'] ?? 'ended',
                'duration' => $retellCall['call_cost']['total_duration_seconds'] ?? 0,
                'created_at' => isset($retellCall['start_timestamp']) 
                    ? Carbon::createFromTimestampMs($retellCall['start_timestamp']) 
                    : Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            
            $imported++;
            echo "✅ Call importiert: $callId\n";
            echo "   Von: " . ($retellCall['from_number'] ?? 'unknown') . "\n";
            echo "   Start: " . (isset($retellCall['start_timestamp']) 
                ? Carbon::createFromTimestampMs($retellCall['start_timestamp'])->format('Y-m-d H:i:s')
                : 'unknown') . "\n\n";
                
        } catch (\Exception $e) {
            echo "❌ Fehler beim Import von $callId: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 ZUSAMMENFASSUNG:\n";
    echo str_repeat("-", 40) . "\n";
    echo "✅ Importiert: $imported Calls\n";
    echo "⏭️  Übersprungen: $skipped Calls (bereits vorhanden)\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 NÄCHSTE SCHRITTE:\n";
echo str_repeat("=", 60) . "\n";
echo "1. Alle fehlenden Calls wurden importiert\n";
echo "2. Webhook-Calls sollten jetzt funktionieren\n";
echo "3. Für automatischen Import: Cron-Job läuft alle 5 Minuten\n";