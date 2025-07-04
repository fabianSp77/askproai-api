<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Branch;
use App\Models\Call;
use App\Scopes\TenantScope;

echo "=== IMPORT LATEST CALL ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// Hole die neuesten Calls von Retell
$apiKey = config('services.retell.api_key');
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->post('https://api.retellai.com/v2/list-calls', [
    'limit' => 5,
    'sort_order' => 'descending'
]);

if (!$response->successful()) {
    echo "❌ Retell API Fehler: " . $response->status() . "\n";
    exit(1);
}

$calls = $response->json();

echo "LETZTE 5 ANRUFE VON RETELL:\n";
echo str_repeat("-", 40) . "\n";

foreach ($calls as $i => $call) {
    $time = date('Y-m-d H:i:s', $call['start_timestamp'] / 1000);
    $callId = $call['call_id'];
    $status = $call['call_status'] ?? 'unknown';
    $fromNumber = $call['from_number'] ?? 'unknown';
    
    echo "\n" . ($i + 1) . ". Call: $callId\n";
    echo "   Zeit: $time\n";
    echo "   Status: $status\n";
    echo "   Von: $fromNumber\n";
    echo "   Nach: " . ($call['to_number'] ?? 'unknown') . "\n";
    
    // Prüfe ob in DB
    $exists = Call::withoutGlobalScope(TenantScope::class)
        ->where('call_id', $callId)
        ->exists();
        
    if ($exists) {
        echo "   ✅ Bereits in Datenbank\n";
    } else {
        echo "   ❌ FEHLT in Datenbank!\n";
        
        // Frage ob importieren
        if ($i === 0) { // Nur der neueste
            echo "\n   IMPORTIERE DIESEN CALL...\n";
            
            try {
                // Finde Branch
                $toNumber = $call['to_number'] ?? null;
                $branch = null;
                
                if ($toNumber) {
                    $cleanNumber = preg_replace('/[^0-9+]/', '', $toNumber);
                    
                    // Suche Branch
                    $branch = Branch::withoutGlobalScope(TenantScope::class)
                        ->where('phone_number', 'LIKE', '%' . substr($cleanNumber, -10) . '%')
                        ->first();
                }
                
                if (!$branch) {
                    echo "   ⚠️ Keine Branch gefunden, verwende Default\n";
                    $branch = Branch::withoutGlobalScope(TenantScope::class)
                        ->whereNotNull('company_id')
                        ->first();
                }
                
                if ($branch) {
                    echo "   Branch: " . $branch->name . "\n";
                    
                    // Erstelle Call
                    $callRecord = [
                        'call_id' => $callId,
                        'retell_call_id' => $callId,
                        'company_id' => $branch->company_id,
                        'branch_id' => $branch->id,
                        'from_number' => $call['from_number'] ?? 'unknown',
                        'to_number' => $toNumber ?? 'unknown',
                        'direction' => 'inbound',
                        'call_status' => $call['call_status'] ?? 'ended',
                        'start_timestamp' => isset($call['start_timestamp']) 
                            ? date('Y-m-d H:i:s', $call['start_timestamp'] / 1000)
                            : now(),
                        'end_timestamp' => isset($call['end_timestamp']) 
                            ? date('Y-m-d H:i:s', $call['end_timestamp'] / 1000)
                            : (isset($call['start_timestamp']) 
                                ? date('Y-m-d H:i:s', ($call['start_timestamp'] / 1000) + ($call['duration'] ?? 0))
                                : now()),
                        'duration_sec' => $call['duration'] ?? 0,
                        'duration_minutes' => round(($call['duration'] ?? 0) / 60, 2),
                        'recording_url' => $call['recording_url'] ?? null,
                        'transcript' => $call['transcript'] ?? null,
                        'agent_id' => $call['agent_id'] ?? null,
                        'metadata' => json_encode($call['metadata'] ?? []),
                        'raw_data' => json_encode($call),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    
                    DB::table('calls')->insert($callRecord);
                    
                    echo "   ✅ Call importiert!\n";
                    
                    // Webhook Event erstellen
                    DB::table('webhook_events')->insert([
                        'event_type' => 'call_ended',
                        'event_id' => $callId,
                        'idempotency_key' => $callId . '_manual_import',
                        'payload' => json_encode($call),
                        'provider' => 'retell',
                        'status' => 'processed',
                        'processed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                } else {
                    echo "   ❌ Keine Branch verfügbar\n";
                }
            } catch (\Exception $e) {
                echo "   ❌ Fehler: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Zeige aktuelle Calls in DB
echo "\n\nAKTUELLE CALLS IN DATENBANK (letzte 5):\n";
echo str_repeat("-", 40) . "\n";

$dbCalls = Call::withoutGlobalScope(TenantScope::class)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($dbCalls as $call) {
    echo sprintf(
        "%s | %s | %s | %ds | %s\n",
        $call->created_at->format('H:i:s'),
        substr($call->call_id, -12),
        $call->call_status,
        $call->duration_sec,
        $call->from_number
    );
}

echo "\n✅ FERTIG!\n";