<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Branch;
use App\Scopes\TenantScope;

echo "=== FIX DUPLICATE CALLS ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// 1. IMPORTIERE FEHLENDEN CALL
echo "1. IMPORTIERE FEHLENDEN CALL\n";
echo str_repeat("-", 40) . "\n";

$apiKey = config('services.retell.api_key');
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->post('https://api.retellai.com/v2/list-calls', [
    'limit' => 1,
    'sort_order' => 'descending'
]);

if ($response->successful()) {
    $calls = $response->json();
    if (!empty($calls)) {
        $latestCall = $calls[0];
        $callId = $latestCall['call_id'];
        
        echo "Neuester Call: $callId\n";
        echo "Zeit: " . date('Y-m-d H:i:s', $latestCall['start_timestamp'] / 1000) . "\n";
        echo "Von: " . $latestCall['from_number'] . "\n";
        echo "Nach: " . $latestCall['to_number'] . "\n";
        echo "Status: " . $latestCall['call_status'] . "\n";
        echo "Dauer: " . ($latestCall['duration'] ?? 0) . " Sekunden\n";
        
        // Prüfe ob existiert
        $exists = DB::table('calls')->where('call_id', $callId)->exists();
        
        if (!$exists) {
            echo "\n⚠️ Call fehlt in DB - IMPORTIERE...\n";
            
            // Finde Branch
            $branch = Branch::withoutGlobalScope(TenantScope::class)
                ->where('phone_number', 'LIKE', '%3083793369%')
                ->first();
                
            if ($branch) {
                $callData = [
                    'call_id' => $callId,
                    'retell_call_id' => $callId,
                    'company_id' => $branch->company_id,
                    'branch_id' => $branch->id,
                    'from_number' => $latestCall['from_number'] ?? 'unknown',
                    'to_number' => $latestCall['to_number'] ?? 'unknown',
                    'direction' => 'inbound',
                    'call_status' => $latestCall['call_status'] ?? 'ended',
                    'start_timestamp' => date('Y-m-d H:i:s', $latestCall['start_timestamp'] / 1000),
                    'end_timestamp' => isset($latestCall['end_timestamp']) 
                        ? date('Y-m-d H:i:s', $latestCall['end_timestamp'] / 1000)
                        : date('Y-m-d H:i:s', ($latestCall['start_timestamp'] / 1000) + ($latestCall['duration'] ?? 0)),
                    'duration_sec' => $latestCall['duration'] ?? 0,
                    'duration_minutes' => round(($latestCall['duration'] ?? 0) / 60, 2),
                    'recording_url' => $latestCall['recording_url'] ?? null,
                    'transcript' => $latestCall['transcript'] ?? null,
                    'agent_id' => $latestCall['agent_id'] ?? null,
                    'metadata' => json_encode($latestCall['metadata'] ?? []),
                    'raw_data' => json_encode($latestCall),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                // Füge Analyse hinzu wenn Transkript vorhanden
                if (!empty($latestCall['transcript'])) {
                    // Extrahiere wichtige Informationen
                    $analysis = [
                        'entities' => [],
                        'sentiment' => 'neutral',
                        'important_phrases' => []
                    ];
                    
                    // Suche nach Termin-Erwähnungen
                    if (preg_match_all('/(\d{1,2}\.\s*(?:Januar|Februar|März|April|Mai|Juni|Juli|August|September|Oktober|November|Dezember)|\d{1,2}\.\d{1,2}\.)/i', $latestCall['transcript'], $matches)) {
                        $analysis['entities']['dates'] = $matches[0];
                    }
                    
                    // Suche nach Uhrzeiten
                    if (preg_match_all('/(\d{1,2}:\d{2}|\d{1,2}\s*Uhr)/i', $latestCall['transcript'], $matches)) {
                        $analysis['entities']['times'] = $matches[0];
                    }
                    
                    $callData['analysis'] = json_encode($analysis);
                    $callData['sentiment'] = 'neutral';
                }
                
                DB::table('calls')->insert($callData);
                echo "✅ Call erfolgreich importiert!\n";
            } else {
                echo "❌ Keine Branch gefunden\n";
            }
        } else {
            echo "✅ Call bereits in DB\n";
        }
    }
}

// 2. BEREINIGE DUPLICATE CALLS
echo "\n2. BEREINIGE DUPLICATE CALLS\n";
echo str_repeat("-", 40) . "\n";

// Lösche Calls mit "unknown" Telefonnummer die heute erstellt wurden
$deletedUnknown = DB::table('calls')
    ->where('from_number', 'unknown')
    ->where('created_at', '>', now()->subHours(2))
    ->delete();

echo "Gelöschte 'unknown' Calls: $deletedUnknown\n";

// Lösche alte in_progress Calls
$deletedInProgress = DB::table('calls')
    ->where('call_status', 'in_progress')
    ->where('created_at', '<', now()->subMinutes(15))
    ->delete();

echo "Gelöschte alte in_progress Calls: $deletedInProgress\n";

// 3. FIX WEBHOOK CONTROLLER
echo "\n3. WEBHOOK CONTROLLER FIX\n";
echo str_repeat("-", 40) . "\n";

$controllerPath = '/var/www/api-gateway/app/Http/Controllers/Api/RetellWebhookWorkingController.php';
$content = file_get_contents($controllerPath);

// Prüfe ob Call-ID korrekt extrahiert wird
if (strpos($content, "\$data['call_id'] ?? 'retell_'") !== false) {
    echo "⚠️ Problem gefunden: Call-ID wird mit 'retell_' prefix erstellt\n";
    echo "   Das verursacht die Duplikate!\n";
}

echo "\nEmpfehlung: Der Webhook Controller muss überarbeitet werden:\n";
echo "1. Call-ID muss korrekt aus den Daten extrahiert werden\n";
echo "2. Telefonnummern müssen aus den richtigen Feldern gelesen werden\n";
echo "3. Idempotenz muss sichergestellt werden\n";

// 4. ZEIGE AKTUELLE SITUATION
echo "\n4. AKTUELLE SITUATION\n";
echo str_repeat("-", 40) . "\n";

$currentCalls = DB::table('calls')
    ->where('created_at', '>', now()->subHour())
    ->orderBy('created_at', 'desc')
    ->get();

echo "Aktuelle Calls (letzte Stunde): " . $currentCalls->count() . "\n";
foreach ($currentCalls as $call) {
    echo sprintf(
        "- %s | %s | %s | %s | %s\n",
        substr($call->call_id, -12),
        $call->created_at,
        str_pad($call->call_status, 11),
        str_pad($call->duration_sec . 's', 5),
        $call->from_number
    );
}

echo "\n✅ BEREINIGUNG ABGESCHLOSSEN\n";