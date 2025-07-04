<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

echo "=== FINALER UMFASSENDER SYSTEMTEST ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n\n";

$allTests = [];

// 1. QUEUE SYSTEM TEST
echo "1. QUEUE SYSTEM TEST\n";
echo str_repeat("-", 40) . "\n";

// Clear old heartbeat
Redis::del('askproai:heartbeat:last');

// Dispatch job
echo "Dispatching HeartbeatJob...\n";
dispatch(new \App\Jobs\HeartbeatJob())->onQueue('default');

// Wait for processing
sleep(3);

$lastHeartbeat = Redis::get('askproai:heartbeat:last');
if ($lastHeartbeat && (time() - $lastHeartbeat) < 5) {
    echo "✅ Queue System funktioniert! (Heartbeat vor " . (time() - $lastHeartbeat) . " Sekunden)\n";
    $allTests['queue'] = true;
} else {
    echo "❌ Queue System funktioniert NICHT!\n";
    $allTests['queue'] = false;
}

// 2. WEBHOOK TEST
echo "\n2. WEBHOOK TEST\n";
echo str_repeat("-", 40) . "\n";

$testCallId = 'final_test_' . time();
$webhookData = [
    'event' => 'call_ended',
    'call_id' => $testCallId,
    'to_number' => '+493083793369',
    'from_number' => '+491234567890',
    'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
    'call_status' => 'ended',
    'start_timestamp' => (time() - 60) * 1000,
    'end_timestamp' => time() * 1000,
    'duration' => 60,
    'transcript' => 'Final test call'
];

$response = \Illuminate\Support\Facades\Http::post('https://api.askproai.de/api/retell/webhook-simple', $webhookData);

if ($response->successful()) {
    echo "✅ Webhook erfolgreich verarbeitet\n";
    
    // Check if saved
    sleep(1);
    $call = DB::table('calls')->where('call_id', $testCallId)->first();
    if ($call) {
        echo "✅ Call in Datenbank gespeichert\n";
        $allTests['webhook'] = true;
        
        // Cleanup
        DB::table('calls')->where('call_id', $testCallId)->delete();
    } else {
        echo "❌ Call nicht in Datenbank\n";
        $allTests['webhook'] = false;
    }
} else {
    echo "❌ Webhook fehlgeschlagen\n";
    $allTests['webhook'] = false;
}

// 3. HORIZON STATUS
echo "\n3. HORIZON STATUS\n";
echo str_repeat("-", 40) . "\n";

$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
if (strpos($horizonStatus, 'running') !== false) {
    echo "✅ Horizon läuft\n";
    
    // Count workers
    $workers = shell_exec('ps aux | grep "horizon:work" | grep -v grep | wc -l');
    echo "✅ " . trim($workers) . " Worker aktiv\n";
    $allTests['horizon'] = true;
} else {
    echo "❌ Horizon läuft nicht\n";
    $allTests['horizon'] = false;
}

// 4. REDIS CONNECTION
echo "\n4. REDIS CONNECTION\n";
echo str_repeat("-", 40) . "\n";

try {
    Redis::ping();
    echo "✅ Redis Verbindung OK\n";
    
    // Check queues
    $queues = ['default', 'webhooks', 'appointments'];
    $totalPending = 0;
    foreach ($queues as $queue) {
        $pending = Redis::llen("queues:$queue");
        $totalPending += $pending;
        if ($pending > 0) {
            echo "   Queue '$queue': $pending Jobs\n";
        }
    }
    if ($totalPending == 0) {
        echo "✅ Alle Queues leer (gut!)\n";
    }
    $allTests['redis'] = true;
} catch (Exception $e) {
    echo "❌ Redis Fehler: " . $e->getMessage() . "\n";
    $allTests['redis'] = false;
}

// 5. DATABASE CONNECTION
echo "\n5. DATABASE CONNECTION\n";
echo str_repeat("-", 40) . "\n";

try {
    $pdo = DB::connection()->getPdo();
    echo "✅ Datenbankverbindung OK\n";
    
    // Recent calls
    $recentCalls = DB::table('calls')
        ->where('created_at', '>', now()->subHours(24))
        ->count();
    echo "✅ $recentCalls Anrufe in den letzten 24 Stunden\n";
    $allTests['database'] = true;
} catch (Exception $e) {
    echo "❌ Datenbankfehler: " . $e->getMessage() . "\n";
    $allTests['database'] = false;
}

// 6. SCHEDULED TASKS
echo "\n6. SCHEDULED TASKS\n";
echo str_repeat("-", 40) . "\n";

$cron = shell_exec('crontab -l -u www-data 2>&1 | grep schedule:run');
if ($cron && strpos($cron, 'schedule:run') !== false) {
    echo "✅ Laravel Scheduler konfiguriert\n";
    $allTests['scheduler'] = true;
} else {
    echo "❌ Laravel Scheduler nicht in Crontab\n";
    $allTests['scheduler'] = false;
}

// ZUSAMMENFASSUNG
echo "\n" . str_repeat("=", 60) . "\n";
echo "TESTERGEBNISSE\n";
echo str_repeat("=", 60) . "\n\n";

$passedTests = array_filter($allTests);
$totalTests = count($allTests);
$passedCount = count($passedTests);

foreach ($allTests as $test => $passed) {
    echo ($passed ? "✅" : "❌") . " " . strtoupper($test) . "\n";
}

echo "\n";
if ($passedCount == $totalTests) {
    echo "🎉 PERFEKT! Alle $totalTests Tests bestanden!\n";
    echo "\n💡 Das System ist vollständig funktionsfähig:\n";
    echo "   - Jobs werden verarbeitet\n";
    echo "   - Webhooks funktionieren\n";
    echo "   - Horizon läuft stabil\n";
    echo "   - Alle Verbindungen OK\n";
} else {
    echo "⚠️ $passedCount von $totalTests Tests bestanden\n";
    echo "\nFehlgeschlagene Tests:\n";
    foreach ($allTests as $test => $passed) {
        if (!$passed) {
            echo "   - " . strtoupper($test) . "\n";
        }
    }
}

echo "\n";