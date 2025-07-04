<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Helpers\RetellDataExtractor;

echo "=== SICHERE DATENREPARATUR AUSFÜHRUNG ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

// 🛡️ SICHERHEITS-KONFIGURATION
$ENABLE_DURATION_REPAIR = true;  // Sehr sicher
$ENABLE_API_SYNC = true;         // Moderate Sicherheit  
$MAX_CALLS_PER_BATCH = 5;        // Kleine Batches
$VALIDATION_ENABLED = true;      // Jeden Schritt validieren

echo "🛡️ SICHERHEITS-EINSTELLUNGEN:\n";
echo "  Duration Repair: " . ($ENABLE_DURATION_REPAIR ? "AKTIVIERT" : "DEAKTIVIERT") . "\n";
echo "  API Sync: " . ($ENABLE_API_SYNC ? "AKTIVIERT" : "DEAKTIVIERT") . "\n";
echo "  Batch-Größe: $MAX_CALLS_PER_BATCH\n";
echo "  Validation: " . ($VALIDATION_ENABLED ? "AKTIVIERT" : "DEAKTIVIERT") . "\n\n";

$totalRepaired = 0;
$totalErrors = 0;

// SCHRITT 1: DURATION REPAIR (SAFEST FIRST)
if ($ENABLE_DURATION_REPAIR) {
    echo "🔧 SCHRITT 1: DURATION REPAIR\n";
    echo str_repeat("-", 50) . "\n";
    
    $durationCalls = DB::select("
        SELECT 
            id, call_id, duration_sec,
            TIMESTAMPDIFF(SECOND, start_timestamp, end_timestamp) as calculated_duration
        FROM calls 
        WHERE (duration_sec IS NULL OR duration_sec = 0)
          AND start_timestamp IS NOT NULL 
          AND end_timestamp IS NOT NULL
          AND TIMESTAMPDIFF(SECOND, start_timestamp, end_timestamp) > 0
        ORDER BY created_at DESC
        LIMIT $MAX_CALLS_PER_BATCH
    ");
    
    echo "Gefunden: " . count($durationCalls) . " Calls\n\n";
    
    foreach ($durationCalls as $call) {
        echo "🔧 Call {$call->id}: {$call->call_id}\n";
        echo "   {$call->duration_sec} → {$call->calculated_duration} sec\n";
        
        try {
            // Sichere Update-Query
            $affected = DB::update("
                UPDATE calls 
                SET duration_sec = ?, duration = ?, duration_minutes = ? 
                WHERE id = ? AND (duration_sec IS NULL OR duration_sec = 0)
            ", [
                $call->calculated_duration,
                $call->calculated_duration, 
                round($call->calculated_duration / 60, 2),
                $call->id
            ]);
            
            if ($affected > 0) {
                echo "   ✅ REPARIERT\n";
                $totalRepaired++;
                
                // Validation
                if ($VALIDATION_ENABLED) {
                    $check = DB::selectOne("SELECT duration_sec FROM calls WHERE id = ?", [$call->id]);
                    if ($check->duration_sec == $call->calculated_duration) {
                        echo "   ✅ VALIDIERT\n";
                    } else {
                        echo "   ❌ VALIDATION FAILED!\n";
                        $totalErrors++;
                    }
                }
            } else {
                echo "   ⚠️ BEREITS REPARIERT\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ FEHLER: " . $e->getMessage() . "\n";
            $totalErrors++;
        }
        
        echo "\n";
    }
}

// SCHRITT 2: API SYNC (NUR WENN SCHRITT 1 ERFOLGREICH)
if ($ENABLE_API_SYNC && $totalErrors == 0) {
    echo "🌐 SCHRITT 2: RETELL API SYNC\n";
    echo str_repeat("-", 50) . "\n";
    
    $apiCalls = DB::select("
        SELECT id, call_id, user_sentiment, agent_name, cost
        FROM calls 
        WHERE (user_sentiment IS NULL OR agent_name IS NULL OR cost IS NULL)
          AND call_id IS NOT NULL 
          AND call_id LIKE 'call_%'
        ORDER BY created_at DESC
        LIMIT $MAX_CALLS_PER_BATCH
    ");
    
    echo "Gefunden: " . count($apiCalls) . " Calls\n\n";
    
    $apiKey = config('services.retell.api_key');
    
    foreach ($apiCalls as $call) {
        echo "🌐 Call {$call->id}: {$call->call_id}\n";
        
        try {
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get("https://api.retellai.com/v2/get-call/{$call->call_id}");
            
            if ($response->successful()) {
                $retellData = $response->json();
                $extractedData = RetellDataExtractor::extractCallData($retellData);
                
                // Sichere Updates nur für NULL-Felder
                $updates = [];
                $values = [];
                
                if (!$call->user_sentiment && isset($extractedData['user_sentiment'])) {
                    $updates[] = "user_sentiment = ?";
                    $values[] = $extractedData['user_sentiment'];
                }
                if (!$call->agent_name && isset($extractedData['agent_name'])) {
                    $updates[] = "agent_name = ?";
                    $values[] = $extractedData['agent_name'];
                }
                if (!$call->cost && isset($extractedData['cost'])) {
                    $updates[] = "cost = ?";
                    $values[] = $extractedData['cost'];
                }
                
                if (!empty($updates)) {
                    $values[] = $call->id;
                    $affected = DB::update("UPDATE calls SET " . implode(', ', $updates) . " WHERE id = ?", $values);
                    
                    if ($affected > 0) {
                        echo "   ✅ " . count($updates) . " Felder aktualisiert\n";
                        $totalRepaired++;
                        
                        // Validation
                        if ($VALIDATION_ENABLED) {
                            $check = DB::selectOne("SELECT user_sentiment, agent_name, cost FROM calls WHERE id = ?", [$call->id]);
                            $validationOk = true;
                            
                            if (isset($extractedData['user_sentiment']) && $check->user_sentiment != $extractedData['user_sentiment']) {
                                $validationOk = false;
                            }
                            
                            if ($validationOk) {
                                echo "   ✅ VALIDIERT\n";
                            } else {
                                echo "   ❌ VALIDATION FAILED!\n";
                                $totalErrors++;
                            }
                        }
                    }
                } else {
                    echo "   ℹ️ Keine Updates nötig\n";
                }
                
            } else {
                echo "   ❌ API Error: HTTP {$response->status()}\n";
                $totalErrors++;
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception: " . $e->getMessage() . "\n";
            $totalErrors++;
        }
        
        echo "\n";
        sleep(1); // Rate limiting
    }
    
} elseif ($totalErrors > 0) {
    echo "⚠️ API SYNC ÜBERSPRUNGEN - Fehler in Schritt 1\n\n";
}

// FINAL SUMMARY
echo "📊 FINAL SUMMARY\n";
echo str_repeat("-", 50) . "\n";
echo "Total Reparierte Calls: $totalRepaired\n";
echo "Total Fehler: $totalErrors\n";
echo "Erfolgsrate: " . ($totalRepaired > 0 ? round(($totalRepaired / ($totalRepaired + $totalErrors)) * 100, 1) : 0) . "%\n\n";

if ($totalErrors == 0) {
    echo "✅ REPARATUR ERFOLGREICH ABGESCHLOSSEN\n";
} else {
    echo "⚠️ REPARATUR MIT FEHLERN ABGESCHLOSSEN\n";
    echo "📋 Bitte Logs prüfen und bei Bedarf Backup wiederherstellen\n";
}

// QUICK VERIFICATION
echo "\n🔍 QUICK VERIFICATION\n";
echo str_repeat("-", 50) . "\n";

$verifyStats = DB::selectOne("
    SELECT 
        COUNT(*) as total_calls,
        SUM(CASE WHEN duration_sec > 0 THEN 1 ELSE 0 END) as has_duration,
        SUM(CASE WHEN user_sentiment IS NOT NULL THEN 1 ELSE 0 END) as has_sentiment,
        SUM(CASE WHEN agent_name IS NOT NULL THEN 1 ELSE 0 END) as has_agent
    FROM calls
");

echo "Total Calls: {$verifyStats->total_calls}\n";
echo "Mit Duration: {$verifyStats->has_duration} (" . round(($verifyStats->has_duration / $verifyStats->total_calls) * 100, 1) . "%)\n";
echo "Mit Sentiment: {$verifyStats->has_sentiment} (" . round(($verifyStats->has_sentiment / $verifyStats->total_calls) * 100, 1) . "%)\n";
echo "Mit Agent: {$verifyStats->has_agent} (" . round(($verifyStats->has_agent / $verifyStats->total_calls) * 100, 1) . "%)\n\n";

echo "✅ Sichere Datenreparatur abgeschlossen\n";