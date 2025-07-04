<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Live Call Monitor ===\n";
echo "Überwacht eingehende Anrufe in Echtzeit\n";
echo "Drücken Sie Ctrl+C zum Beenden\n\n";

$lastCheck = time();
$knownCalls = [];

// Initial load
$calls = \DB::table('calls')
    ->where('created_at', '>=', now()->subMinutes(10))
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($calls as $call) {
    $knownCalls[$call->call_id] = true;
}

echo "Überwache neue Anrufe...\n";
echo str_repeat("-", 80) . "\n";

while (true) {
    // Check for new calls
    $newCalls = \DB::table('calls')
        ->where('created_at', '>=', date('Y-m-d H:i:s', $lastCheck))
        ->orderBy('created_at', 'desc')
        ->get();
    
    foreach ($newCalls as $call) {
        if (!isset($knownCalls[$call->call_id])) {
            $knownCalls[$call->call_id] = true;
            
            echo "\n🔔 NEUER ANRUF ERKANNT!\n";
            echo "Zeit: " . date('Y-m-d H:i:s') . "\n";
            echo "Call ID: " . $call->call_id . "\n";
            echo "Von: " . $call->from_number . "\n";
            echo "An: " . $call->to_number . "\n";
            echo "Status: " . $call->call_status . "\n";
            
            if ($call->call_status === 'in_progress') {
                echo "🟢 ANRUF LÄUFT GERADE!\n";
            } else {
                echo "Dauer: " . $call->duration_sec . " Sekunden\n";
            }
            
            echo str_repeat("-", 80) . "\n";
        }
    }
    
    // Check for webhook logs
    $webhookLogs = shell_exec("tail -n 5 " . storage_path('logs/laravel.log') . " | grep -i 'webhook.*bypass' 2>/dev/null");
    if ($webhookLogs && strpos($webhookLogs, 'empfangen') !== false) {
        echo "\n📨 Webhook empfangen: " . trim($webhookLogs) . "\n";
    }
    
    $lastCheck = time();
    sleep(2); // Check every 2 seconds
}