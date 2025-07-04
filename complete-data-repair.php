<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Helpers\RetellDataExtractor;

echo "=== VOLLSTÄNDIGE DATENREPARATUR ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

// Config für größere Batches
$BATCH_SIZE = 20;           // Größere Batches für Effizienz
$MAX_TOTAL_CALLS = 100;     // Limit um API nicht zu überlasten
$DELAY_BETWEEN_BATCHES = 3; // Sekunden zwischen Batches

echo "⚙️ KONFIGURATION:\n";
echo "  Batch-Größe: $BATCH_SIZE Calls\n";
echo "  Max Total: $MAX_TOTAL_CALLS Calls\n";
echo "  Batch-Delay: $DELAY_BETWEEN_BATCHES Sekunden\n\n";

$totalProcessed = 0;
$totalRepaired = 0;
$totalErrors = 0;
$batchNumber = 1;

// Hole API Key
$apiKey = config('services.retell.api_key');
if (!$apiKey) {
    echo "❌ FEHLER: Retell API Key nicht konfiguriert\n";
    exit(1);
}

echo "🚀 STARTE VOLLSTÄNDIGE REPARATUR\n";
echo str_repeat("-", 50) . "\n\n";

while ($totalProcessed < $MAX_TOTAL_CALLS) {
    echo "📦 BATCH $batchNumber\n";
    echo str_repeat("-", 30) . "\n";
    
    // Hole nächste Batch unvollständiger Calls
    $incompleteCalls = DB::select("
        SELECT id, call_id, user_sentiment, agent_name, cost, call_successful, extracted_name
        FROM calls 
        WHERE (user_sentiment IS NULL OR agent_name IS NULL OR call_successful IS NULL)
          AND call_id IS NOT NULL 
          AND call_id LIKE 'call_%'
        ORDER BY created_at DESC
        LIMIT $BATCH_SIZE
    ");
    
    if (empty($incompleteCalls)) {
        echo "✅ Keine weiteren Calls zu reparieren\n";
        break;
    }
    
    echo "Verarbeite " . count($incompleteCalls) . " Calls...\n";
    
    $batchRepaired = 0;
    $batchErrors = 0;
    
    foreach ($incompleteCalls as $call) {
        $totalProcessed++;
        
        try {
            // API Call zu Retell
            $response = Http::timeout(15)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get("https://api.retellai.com/v2/get-call/{$call->call_id}");
            
            if ($response->successful()) {
                $retellData = $response->json();
                $extractedData = RetellDataExtractor::extractCallData($retellData);
                
                // Intelligente Updates nur für fehlende Felder
                $updates = [];
                $values = [];
                
                // Kritische Felder zuerst
                if (!$call->user_sentiment && isset($extractedData['user_sentiment'])) {
                    $updates[] = "user_sentiment = ?";
                    $values[] = $extractedData['user_sentiment'];
                }
                
                if ($call->call_successful === null && isset($extractedData['call_successful'])) {
                    $updates[] = "call_successful = ?";
                    $values[] = $extractedData['call_successful'];
                }
                
                if (!$call->agent_name && isset($extractedData['agent_name'])) {
                    $updates[] = "agent_name = ?";
                    $values[] = $extractedData['agent_name'];
                }
                
                if (!$call->extracted_name && isset($extractedData['extracted_name'])) {
                    $updates[] = "extracted_name = ?";
                    $values[] = $extractedData['extracted_name'];
                }
                
                if (!$call->cost && isset($extractedData['cost'])) {
                    $updates[] = "cost = ?";
                    $values[] = $extractedData['cost'];
                }
                
                // Erweiterte Felder für vollständige Daten
                if (isset($extractedData['end_to_end_latency'])) {
                    $updates[] = "end_to_end_latency = ?";
                    $values[] = $extractedData['end_to_end_latency'];
                }
                
                if (isset($extractedData['analysis']) && is_array($extractedData['analysis'])) {
                    $updates[] = "analysis = ?";
                    $values[] = json_encode($extractedData['analysis']);
                }
                
                if (!empty($updates)) {
                    $values[] = $call->id;
                    $affected = DB::update("UPDATE calls SET " . implode(', ', $updates) . " WHERE id = ?", $values);
                    
                    if ($affected > 0) {
                        $batchRepaired++;
                        $totalRepaired++;
                        echo "  ✅ Call {$call->id}: " . count($updates) . " Felder\n";
                    }
                } else {
                    echo "  ℹ️ Call {$call->id}: Bereits vollständig\n";
                }
                
            } else {
                echo "  ❌ Call {$call->id}: API Error HTTP {$response->status()}\n";
                $batchErrors++;
                $totalErrors++;
            }
            
        } catch (Exception $e) {
            echo "  ❌ Call {$call->id}: " . $e->getMessage() . "\n";
            $batchErrors++;
            $totalErrors++;
        }
        
        // Kurze Pause zwischen API Calls
        usleep(250000); // 0.25 Sekunden
    }
    
    echo "\n📊 Batch $batchNumber Ergebnisse:\n";
    echo "  Repariert: $batchRepaired\n";
    echo "  Fehler: $batchErrors\n";
    echo "  Erfolgsrate: " . ($batchRepaired > 0 ? round(($batchRepaired / ($batchRepaired + $batchErrors)) * 100, 1) : 0) . "%\n\n";
    
    $batchNumber++;
    
    // Pause zwischen Batches
    if ($totalProcessed < $MAX_TOTAL_CALLS && !empty($incompleteCalls)) {
        echo "⏸️ Pause $DELAY_BETWEEN_BATCHES Sekunden...\n\n";
        sleep($DELAY_BETWEEN_BATCHES);
    }
}

// FINAL VERIFICATION
echo "🔍 FINAL VERIFICATION\n";
echo str_repeat("-", 50) . "\n";

$finalStats = DB::selectOne("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN duration_sec > 0 THEN 1 ELSE 0 END) as has_duration,
        SUM(CASE WHEN user_sentiment IS NOT NULL THEN 1 ELSE 0 END) as has_sentiment,
        SUM(CASE WHEN agent_name IS NOT NULL THEN 1 ELSE 0 END) as has_agent,
        SUM(CASE WHEN call_successful IS NOT NULL THEN 1 ELSE 0 END) as has_success,
        SUM(CASE WHEN cost IS NOT NULL THEN 1 ELSE 0 END) as has_cost,
        SUM(CASE WHEN extracted_name IS NOT NULL THEN 1 ELSE 0 END) as has_customer
    FROM calls
");

echo "DATENQUALITÄT NACH REPARATUR:\n";
echo "Total Calls: {$finalStats->total_calls}\n";
echo "Duration: {$finalStats->has_duration} (" . round(($finalStats->has_duration / $finalStats->total_calls) * 100, 1) . "%)\n";
echo "Sentiment: {$finalStats->has_sentiment} (" . round(($finalStats->has_sentiment / $finalStats->total_calls) * 100, 1) . "%)\n";
echo "Agent: {$finalStats->has_agent} (" . round(($finalStats->has_agent / $finalStats->total_calls) * 100, 1) . "%)\n";
echo "Success: {$finalStats->has_success} (" . round(($finalStats->has_success / $finalStats->total_calls) * 100, 1) . "%)\n";
echo "Cost: {$finalStats->has_cost} (" . round(($finalStats->has_cost / $finalStats->total_calls) * 100, 1) . "%)\n";
echo "Customer: {$finalStats->has_customer} (" . round(($finalStats->has_customer / $finalStats->total_calls) * 100, 1) . "%)\n\n";

// SUMMARY
echo "📈 REPARATUR ZUSAMMENFASSUNG\n";
echo str_repeat("-", 50) . "\n";
echo "Verarbeitete Calls: $totalProcessed\n";
echo "Reparierte Calls: $totalRepaired\n";
echo "Fehler: $totalErrors\n";
echo "Gesamt-Erfolgsrate: " . ($totalRepaired > 0 ? round(($totalRepaired / $totalProcessed) * 100, 1) : 0) . "%\n";
echo "Batches verarbeitet: " . ($batchNumber - 1) . "\n\n";

if ($totalErrors == 0) {
    echo "🎉 VOLLSTÄNDIGE REPARATUR ERFOLGREICH!\n";
} else {
    echo "⚠️ REPARATUR MIT EINIGEN FEHLERN ABGESCHLOSSEN\n";
}

echo "\n✅ Vollständige Datenreparatur abgeschlossen\n";