<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

echo "=== ULTRATHINK DEEP SYSTEM CHECK ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

$issues = [];
$warnings = [];

// 1. DEEP HORIZON CHECK
echo "1. DEEP HORIZON CHECK\n";
echo str_repeat("-", 40) . "\n";

// Prüfe Horizon Prozess
$horizonPs = shell_exec('ps aux | grep -E "horizon$" | grep -v grep | wc -l');
if (trim($horizonPs) > 0) {
    echo "✅ Horizon Master-Prozess läuft\n";
} else {
    $issues[] = "Horizon Master-Prozess nicht gefunden!";
    echo "❌ Horizon Master-Prozess nicht gefunden!\n";
}

// Prüfe Worker
$workers = shell_exec('ps aux | grep "horizon:work" | grep -v grep | wc -l');
echo "✅ " . trim($workers) . " Horizon Worker aktiv\n";

// Teste Job-Verarbeitung in Echtzeit
echo "\nTeste Echtzeit-Job-Verarbeitung...\n";
$testId = 'test_' . uniqid();
Redis::set('job_test_' . $testId, 'waiting');

dispatch(new \App\Jobs\HeartbeatJob())->onQueue('default');

sleep(2); // Warte 2 Sekunden

$processed = Redis::get('askproai:heartbeat:last');
if ($processed && (time() - $processed) < 5) {
    echo "✅ Jobs werden in Echtzeit verarbeitet (vor " . (time() - $processed) . " Sekunden)\n";
} else {
    $issues[] = "Jobs werden NICHT verarbeitet!";
    echo "❌ Jobs werden NICHT verarbeitet!\n";
}

// 2. WEBHOOK DEEP CHECK
echo "\n2. WEBHOOK DEEP CHECK\n";
echo str_repeat("-", 40) . "\n";

// Prüfe Retell Agent Konfiguration
$apiKey = config('services.retell.api_key');
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->get("https://api.retellai.com/get-agent/{$agentId}");

if ($response->successful()) {
    $agent = $response->json();
    $webhookUrl = $agent['webhook_url'] ?? '';
    
    if ($webhookUrl === 'https://api.askproai.de/api/retell/webhook-simple') {
        echo "✅ Webhook URL korrekt: $webhookUrl\n";
    } else {
        $issues[] = "Webhook URL falsch: $webhookUrl";
        echo "❌ Webhook URL falsch: $webhookUrl\n";
    }
    
    // Prüfe Phone Numbers
    echo "\nPrüfe Telefonnummern...\n";
    $phoneResponse = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
    ])->post('https://api.retellai.com/v2/list-phone-numbers');
    
    if ($phoneResponse->successful()) {
        $phones = $phoneResponse->json();
        $assignedCount = 0;
        foreach ($phones as $phone) {
            if (!empty($phone['agent_id'])) {
                $assignedCount++;
            }
        }
        echo "✅ $assignedCount von " . count($phones) . " Telefonnummern haben Agenten zugewiesen\n";
        
        if ($assignedCount == 0) {
            $issues[] = "KEINE Telefonnummern haben Agenten zugewiesen!";
        }
    }
} else {
    $issues[] = "Retell API nicht erreichbar!";
    echo "❌ Retell API nicht erreichbar!\n";
}

// 3. LIVE WEBHOOK TEST
echo "\n3. LIVE WEBHOOK TEST\n";
echo str_repeat("-", 40) . "\n";

// Simuliere einen Webhook direkt
$webhookData = [
    'event' => 'call_started',
    'call_id' => 'ultrathink_test_' . time(),
    'to_number' => '+493083793369',
    'from_number' => '+49123456789',
    'agent_id' => $agentId,
    'call_status' => 'in_progress',
    'start_timestamp' => time() * 1000
];

echo "Sende Test-Webhook...\n";
$webhookResponse = Http::post('https://api.askproai.de/api/retell/webhook-simple', $webhookData);

if ($webhookResponse->successful()) {
    echo "✅ Webhook erfolgreich verarbeitet (HTTP " . $webhookResponse->status() . ")\n";
    
    // Prüfe ob Call in DB
    sleep(1);
    $testCall = DB::table('calls')->where('call_id', $webhookData['call_id'])->first();
    if ($testCall) {
        echo "✅ Test-Call wurde in Datenbank gespeichert\n";
    } else {
        $warnings[] = "Test-Call nicht in Datenbank gefunden";
        echo "⚠️ Test-Call nicht in Datenbank gefunden\n";
    }
} else {
    $issues[] = "Webhook fehlgeschlagen: HTTP " . $webhookResponse->status();
    echo "❌ Webhook fehlgeschlagen: HTTP " . $webhookResponse->status() . "\n";
    echo "Response: " . $webhookResponse->body() . "\n";
}

// 4. DATABASE INTEGRITY CHECK
echo "\n4. DATABASE INTEGRITY CHECK\n";
echo str_repeat("-", 40) . "\n";

// Prüfe wichtige Constraints
$constraints = DB::select("
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        CONSTRAINT_TYPE
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'askproai_db'
    AND CONSTRAINT_TYPE IN ('PRIMARY KEY', 'FOREIGN KEY', 'UNIQUE')
    AND TABLE_NAME IN ('calls', 'webhook_events', 'appointments')
");
echo "✅ " . count($constraints) . " Datenbank-Constraints aktiv\n";

// Prüfe auf Duplikate
$duplicateCalls = DB::select("
    SELECT call_id, COUNT(*) as count 
    FROM calls 
    GROUP BY call_id 
    HAVING count > 1
");
if (count($duplicateCalls) > 0) {
    $issues[] = count($duplicateCalls) . " duplizierte Call IDs gefunden!";
    echo "❌ " . count($duplicateCalls) . " duplizierte Call IDs!\n";
} else {
    echo "✅ Keine duplizierten Call IDs\n";
}

// 5. SCHEDULED TASKS CHECK
echo "\n5. SCHEDULED TASKS CHECK\n";
echo str_repeat("-", 40) . "\n";

// Prüfe ob schedule:run kürzlich ausgeführt wurde
$lastScheduleRun = Redis::get('askproai:schedule:last_run');
if ($lastScheduleRun && (time() - $lastScheduleRun) < 120) {
    echo "✅ Schedule läuft (letzter Run vor " . (time() - $lastScheduleRun) . " Sekunden)\n";
} else {
    // Setze Marker für nächsten Run
    Redis::setex('askproai:schedule:check', 180, time());
    
    // Führe schedule:run manuell aus
    shell_exec('cd /var/www/api-gateway && php artisan schedule:run >> /dev/null 2>&1 &');
    echo "⚠️ Schedule möglicherweise inaktiv - manueller Run gestartet\n";
    $warnings[] = "Schedule möglicherweise inaktiv";
}

// 6. QUEUE PERFORMANCE CHECK
echo "\n6. QUEUE PERFORMANCE CHECK\n";
echo str_repeat("-", 40) . "\n";

// Messe Queue-Latenz
$startTime = microtime(true);
$jobId = uniqid();
Redis::set("latency_test_$jobId", 'waiting');

dispatch(function() use ($jobId) {
    Redis::set("latency_test_$jobId", 'processed');
})->onQueue('default');

sleep(1);

if (Redis::get("latency_test_$jobId") === 'processed') {
    $latency = round((microtime(true) - $startTime) * 1000);
    echo "✅ Queue-Latenz: {$latency}ms\n";
    if ($latency > 5000) {
        $warnings[] = "Queue-Latenz sehr hoch: {$latency}ms";
    }
} else {
    $issues[] = "Queue verarbeitet keine Jobs!";
    echo "❌ Queue verarbeitet keine Jobs!\n";
}

// 7. ERROR LOG ANALYSIS
echo "\n7. ERROR LOG ANALYSIS\n";
echo str_repeat("-", 40) . "\n";

$recentErrors = shell_exec('tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep -i "error" | wc -l');
$errorCount = trim($recentErrors);
echo "Gefundene Errors in den letzten 100 Log-Zeilen: $errorCount\n";

if ($errorCount > 10) {
    $warnings[] = "$errorCount Errors in Recent Logs";
    echo "⚠️ Viele Errors gefunden!\n";
} else {
    echo "✅ Wenige Errors\n";
}

// 8. NGINX ERROR CHECK
echo "\n8. NGINX ERROR CHECK\n";
echo str_repeat("-", 40) . "\n";

$nginx5xx = shell_exec('tail -1000 /var/log/nginx/access.log | grep -E " 5[0-9]{2} " | wc -l');
$count5xx = trim($nginx5xx);
echo "HTTP 5xx Errors in den letzten 1000 Requests: $count5xx\n";

if ($count5xx > 20) {
    $warnings[] = "$count5xx HTTP 5xx Errors";
    echo "⚠️ Viele 5xx Errors!\n";
} else {
    echo "✅ Wenige 5xx Errors\n";
}

// 9. MEMORY & DISK CHECK
echo "\n9. MEMORY & DISK CHECK\n";
echo str_repeat("-", 40) . "\n";

// Memory
$memInfo = shell_exec('free -m | grep Mem');
if (preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $memInfo, $matches)) {
    $totalMem = $matches[1];
    $usedMem = $matches[2];
    $memPercent = round(($usedMem / $totalMem) * 100);
    echo "Memory: $memPercent% verwendet ($usedMem MB von $totalMem MB)\n";
    
    if ($memPercent > 90) {
        $issues[] = "Memory fast voll: $memPercent%";
        echo "❌ Memory kritisch!\n";
    } else {
        echo "✅ Memory OK\n";
    }
}

// Disk
$diskUsage = shell_exec('df -h / | tail -1 | awk \'{print $5}\'');
$diskPercent = intval($diskUsage);
echo "Disk: $diskPercent% verwendet\n";

if ($diskPercent > 90) {
    $issues[] = "Disk fast voll: $diskPercent%";
    echo "❌ Disk kritisch!\n";
} else {
    echo "✅ Disk OK\n";
}

// 10. LIVE CALL MONITORING CHECK
echo "\n10. LIVE CALL MONITORING CHECK\n";
echo str_repeat("-", 40) . "\n";

// Prüfe ob call_started Events verarbeitet werden
$liveCallsEnabled = DB::table('calls')
    ->where('call_status', 'in_progress')
    ->where('created_at', '>', now()->subMinutes(30))
    ->exists();

if ($liveCallsEnabled) {
    echo "✅ Live-Call Monitoring scheint aktiv\n";
} else {
    echo "⚠️ Keine aktiven Calls in den letzten 30 Minuten\n";
    $warnings[] = "Live-Call Monitoring nicht verifiziert";
}

// FINAL SUMMARY
echo "\n" . str_repeat("=", 60) . "\n";
echo "ULTRATHINK ANALYSE ABGESCHLOSSEN\n";
echo str_repeat("=", 60) . "\n\n";

if (count($issues) == 0 && count($warnings) == 0) {
    echo "🎉 PERFEKT! Keine Probleme gefunden!\n";
    echo "\n✅ Alle Systeme funktionieren optimal\n";
    echo "✅ Jobs werden in Echtzeit verarbeitet\n";
    echo "✅ Webhooks funktionieren einwandfrei\n";
    echo "✅ Keine kritischen Fehler\n";
} else {
    if (count($issues) > 0) {
        echo "❌ KRITISCHE PROBLEME GEFUNDEN:\n";
        foreach ($issues as $issue) {
            echo "   - $issue\n";
        }
        echo "\n";
    }
    
    if (count($warnings) > 0) {
        echo "⚠️ WARNUNGEN:\n";
        foreach ($warnings as $warning) {
            echo "   - $warning\n";
        }
    }
}

echo "\n";

// Cleanup
Redis::del("latency_test_$jobId");
if (isset($testCall)) {
    DB::table('calls')->where('call_id', $webhookData['call_id'])->delete();
}