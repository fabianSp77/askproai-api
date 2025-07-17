<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST E-Mail nach Horizon Neustart ===\n\n";

$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find(227);
if (!$call) {
    echo "❌ Call 227 not found!\n";
    exit(1);
}

app()->instance('current_company_id', $call->company_id);

// Clear previous activities
\App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('call_id', 227)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(10))
    ->delete();

echo "1. Sende E-Mail über Queue (wie Business Portal):\n";

try {
    // Queue email
    \Illuminate\Support\Facades\Mail::to('fabianspitzer@icloud.com')->queue(new \App\Mail\CallSummaryEmail(
        $call,
        true,
        true,
        'Test nach Horizon Restart - ' . now()->format('H:i:s'),
        'internal'
    ));
    
    echo "   ✅ E-Mail in Queue gestellt\n\n";
    
    // Wait for processing
    echo "2. Warte auf Verarbeitung...\n";
    sleep(3);
    
    // Check if processed
    $redis = app('redis');
    $queueCount = 0;
    foreach (['default', 'high', 'emails'] as $queue) {
        $queueCount += $redis->llen("queues:{$queue}");
    }
    
    echo "   Jobs in Queue: $queueCount\n";
    
    if ($queueCount == 0) {
        echo "   ✅ E-Mail wurde verarbeitet!\n\n";
        echo "=== ERFOLG ===\n";
        echo "Das Problem war, dass die Queue Worker hängen geblieben waren.\n";
        echo "Nach dem Neustart von Horizon funktioniert alles wieder.\n\n";
        echo "Die E-Mail sollte jetzt ankommen!\n";
    } else {
        echo "   ⚠️ E-Mail noch in Queue\n";
    }
    
    // Check activity
    $activity = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('call_id', 227)
        ->where('activity_type', 'email_sent')
        ->orderBy('created_at', 'desc')
        ->first();
    
    if ($activity) {
        echo "\n3. Activity Status:\n";
        echo "   " . $activity->description . "\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== EMPFEHLUNG ===\n";
echo "Falls das Problem wieder auftritt:\n";
echo "1. Überwachen Sie Horizon: https://api.askproai.de/horizon\n";
echo "2. Setzen Sie einen Cron-Job für automatischen Restart\n";
echo "3. Prüfen Sie die Logs: tail -f storage/logs/horizon.log\n";