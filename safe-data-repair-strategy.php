<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Helpers\RetellDataExtractor;
use App\Models\Call;
use App\Scopes\TenantScope;

echo "=== SAFE DATA REPAIR STRATEGY ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

// Config
$DRY_RUN = true; // Sicherheit: Zuerst nur simulieren
$MAX_CALLS_PER_BATCH = 10; // Kleine Batches für Sicherheit
$DELAY_BETWEEN_API_CALLS = 2; // Sekunden zwischen Retell API Calls

echo "🛡️ SICHERHEITS-MODUS: " . ($DRY_RUN ? "DRY RUN (keine Änderungen)" : "LIVE (Änderungen werden gespeichert)") . "\n";
echo "📊 Batch-Größe: $MAX_CALLS_PER_BATCH Calls\n";
echo "⏱️ API-Delay: $DELAY_BETWEEN_API_CALLS Sekunden\n\n";

// PHASE 1: DURATION REPAIR (SAFEST)
echo "🔧 PHASE 1: DURATION REPAIR\n";
echo str_repeat("-", 50) . "\n";

$durationRepairCalls = DB::select("
    SELECT 
        id,
        call_id,
        start_timestamp,
        end_timestamp,
        duration_sec,
        TIMESTAMPDIFF(SECOND, start_timestamp, end_timestamp) as calculated_duration
    FROM calls 
    WHERE (duration_sec IS NULL OR duration_sec = 0)
      AND start_timestamp IS NOT NULL 
      AND end_timestamp IS NOT NULL
      AND TIMESTAMPDIFF(SECOND, start_timestamp, end_timestamp) > 0
    ORDER BY created_at DESC
    LIMIT $MAX_CALLS_PER_BATCH
");

echo "Gefunden: " . count($durationRepairCalls) . " Calls mit reparierbarer Duration\n\n";

foreach ($durationRepairCalls as $call) {
    echo "Call ID {$call->id}: {$call->call_id}\n";
    echo "  Aktuell: {$call->duration_sec} sec\n";
    echo "  Berechnet: {$call->calculated_duration} sec\n";
    
    if (!$DRY_RUN) {
        DB::update("UPDATE calls SET duration_sec = ?, duration = ?, duration_minutes = ? WHERE id = ?", [
            $call->calculated_duration,
            $call->calculated_duration,
            round($call->calculated_duration / 60, 2),
            $call->id
        ]);
        echo "  ✅ REPARIERT\n";
    } else {
        echo "  🔍 WÜRDE REPARIERT (DRY RUN)\n";
    }
    echo "\n";
}

// PHASE 2: RETELL API SYNC (RISKIER)
echo "🌐 PHASE 2: RETELL API SYNC\n";
echo str_repeat("-", 50) . "\n";

$apiSyncCalls = DB::select("
    SELECT 
        id,
        call_id,
        user_sentiment,
        call_successful,
        agent_name,
        cost
    FROM calls 
    WHERE (user_sentiment IS NULL OR call_successful IS NULL OR agent_name IS NULL OR cost IS NULL)
      AND call_id IS NOT NULL 
      AND call_id != ''
      AND call_id NOT LIKE 'direct_test_%'
      AND call_id LIKE 'call_%'
    ORDER BY created_at DESC
    LIMIT $MAX_CALLS_PER_BATCH
");

echo "Gefunden: " . count($apiSyncCalls) . " Calls für Retell API Sync\n\n";

$apiKey = config('services.retell.api_key');
if (!$apiKey) {
    echo "❌ FEHLER: Retell API Key nicht konfiguriert\n";
    exit(1);
}

$successCount = 0;
$errorCount = 0;

foreach ($apiSyncCalls as $call) {
    echo "Call ID {$call->id}: {$call->call_id}\n";
    
    // Retell API Call
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->get("https://api.retellai.com/v2/get-call/{$call->call_id}");
        
        if ($response->successful()) {
            $retellData = $response->json();
            
            echo "  📥 API Response erhalten\n";
            
            // Extract data using our helper
            $extractedData = RetellDataExtractor::extractCallData($retellData);
            
            // Show what we would update
            $updates = [];
            if (!$call->user_sentiment && isset($extractedData['user_sentiment'])) {
                $updates[] = "user_sentiment: {$extractedData['user_sentiment']}";
            }
            if ($call->call_successful === null && isset($extractedData['call_successful'])) {
                $updates[] = "call_successful: " . ($extractedData['call_successful'] ? 'true' : 'false');
            }
            if (!$call->agent_name && isset($extractedData['agent_name'])) {
                $updates[] = "agent_name: " . substr($extractedData['agent_name'], 0, 30) . "...";
            }
            if (!$call->cost && isset($extractedData['cost'])) {
                $updates[] = "cost: $" . $extractedData['cost'];
            }
            
            if (!empty($updates)) {
                echo "  📝 Updates: " . implode(', ', $updates) . "\n";
                
                if (!$DRY_RUN) {
                    // Apply updates safely
                    $updateFields = [];
                    $updateValues = [];
                    
                    foreach ($extractedData as $field => $value) {
                        if ($value !== null && in_array($field, [
                            'user_sentiment', 'call_successful', 'agent_name', 'cost', 
                            'extracted_name', 'end_to_end_latency', 'analysis',
                            'latency_metrics', 'cost_breakdown', 'llm_usage'
                        ])) {
                            $updateFields[] = "$field = ?";
                            $updateValues[] = is_array($value) ? json_encode($value) : $value;
                        }
                    }
                    
                    if (!empty($updateFields)) {
                        $updateValues[] = $call->id;
                        DB::update("UPDATE calls SET " . implode(', ', $updateFields) . " WHERE id = ?", $updateValues);
                        echo "  ✅ DATEN AKTUALISIERT\n";
                    }
                } else {
                    echo "  🔍 WÜRDE AKTUALISIERT (DRY RUN)\n";
                }
                
                $successCount++;
            } else {
                echo "  ℹ️ Keine neuen Daten verfügbar\n";
            }
            
        } else {
            echo "  ❌ API Fehler: HTTP {$response->status()}\n";
            $errorCount++;
        }
        
    } catch (Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    echo "\n";
    
    // Rate limiting
    if ($DELAY_BETWEEN_API_CALLS > 0) {
        sleep($DELAY_BETWEEN_API_CALLS);
    }
}

// SUMMARY
echo "📊 ZUSAMMENFASSUNG\n";
echo str_repeat("-", 50) . "\n";
echo "Duration Repairs: " . count($durationRepairCalls) . " Calls\n";
echo "API Sync Attempts: " . count($apiSyncCalls) . " Calls\n";
echo "  - Erfolgreich: $successCount\n";
echo "  - Fehler: $errorCount\n";
echo "Modus: " . ($DRY_RUN ? "DRY RUN" : "LIVE") . "\n\n";

if ($DRY_RUN) {
    echo "🔄 Um die Reparaturen anzuwenden, setze \$DRY_RUN = false\n";
} else {
    echo "✅ Reparaturen wurden angewendet\n";
}

echo "\n⚠️  SICHERHEITS-HINWEISE:\n";
echo "1. Immer zuerst mit DRY_RUN = true testen\n";
echo "2. Kleine Batch-Größen verwenden\n";
echo "3. Database Backup vor LIVE-Modus erstellen\n";
echo "4. Monitoring der API Rate Limits beachten\n";
echo "5. Bei Fehlern sofort stoppen und analysieren\n\n";

echo "✅ Safe Data Repair Strategy completed\n";