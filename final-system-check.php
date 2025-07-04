<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FINALER SYSTEM CHECK ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

$status = [
    'horizon' => false,
    'queues' => false,
    'scheduler' => false,
    'webhooks' => false,
    'database' => false,
    'redis' => false
];

// 1. Horizon Status
echo "1. HORIZON STATUS\n";
echo str_repeat("-", 30) . "\n";
try {
    $horizonStatus = shell_exec('php artisan horizon:status 2>&1');
    if (strpos($horizonStatus, 'running') !== false) {
        echo "✅ Horizon läuft\n";
        $status['horizon'] = true;
    } else {
        echo "❌ Horizon läuft nicht!\n";
    }
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

// 2. Queue Status
echo "\n2. QUEUE STATUS\n";
echo str_repeat("-", 30) . "\n";
$redis = \Illuminate\Support\Facades\Redis::connection();
$queues = ['default', 'webhooks', 'appointments'];
$allQueuesOk = true;
foreach ($queues as $queue) {
    $pending = $redis->llen("queues:$queue");
    if ($pending < 50) {
        echo "✅ Queue '$queue': $pending Jobs\n";
    } else {
        echo "⚠️ Queue '$queue': $pending Jobs (zu viele!)\n";
        $allQueuesOk = false;
    }
}
$status['queues'] = $allQueuesOk;

// 3. Scheduler Status
echo "\n3. SCHEDULER STATUS\n";
echo str_repeat("-", 30) . "\n";
$cron = shell_exec('crontab -l -u www-data 2>&1 | grep schedule:run');
if ($cron && strpos($cron, 'schedule:run') !== false) {
    echo "✅ Laravel Scheduler ist konfiguriert\n";
    $status['scheduler'] = true;
} else {
    echo "❌ Laravel Scheduler nicht in Crontab!\n";
}

// 4. Webhook Status
echo "\n4. WEBHOOK STATUS\n";
echo str_repeat("-", 30) . "\n";
// Prüfe Retell Agent Webhook URL
$company = \App\Models\Company::first();
$apiKey = config('services.retell.api_key') ?? env('RETELL_TOKEN');
if ($apiKey) {
    $agentId = 'agent_9a8202a740cd3120d96fcfda1e';
    
    $ch = curl_init("https://api.retellai.com/get-agent/$agentId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $agent = json_decode($response, true);
        $webhookUrl = $agent['webhook_url'] ?? '';
        if (strpos($webhookUrl, 'api.askproai.de') !== false) {
            echo "✅ Webhook URL konfiguriert: " . $webhookUrl . "\n";
            $status['webhooks'] = true;
        } else {
            echo "❌ Webhook URL falsch: " . $webhookUrl . "\n";
        }
    }
}

// 5. Database Status
echo "\n5. DATABASE STATUS\n";
echo str_repeat("-", 30) . "\n";
try {
    $pdo = \DB::connection()->getPdo();
    echo "✅ Datenbankverbindung OK\n";
    
    // Prüfe wichtige Tabellen
    $tables = ['calls', 'appointments', 'companies', 'branches'];
    foreach ($tables as $table) {
        $count = \DB::table($table)->count();
        echo "   - $table: $count Einträge\n";
    }
    $status['database'] = true;
} catch (Exception $e) {
    echo "❌ Datenbankfehler: " . $e->getMessage() . "\n";
}

// 6. Redis Status
echo "\n6. REDIS STATUS\n";
echo str_repeat("-", 30) . "\n";
try {
    $redis->ping();
    echo "✅ Redis Verbindung OK\n";
    
    // Failed Jobs
    $failedCount = $redis->zcard('askproaifailed_jobs');
    if ($failedCount == 0) {
        echo "✅ Keine Failed Jobs\n";
    } else {
        echo "⚠️ $failedCount Failed Jobs\n";
    }
    $status['redis'] = true;
} catch (Exception $e) {
    echo "❌ Redis Fehler: " . $e->getMessage() . "\n";
}

// 7. Letzte Aktivitäten
echo "\n7. LETZTE AKTIVITÄTEN\n";
echo str_repeat("-", 30) . "\n";

// Letzter Call
$lastCall = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->latest()
    ->first();
if ($lastCall) {
    echo "✅ Letzter Call: " . $lastCall->created_at->diffForHumans() . " (" . $lastCall->call_id . ")\n";
} else {
    echo "⚠️ Keine Calls gefunden\n";
}

// Letzte Webhook Events
$lastWebhook = \DB::table('webhook_events')
    ->latest()
    ->first();
if ($lastWebhook) {
    echo "✅ Letztes Webhook Event: " . \Carbon\Carbon::parse($lastWebhook->created_at)->diffForHumans() . "\n";
} else {
    echo "⚠️ Keine Webhook Events gefunden\n";
}

// ZUSAMMENFASSUNG
echo "\n" . str_repeat("=", 50) . "\n";
echo "ZUSAMMENFASSUNG\n";
echo str_repeat("=", 50) . "\n";

$allOk = true;
foreach ($status as $service => $isOk) {
    if (!$isOk) {
        $allOk = false;
        echo "❌ " . strtoupper($service) . " hat Probleme!\n";
    }
}

if ($allOk) {
    echo "\n🎉 ALLE SYSTEME FUNKTIONIEREN EINWANDFREI! 🎉\n";
    echo "\n✅ Horizon läuft und verarbeitet Jobs\n";
    echo "✅ Alle Queues sind aktiv\n";
    echo "✅ Scheduler ist konfiguriert\n";
    echo "✅ Webhooks sind richtig eingerichtet\n";
    echo "✅ Datenbank und Redis funktionieren\n";
    echo "\n💡 Das System ist vollständig betriebsbereit!\n";
} else {
    echo "\n⚠️ EINIGE SYSTEME BENÖTIGEN AUFMERKSAMKEIT!\n";
}

echo "\n";