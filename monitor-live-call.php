<?php

/**
 * Monitor Live Call
 * 
 * Überwacht einen eingehenden Anruf in Echtzeit
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;

echo "\n=== Live Call Monitor ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n";
echo "Watching for incoming calls...\n\n";

$lastCallCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->count();
$lastWebhookCount = DB::table('webhook_events')->where('provider', 'retell')->count();
$startTime = now();

while (true) {
    // Check for new webhooks
    $newWebhooks = DB::table('webhook_events')
        ->where('provider', 'retell')
        ->where('created_at', '>', $startTime)
        ->orderBy('created_at', 'desc')
        ->get();
    
    if ($newWebhooks->count() > 0) {
        foreach ($newWebhooks as $webhook) {
            echo "[" . date('H:i:s') . "] 📨 Webhook received: " . $webhook->event_type . "\n";
            $payload = json_decode($webhook->payload, true);
            if (isset($payload['call']['call_id'])) {
                echo "   Call ID: " . $payload['call']['call_id'] . "\n";
            }
            if (isset($payload['call']['from_number'])) {
                echo "   From: " . $payload['call']['from_number'] . "\n";
            }
        }
        $startTime = now();
    }
    
    // Check for new calls
    $currentCallCount = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->count();
    if ($currentCallCount > $lastCallCount) {
        $newCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->orderBy('created_at', 'desc')
            ->first();
            
        echo "[" . date('H:i:s') . "] 📞 NEW CALL DETECTED!\n";
        echo "   ID: " . $newCall->id . "\n";
        echo "   Call ID: " . ($newCall->retell_call_id ?? 'unknown') . "\n";
        echo "   From: " . $newCall->from_number . "\n";
        echo "   Status: " . $newCall->call_status . "\n";
        echo "   Started: " . $newCall->start_timestamp . "\n";
        
        $lastCallCount = $currentCallCount;
    }
    
    // Check for active calls
    $activeCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->whereNull('end_timestamp')
        ->where('created_at', '>', now()->subHours(2))
        ->get();
    
    if ($activeCalls->count() > 0) {
        echo "\r[" . date('H:i:s') . "] 🔴 Active calls: " . $activeCalls->count() . " ";
        foreach ($activeCalls as $call) {
            $duration = $call->start_timestamp ? now()->diffInSeconds($call->start_timestamp) : 0;
            echo "(" . gmdate('i:s', $duration) . ") ";
        }
    } else {
        echo "\r[" . date('H:i:s') . "] ⏳ Waiting for calls... ";
    }
    
    // Check every second
    sleep(1);
    
    // Clear line for next update
    echo str_repeat(' ', 50);
}