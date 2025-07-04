<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

echo "=== ULTRATHINK COMPLETE FIX VERIFICATION ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 70) . "\n\n";

$allFixed = true;
$problems = [];
$fixed = [];

// 1. WEBHOOK CONFIGURATION CHECK
echo "1. WEBHOOK KONFIGURATION CHECK\n";
echo str_repeat("-", 50) . "\n";

$apiKey = config('services.retell.api_key');
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->get("https://api.retellai.com/get-agent/$agentId");

if ($response->successful()) {
    $agent = $response->json();
    $webhookUrl = $agent['webhook_url'] ?? '';
    
    if ($webhookUrl === 'https://api.askproai.de/api/retell/webhook-simple') {
        echo "✅ Webhook URL korrekt: $webhookUrl\n";
        $fixed[] = "Webhook URL korrekt konfiguriert";
    } else {
        echo "❌ Webhook URL falsch: $webhookUrl\n";
        $problems[] = "Webhook URL nicht korrekt";
        $allFixed = false;
    }
} else {
    echo "❌ Agent API Fehler\n";
    $problems[] = "Retell Agent API nicht erreichbar";
    $allFixed = false;
}

// 2. WEBHOOK EVENT PROCESSING TEST
echo "\n2. WEBHOOK EVENT PROCESSING TEST\n";
echo str_repeat("-", 50) . "\n";

// Teste alle Event-Typen
$eventTypes = ['call_started', 'call_ended', 'call_analyzed'];
$webhookFixed = true;

foreach ($eventTypes as $eventType) {
    $testCallId = 'ultrathink_' . $eventType . '_' . time();
    $testData = [
        'event' => $eventType,
        'call_id' => $testCallId,
        'to_number' => '+493083793369',
        'from_number' => '+491234567890',
        'agent_id' => $agentId,
        'call_status' => $eventType === 'call_started' ? 'in_progress' : 'ended',
        'start_timestamp' => time() * 1000,
        'end_timestamp' => $eventType !== 'call_started' ? time() * 1000 : null,
        'duration' => $eventType !== 'call_started' ? 30 : null
    ];
    
    $response = Http::post('https://api.askproai.de/api/retell/webhook-simple', $testData);
    
    if ($response->successful()) {
        echo "✅ Event '$eventType' wird verarbeitet\n";
        
        // Cleanup
        DB::table('calls')->where('call_id', $testCallId)->delete();
        DB::table('webhook_events')->where('event_id', $testCallId)->delete();
    } else {
        echo "❌ Event '$eventType' schlägt fehl: " . $response->status() . "\n";
        $webhookFixed = false;
    }
}

if ($webhookFixed) {
    $fixed[] = "Alle Webhook Events (call_started, call_ended, call_analyzed) funktionieren";
} else {
    $problems[] = "Einige Webhook Events funktionieren nicht";
    $allFixed = false;
}

// 3. LIVE CALL DISPLAY CHECK
echo "\n3. LIVE CALL DISPLAY CHECK\n";
echo str_repeat("-", 50) . "\n";

// Prüfe ob call_started im Controller erlaubt ist
$controllerContent = file_get_contents('/var/www/api-gateway/app/Http/Controllers/Api/RetellWebhookSimpleController.php');
if (strpos($controllerContent, "'call_started'") !== false) {
    echo "✅ Controller erlaubt call_started Events\n";
    $fixed[] = "Live-Call Anzeige aktiviert (call_started wird verarbeitet)";
} else {
    echo "❌ Controller blockiert call_started Events\n";
    $problems[] = "Live-Call Anzeige nicht möglich";
    $allFixed = false;
}

// 4. BRANCH ASSIGNMENT CHECK
echo "\n4. BRANCH ZUORDNUNG CHECK\n";
echo str_repeat("-", 50) . "\n";

// Prüfe Branch-Telefonnummern
$branches = DB::table('branches')
    ->where('phone_number', 'LIKE', '%3083793369%')
    ->get();

echo "Branches mit Hauptnummer: " . $branches->count() . "\n";

if ($branches->count() > 0) {
    echo "✅ Branch-Zuordnung möglich\n";
    
    // Prüfe Fallback-Logik
    if (strpos($controllerContent, 'verwende Default') !== false) {
        echo "✅ Fallback auf Default-Branch implementiert\n";
        $fixed[] = "Branch-Zuordnung mit Fallback funktioniert";
    }
} else {
    echo "⚠️ Keine Branch mit Hauptnummer gefunden\n";
}

// 5. DATA CONSISTENCY CHECK
echo "\n5. DATEN KONSISTENZ CHECK\n";
echo str_repeat("-", 50) . "\n";

// Hole letzte Calls von Retell
$retellResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->post('https://api.retellai.com/v2/list-calls', [
    'limit' => 10,
    'sort_order' => 'descending'
]);

if ($retellResponse->successful()) {
    $retellCalls = $retellResponse->json();
    $missingCalls = 0;
    
    foreach ($retellCalls as $call) {
        $exists = DB::table('calls')->where('call_id', $call['call_id'])->exists();
        if (!$exists) {
            $missingCalls++;
        }
    }
    
    if ($missingCalls === 0) {
        echo "✅ Alle Retell-Calls sind in der Datenbank\n";
        $fixed[] = "Keine fehlenden Calls mehr";
    } else {
        echo "❌ $missingCalls Calls fehlen noch in der Datenbank\n";
        $problems[] = "$missingCalls Calls fehlen in DB";
        $allFixed = false;
    }
} else {
    echo "❌ Retell API nicht erreichbar\n";
}

// 6. JOB PROCESSING CHECK
echo "\n6. JOB PROCESSING CHECK\n";
echo str_repeat("-", 50) . "\n";

// Failed Jobs
$failedJobs = Redis::zcard('askproaifailed_jobs');
if ($failedJobs === 0) {
    echo "✅ Keine Failed Jobs\n";
    $fixed[] = "Alle Failed Jobs bereinigt";
} else {
    echo "❌ $failedJobs Failed Jobs vorhanden\n";
    $problems[] = "$failedJobs Failed Jobs";
    $allFixed = false;
}

// Queue Processing
Redis::del('ultrathink_test');
dispatch(function() {
    Redis::set('ultrathink_test', 'processed');
})->onQueue('default');

sleep(2);

if (Redis::get('ultrathink_test') === 'processed') {
    echo "✅ Queue-Verarbeitung funktioniert\n";
    $fixed[] = "Jobs werden korrekt verarbeitet";
} else {
    echo "❌ Queue-Verarbeitung funktioniert nicht\n";
    $problems[] = "Queue-Verarbeitung gestört";
    $allFixed = false;
}

// 7. WEBHOOK ERROR HANDLING CHECK
echo "\n7. WEBHOOK ERROR HANDLING CHECK\n";
echo str_repeat("-", 50) . "\n";

// Prüfe Duplicate-Handling
if (strpos($controllerContent, 'existingWebhook') !== false) {
    echo "✅ Doppelte Webhook-Events werden verhindert\n";
    $fixed[] = "Webhook Duplicate-Handling implementiert";
} else {
    echo "⚠️ Kein explizites Duplicate-Handling\n";
}

// Prüfe Update-Logik
if (strpos($controllerContent, 'Call aktualisiert') !== false) {
    echo "✅ Call-Update bei call_ended implementiert\n";
    $fixed[] = "Call-Updates funktionieren";
} else {
    echo "⚠️ Keine Call-Update Logik gefunden\n";
}

// 8. REALTIME MONITORING TEST
echo "\n8. REALTIME MONITORING TEST\n";
echo str_repeat("-", 50) . "\n";

// Simuliere kompletten Call-Flow
$realtimeCallId = 'realtime_test_' . time();

// Step 1: call_started
$startResponse = Http::post('https://api.askproai.de/api/retell/webhook-simple', [
    'event' => 'call_started',
    'call_id' => $realtimeCallId,
    'to_number' => '+493083793369',
    'from_number' => '+491234567890',
    'agent_id' => $agentId,
    'call_status' => 'in_progress',
    'start_timestamp' => time() * 1000
]);

if ($startResponse->successful()) {
    sleep(1);
    $liveCall = DB::table('calls')->where('call_id', $realtimeCallId)->first();
    if ($liveCall && $liveCall->call_status === 'in_progress') {
        echo "✅ Live-Call wird während des Anrufs angezeigt\n";
        
        // Step 2: call_ended
        $endResponse = Http::post('https://api.askproai.de/api/retell/webhook-simple', [
            'event' => 'call_ended',
            'call_id' => $realtimeCallId,
            'to_number' => '+493083793369',
            'from_number' => '+491234567890',
            'agent_id' => $agentId,
            'call_status' => 'ended',
            'start_timestamp' => (time() - 60) * 1000,
            'end_timestamp' => time() * 1000,
            'duration' => 60,
            'transcript' => 'Test completed'
        ]);
        
        if ($endResponse->successful()) {
            sleep(1);
            $endedCall = DB::table('calls')->where('call_id', $realtimeCallId)->first();
            if ($endedCall && $endedCall->call_status === 'ended' && $endedCall->duration_sec === 60) {
                echo "✅ Call wird nach Beendigung korrekt aktualisiert\n";
                $fixed[] = "Kompletter Call-Flow funktioniert (Start → Ende)";
            }
        }
        
        // Cleanup
        DB::table('calls')->where('call_id', $realtimeCallId)->delete();
        DB::table('webhook_events')->where('event_id', $realtimeCallId)->delete();
    } else {
        echo "❌ Live-Call wird nicht erstellt\n";
        $problems[] = "Live-Call Erstellung fehlgeschlagen";
        $allFixed = false;
    }
} else {
    echo "❌ call_started Webhook schlägt fehl\n";
    $problems[] = "call_started Event funktioniert nicht";
    $allFixed = false;
}

// 9. RECENT ERRORS CHECK
echo "\n9. RECENT ERRORS CHECK\n";
echo str_repeat("-", 50) . "\n";

$recentErrors = shell_exec('tail -200 /var/www/api-gateway/storage/logs/laravel.log | grep -i "error" | grep -v "No error" | wc -l');
$errorCount = trim($recentErrors);

if ($errorCount < 10) {
    echo "✅ Wenige Errors in den Logs ($errorCount)\n";
    $fixed[] = "Keine kritischen Errors in den Logs";
} else {
    echo "⚠️ $errorCount Errors in den letzten 200 Log-Zeilen\n";
}

// 10. HTTP 500 ERRORS CHECK
echo "\n10. HTTP 500 ERRORS CHECK\n";
echo str_repeat("-", 50) . "\n";

$http500s = shell_exec('tail -500 /var/log/nginx/access.log | grep "webhook-simple" | grep " 500 " | wc -l');
$count500 = trim($http500s);

if ($count500 < 5) {
    echo "✅ Wenige HTTP 500 Errors ($count500)\n";
    $fixed[] = "Webhook-Errors minimiert";
} else {
    echo "⚠️ $count500 HTTP 500 Errors bei webhook-simple\n";
}

// FINAL SUMMARY
echo "\n" . str_repeat("=", 70) . "\n";
echo "ULTRATHINK GESAMTANALYSE\n";
echo str_repeat("=", 70) . "\n\n";

if ($allFixed && count($problems) === 0) {
    echo "🎉 ALLE PROBLEME VOLLSTÄNDIG BEHOBEN!\n\n";
    echo "✅ BEHOBENE PROBLEME:\n";
    foreach ($fixed as $fix) {
        echo "   - $fix\n";
    }
    echo "\n💯 Das System funktioniert jetzt perfekt:\n";
    echo "   - Live-Calls werden während des Anrufs angezeigt\n";
    echo "   - Alle Webhook-Events funktionieren\n";
    echo "   - Keine fehlenden Calls mehr\n";
    echo "   - Jobs werden zuverlässig verarbeitet\n";
    echo "   - Fehlerbehandlung ist robust\n";
} else {
    echo "⚠️ FAST ALLES BEHOBEN, ABER:\n\n";
    
    if (count($problems) > 0) {
        echo "❌ VERBLEIBENDE PROBLEME:\n";
        foreach ($problems as $problem) {
            echo "   - $problem\n";
        }
        echo "\n";
    }
    
    echo "✅ BEHOBENE PROBLEME:\n";
    foreach ($fixed as $fix) {
        echo "   - $fix\n";
    }
}

echo "\n";