#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Live Webhook Monitor gestartet...\n";
echo "Überwache eingehende Webhooks und Calls...\n\n";

$lastWebhookId = 0;
$lastCallId = 0;

while (true) {
    // Prüfe neue Webhook Events
    $webhooks = DB::table('webhook_events')
        ->where('id', '>', $lastWebhookId)
        ->orderBy('id', 'asc')
        ->get();
    
    foreach ($webhooks as $webhook) {
        echo "[" . date('H:i:s') . "] 📨 WEBHOOK: {$webhook->event_type}\n";
        echo "  Provider: {$webhook->provider}\n";
        echo "  Status: {$webhook->status}\n";
        if ($webhook->error_message) {
            echo "  ❌ Error: {$webhook->error_message}\n";
        }
        echo "\n";
        
        $lastWebhookId = $webhook->id;
    }
    
    // Prüfe neue Calls
    $calls = DB::table('calls')
        ->where('id', '>', $lastCallId)
        ->orderBy('id', 'asc')
        ->get();
    
    foreach ($calls as $call) {
        echo "[" . date('H:i:s') . "] 📞 NEUER ANRUF: {$call->retell_call_id}\n";
        echo "  Von: {$call->from_number}\n";
        echo "  Nach: {$call->to_number}\n";
        echo "  Status: {$call->call_status}\n";
        echo "  Branch: " . ($call->branch_id ?: 'KEINE') . "\n";
        echo "\n";
        
        $lastCallId = $call->id;
    }
    
    // Zeige aktuelle Stats
    if (time() % 30 == 0) { // Alle 30 Sekunden
        $todayWebhooks = DB::table('webhook_events')
            ->whereDate('created_at', today())
            ->count();
        
        $todayCalls = DB::table('calls')
            ->whereDate('created_at', today())
            ->count();
            
        echo "📊 Stats: {$todayWebhooks} Webhooks heute, {$todayCalls} Anrufe heute\n\n";
    }
    
    sleep(2); // Prüfe alle 2 Sekunden
}