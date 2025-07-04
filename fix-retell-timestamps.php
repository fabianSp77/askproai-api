#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Traits\BelongsToCompany::setTrustedCompanyContext(1, 'fix-timestamps');

try {
    echo "🕐 Korrigiere Retell Timestamps auf Berliner Zeit...\n\n";
    
    // Zeige aktuelle Situation
    $sample = \App\Models\Call::where('from_number', '+491604366218')
        ->whereDate('created_at', today())
        ->orderBy('created_at', 'desc')
        ->first();
    
    if ($sample) {
        echo "Beispiel vorher:\n";
        echo "- Call ID: {$sample->retell_call_id}\n";
        echo "- Start (DB): {$sample->start_timestamp}\n";
        echo "- Ende (DB): {$sample->end_timestamp}\n\n";
    }
    
    // Korrigiere alle Calls von heute (und die letzten Tage)
    $calls = \App\Models\Call::where('created_at', '>', now()->subDays(7))
        ->whereNotNull('start_timestamp')
        ->get();
    
    $updated = 0;
    foreach ($calls as $call) {
        // Addiere 2 Stunden für CEST
        if ($call->start_timestamp) {
            $call->start_timestamp = \Carbon\Carbon::parse($call->start_timestamp)->addHours(2);
        }
        if ($call->end_timestamp) {
            $call->end_timestamp = \Carbon\Carbon::parse($call->end_timestamp)->addHours(2);
        }
        $call->save();
        $updated++;
    }
    
    echo "✅ {$updated} Anrufe korrigiert!\n\n";
    
    // Zeige Ergebnis
    if ($sample) {
        $sample->refresh();
        echo "Beispiel nachher:\n";
        echo "- Call ID: {$sample->retell_call_id}\n";
        echo "- Start (DB): {$sample->start_timestamp}\n";
        echo "- Ende (DB): {$sample->end_timestamp}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
} finally {
    \App\Traits\BelongsToCompany::clearCompanyContext();
}