#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Traits\BelongsToCompany::setTrustedCompanyContext(1, 'cleanup-script');

try {
    echo "🧹 Bereinige alte 'in_progress' Anrufe...\n\n";
    
    // Finde alle Anrufe die länger als 15 Minuten "in_progress" sind
    $staleCalls = \App\Models\Call::where('call_status', 'in_progress')
        ->whereNull('end_timestamp')
        ->where('start_timestamp', '<', now()->subMinutes(15))
        ->get();
    
    echo "Gefundene alte Anrufe: " . $staleCalls->count() . "\n";
    
    foreach ($staleCalls as $call) {
        $duration = $call->start_timestamp 
            ? $call->start_timestamp->diffInMinutes(now()) 
            : 999;
            
        echo "- {$call->retell_call_id} (seit {$duration} Minuten)\n";
        
        // Setze Status auf 'abandoned' (aufgegeben)
        $call->update([
            'call_status' => 'abandoned',
            'end_timestamp' => now(),
            'duration_sec' => $call->start_timestamp 
                ? $call->start_timestamp->diffInSeconds(now()) 
                : 0,
            'notes' => 'Automatisch beendet nach 15 Minuten ohne Update'
        ]);
    }
    
    if ($staleCalls->count() > 0) {
        echo "\n✅ {$staleCalls->count()} alte Anrufe bereinigt.\n";
    } else {
        echo "\n✅ Keine alten Anrufe gefunden.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
} finally {
    \App\Traits\BelongsToCompany::clearCompanyContext();
}