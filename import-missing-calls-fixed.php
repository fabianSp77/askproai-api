<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Branch;
use App\Models\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "=== IMPORT FEHLENDE CALLS (FIXED) ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// Hole fehlende Calls von Retell
$apiKey = config('services.retell.api_key');
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->post('https://api.retellai.com/v2/list-calls', [
    'limit' => 20,
    'sort_order' => 'descending'
]);

if (!$response->successful()) {
    echo "❌ Retell API Fehler: " . $response->status() . "\n";
    exit(1);
}

$calls = $response->json();
$imported = 0;
$skipped = 0;
$failed = 0;

foreach ($calls as $callData) {
    $callId = $callData['call_id'];
    
    // Prüfe ob Call existiert
    $exists = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', $callId)
        ->exists();
        
    if ($exists) {
        $skipped++;
        continue;
    }
    
    echo "\nImportiere Call: $callId\n";
    echo "  Von: " . ($callData['from_number'] ?? 'unknown') . "\n";
    echo "  Nach: " . ($callData['to_number'] ?? 'unknown') . "\n";
    
    try {
        // Finde Branch
        $toNumber = $callData['to_number'] ?? $callData['to'] ?? null;
        $branch = null;
        
        if ($toNumber) {
            $cleanNumber = preg_replace('/[^0-9+]/', '', $toNumber);
            
            // Direkte Suche
            $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('phone_number', $cleanNumber)
                ->first();
                
            if (!$branch) {
                // Suche mit LIKE
                $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('phone_number', 'LIKE', '%' . substr($cleanNumber, -10) . '%')
                    ->first();
            }
        }
        
        if (!$branch) {
            echo "  ⚠️ Keine Branch gefunden für: $toNumber\n";
            echo "  Verwende Default Branch\n";
            $branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)->first();
        } else {
            echo "  Branch: " . $branch->name . " (Company: " . $branch->company_id . ")\n";
        }
        
        if (!$branch) {
            throw new \Exception("Keine Branch verfügbar");
        }
        
        // Erstelle Call mit korrekten Feldnamen
        $callRecord = [
            'call_id' => $callId,
            'retell_call_id' => $callId,
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'from_number' => $callData['from_number'] ?? 'unknown',
            'to_number' => $toNumber ?? 'unknown',
            'direction' => 'inbound',
            'call_status' => $callData['call_status'] ?? 'ended',
            'start_timestamp' => isset($callData['start_timestamp']) 
                ? date('Y-m-d H:i:s', $callData['start_timestamp'] / 1000)
                : now()->subSeconds($callData['duration'] ?? 0),
            'end_timestamp' => isset($callData['end_timestamp']) 
                ? date('Y-m-d H:i:s', $callData['end_timestamp'] / 1000)
                : now(),
            'duration_sec' => $callData['duration'] ?? 0,  // WICHTIG: duration_sec statt duration_seconds
            'duration_minutes' => round(($callData['duration'] ?? 0) / 60, 2),
            'recording_url' => $callData['recording_url'] ?? null,
            'transcript' => $callData['transcript'] ?? null,
            'agent_id' => $callData['agent_id'] ?? null,
            'metadata' => json_encode($callData['metadata'] ?? []),
            'raw_data' => json_encode($callData),
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        // Analyse hinzufügen wenn vorhanden
        if (isset($callData['transcript']) && !empty($callData['transcript'])) {
            $callRecord['analysis'] = json_encode([
                'entities' => [],
                'sentiment' => 'neutral',
                'important_phrases' => []
            ]);
            $callRecord['sentiment'] = 'neutral';
        }
        
        // Insert mit direktem DB Query
        DB::table('calls')->insert($callRecord);
        
        echo "  ✅ Call importiert!\n";
        $imported++;
        
        // Webhook Event erstellen
        DB::table('webhook_events')->insert([
            'event_type' => 'call_ended',
            'event_id' => $callId,
            'idempotency_key' => $callId . '_imported',
            'payload' => json_encode($callData),
            'provider' => 'retell',
            'status' => 'processed',
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
    } catch (\Exception $e) {
        echo "  ❌ Fehler: " . $e->getMessage() . "\n";
        $failed++;
        Log::error('Import fehlgeschlagen', [
            'call_id' => $callId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "ZUSAMMENFASSUNG:\n";
echo "- Importiert: $imported\n";
echo "- Übersprungen: $skipped (bereits vorhanden)\n";
echo "- Fehlgeschlagen: $failed\n";

// Zeige aktuelle Calls
echo "\nAKTUELLE CALLS (letzte 10):\n";
echo str_repeat("-", 40) . "\n";

$recentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

foreach ($recentCalls as $call) {
    echo sprintf(
        "- %s | %s | %s | %ds\n",
        $call->created_at->format('H:i:s'),
        substr($call->call_id, -12),
        $call->call_status,
        $call->duration_sec
    );
}

echo "\n✅ FERTIG!\n";