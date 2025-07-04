<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== RETELL.AI FINAL STATUS CHECK ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// Get API key
$apiKey = config('services.retell.api_key') ?? env('RETELL_TOKEN');
if (!$apiKey) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    if (preg_match('/RETELL_TOKEN=(.+)/', $envContent, $matches)) {
        $apiKey = trim($matches[1]);
    }
}

$baseUrl = 'https://api.retellai.com';

// 1. Check Phone Numbers
echo "📞 TELEFONNUMMERN STATUS:\n";
echo str_repeat("-", 30) . "\n";

$ch = curl_init($baseUrl . '/list-phone-numbers');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $phoneNumbers = json_decode($response, true);
    $configured = 0;
    $notConfigured = 0;
    
    foreach ($phoneNumbers as $phone) {
        $hasAgent = !empty($phone['inbound_agent_id']);
        $hasWebhook = !empty($phone['inbound_webhook_url']);
        
        if ($hasAgent && $hasWebhook) {
            $configured++;
            echo "✅ " . $phone['phone_number'] . "\n";
            echo "   Agent: " . substr($phone['inbound_agent_id'], 0, 20) . "...\n";
            echo "   Webhook: " . $phone['inbound_webhook_url'] . "\n";
        } else {
            $notConfigured++;
            echo "❌ " . $phone['phone_number'] . " - NICHT KONFIGURIERT\n";
        }
    }
    
    echo "\nZusammenfassung:\n";
    echo "✅ Konfiguriert: $configured\n";
    echo "❌ Nicht konfiguriert: $notConfigured\n";
    echo "📊 Gesamt: " . count($phoneNumbers) . "\n";
}

// 2. Check Recent Calls
echo "\n\n📞 AKTUELLE ANRUFE (letzte 10):\n";
echo str_repeat("-", 30) . "\n";

$ch = curl_init($baseUrl . '/v2/list-calls');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'limit' => 10,
    'sort_order' => 'descending'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $calls = json_decode($response, true);
    
    if (empty($calls)) {
        echo "Keine Anrufe gefunden.\n";
    } else {
        foreach ($calls as $call) {
            $timestamp = ($call['start_timestamp'] ?? 0) / 1000;
            $date = date('Y-m-d H:i:s', $timestamp);
            $duration = $call['duration'] ?? 0;
            
            echo "\n📅 " . $date . " (vor " . human_time_diff($timestamp) . ")\n";
            echo "   Von: " . ($call['from_number'] ?? 'Unknown') . "\n";
            echo "   An: " . ($call['to_number'] ?? 'Unknown') . "\n";
            echo "   Dauer: " . gmdate("i:s", $duration) . "\n";
            echo "   Status: " . ($call['status'] ?? 'Unknown') . "\n";
        }
    }
}

// 3. Database Check
echo "\n\n💾 DATENBANK STATUS:\n";
echo str_repeat("-", 30) . "\n";

use App\Models\Call;

$totalCalls = Call::count();
$recentCalls = Call::where('created_at', '>=', now()->subDay())->count();
$todayCalls = Call::whereDate('created_at', today())->count();

echo "Gesamt Anrufe in DB: $totalCalls\n";
echo "Anrufe letzte 24h: $recentCalls\n";
echo "Anrufe heute: $todayCalls\n";

// 4. Check Webhook Logs
echo "\n\n🔔 WEBHOOK AKTIVITÄT:\n";
echo str_repeat("-", 30) . "\n";

$logFile = storage_path('logs/laravel.log');
$recentWebhooks = shell_exec("grep -c 'retell/webhook' $logFile 2>/dev/null") ?: 0;
echo "Webhook Hits im Log: " . trim($recentWebhooks) . "\n";

// 5. Final Status
echo "\n\n✅ NÄCHSTE SCHRITTE:\n";
echo str_repeat("=", 50) . "\n";
echo "1. Führen Sie einen Test-Anruf durch auf eine der Nummern\n";
echo "2. Monitor: tail -f storage/logs/laravel.log | grep -i retell\n";
echo "3. Prüfen Sie Horizon: php artisan horizon:status\n";
echo "4. Bei Problemen: php import-retell-calls.php (manueller Import)\n";

function human_time_diff($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) return "$diff Sekunden";
    if ($diff < 3600) return round($diff / 60) . " Minuten";
    if ($diff < 86400) return round($diff / 3600) . " Stunden";
    return round($diff / 86400) . " Tage";
}