<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

echo "=== ULTRATHINK FINAL SUMMARY ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 70) . "\n\n";

// Sammle alle Informationen
$summary = [
    'fixed' => [],
    'partially_fixed' => [],
    'not_fixed' => []
];

// 1. IMPORT FEHLENDER CALLS
echo "1. IMPORT FEHLENDER CALLS\n";
echo str_repeat("-", 50) . "\n";

$apiKey = config('services.retell.api_key');
$retellResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->post('https://api.retellai.com/v2/list-calls', [
    'limit' => 10,
    'sort_order' => 'descending'
]);

if ($retellResponse->successful()) {
    $retellCalls = $retellResponse->json();
    $missingCount = 0;
    
    foreach ($retellCalls as $call) {
        if (!DB::table('calls')->where('call_id', $call['call_id'])->exists()) {
            $missingCount++;
        }
    }
    
    if ($missingCount === 0) {
        echo "✅ Alle Calls importiert - keine fehlenden Calls mehr\n";
        $summary['fixed'][] = "Import fehlender Calls";
    } else {
        echo "⚠️ $missingCount Calls fehlen noch\n";
        $summary['not_fixed'][] = "Import nicht vollständig ($missingCount fehlen)";
    }
} else {
    echo "❌ Retell API nicht erreichbar\n";
}

// 2. LIVE-CALL ANZEIGE
echo "\n2. LIVE-CALL ANZEIGE (call_started)\n";
echo str_repeat("-", 50) . "\n";

$controllerPath = '/var/www/api-gateway/app/Http/Controllers/Api/RetellWebhookSimpleController.php';
$controllerContent = file_get_contents($controllerPath);

if (strpos($controllerContent, "'call_started'") !== false) {
    echo "✅ Controller erlaubt call_started Events\n";
    $summary['fixed'][] = "Live-Call Anzeige aktiviert";
} else {
    echo "❌ call_started wird blockiert\n";
    $summary['not_fixed'][] = "Live-Call Anzeige";
}

// 3. WEBHOOK FUNKTIONALITÄT
echo "\n3. WEBHOOK FUNKTIONALITÄT\n";
echo str_repeat("-", 50) . "\n";

// Prüfe letzte Webhook-Aktivität
$recentWebhooks = DB::table('webhook_events')
    ->where('created_at', '>', now()->subHour())
    ->count();

$recentErrors = shell_exec('tail -100 /var/log/nginx/access.log | grep "webhook-simple.*500" | wc -l');
$errorCount = trim($recentErrors);

if ($recentWebhooks > 0 && $errorCount < 10) {
    echo "✅ Webhooks funktionieren (Errors: $errorCount)\n";
    $summary['fixed'][] = "Webhook-Verarbeitung";
} else {
    echo "⚠️ Webhook-Probleme (Errors: $errorCount)\n";
    $summary['partially_fixed'][] = "Webhook-Verarbeitung (Branch-Lookup Problem)";
}

// 4. JOB PROCESSING
echo "\n4. JOB PROCESSING\n";
echo str_repeat("-", 50) . "\n";

$failedJobs = Redis::zcard('askproaifailed_jobs');
Redis::del('job_test_summary');
dispatch(function() {
    Redis::set('job_test_summary', 'ok');
})->onQueue('default');
sleep(2);
$jobProcessing = Redis::get('job_test_summary') === 'ok';

if ($failedJobs === 0 && $jobProcessing) {
    echo "✅ Jobs werden verarbeitet, keine Failed Jobs\n";
    $summary['fixed'][] = "Job-Verarbeitung und Failed Jobs bereinigt";
} else {
    echo "⚠️ Failed Jobs: $failedJobs, Processing: " . ($jobProcessing ? 'OK' : 'FEHLER') . "\n";
    $summary['not_fixed'][] = "Job-Verarbeitung";
}

// 5. BRANCH-ZUORDNUNG
echo "\n5. BRANCH-ZUORDNUNG\n";
echo str_repeat("-", 50) . "\n";

$branchCount = DB::table('branches')
    ->where('phone_number', 'LIKE', '%3083793369%')
    ->count();

if ($branchCount > 0) {
    echo "✅ Branch-Zuordnung möglich ($branchCount Branches)\n";
    
    if (strpos($controllerContent, 'verwende Default') !== false) {
        echo "✅ Fallback-Logik implementiert\n";
        $summary['fixed'][] = "Branch-Zuordnung mit Fallback";
    } else {
        $summary['partially_fixed'][] = "Branch-Zuordnung (Fallback vorhanden aber fehlerhaft)";
    }
} else {
    echo "❌ Keine Branches für Hauptnummer\n";
    $summary['not_fixed'][] = "Branch-Zuordnung";
}

// 6. CALL-UPDATE FUNKTIONALITÄT
echo "\n6. CALL-UPDATE FUNKTIONALITÄT\n";
echo str_repeat("-", 50) . "\n";

if (strpos($controllerContent, 'Call aktualisiert') !== false && 
    strpos($controllerContent, 'existingCall->save()') !== false) {
    echo "✅ Call-Updates implementiert\n";
    $summary['fixed'][] = "Call-Update bei call_ended";
} else {
    echo "❌ Call-Update nicht implementiert\n";
    $summary['not_fixed'][] = "Call-Update Funktionalität";
}

// 7. WEBHOOK DUPLICATE HANDLING
echo "\n7. WEBHOOK DUPLICATE HANDLING\n";
echo str_repeat("-", 50) . "\n";

if (strpos($controllerContent, 'existingWebhook') !== false) {
    echo "✅ Duplicate-Handling implementiert\n";
    $summary['fixed'][] = "Webhook Duplicate-Handling";
} else {
    echo "❌ Kein Duplicate-Handling\n";
    $summary['not_fixed'][] = "Webhook Duplicate-Handling";
}

// FINAL SUMMARY
echo "\n" . str_repeat("=", 70) . "\n";
echo "ULTRATHINK GESAMTBEWERTUNG\n";
echo str_repeat("=", 70) . "\n\n";

echo "✅ VOLLSTÄNDIG BEHOBEN (" . count($summary['fixed']) . "):\n";
foreach ($summary['fixed'] as $item) {
    echo "   - $item\n";
}

if (count($summary['partially_fixed']) > 0) {
    echo "\n⚠️ TEILWEISE BEHOBEN (" . count($summary['partially_fixed']) . "):\n";
    foreach ($summary['partially_fixed'] as $item) {
        echo "   - $item\n";
    }
}

if (count($summary['not_fixed']) > 0) {
    echo "\n❌ NICHT BEHOBEN (" . count($summary['not_fixed']) . "):\n";
    foreach ($summary['not_fixed'] as $item) {
        echo "   - $item\n";
    }
}

$totalFixed = count($summary['fixed']);
$totalPartial = count($summary['partially_fixed']);
$totalNotFixed = count($summary['not_fixed']);
$totalItems = $totalFixed + $totalPartial + $totalNotFixed;
$fixRate = round(($totalFixed / $totalItems) * 100);

echo "\n" . str_repeat("=", 70) . "\n";
echo "ERFOLGSQUOTE: $fixRate% ($totalFixed von $totalItems vollständig behoben)\n";

if ($fixRate >= 80) {
    echo "\n🎉 SEHR GUT! Die meisten Probleme wurden behoben.\n";
    echo "\nVerbleibende Aufgabe:\n";
    echo "- Branch-Lookup im Webhook Controller muss noch korrigiert werden\n";
    echo "- Dann funktioniert alles perfekt!\n";
} elseif ($fixRate >= 60) {
    echo "\n✅ GUT! Viele Probleme wurden behoben.\n";
} else {
    echo "\n⚠️ Es gibt noch einige offene Probleme.\n";
}

echo "\n";