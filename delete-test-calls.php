#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Traits\BelongsToCompany::setTrustedCompanyContext(1, 'cleanup-script');

try {
    // Zeige was gelöscht wird
    $testCalls = \App\Models\Call::where(function($query) {
            $query->where('retell_call_id', 'LIKE', 'test%')
                  ->orWhere('retell_call_id', 'LIKE', 'live_test%');
        })
        ->where('from_number', '!=', '+491604366218')
        ->get(['id', 'retell_call_id', 'from_number', 'to_number', 'created_at']);
    
    echo "🗑️  Folgende Test-Anrufe werden gelöscht:\n\n";
    
    foreach ($testCalls as $call) {
        echo "- {$call->retell_call_id} | Von: {$call->from_number} | {$call->created_at}\n";
    }
    
    echo "\nTotal: " . $testCalls->count() . " Anrufe\n";
    
    if ($testCalls->count() > 0) {
        // Lösche die Anrufe
        $deleted = \App\Models\Call::where(function($query) {
                $query->where('retell_call_id', 'LIKE', 'test%')
                      ->orWhere('retell_call_id', 'LIKE', 'live_test%');
            })
            ->where('from_number', '!=', '+491604366218')
            ->delete();
        
        echo "\n✅ {$deleted} Test-Anrufe gelöscht!\n";
    } else {
        echo "\n✅ Keine Test-Anrufe zum Löschen gefunden.\n";
    }
    
    // Zeige verbleibende Anrufe von dir
    echo "\n📞 Deine verbleibenden Anrufe:\n";
    $yourCalls = \App\Models\Call::where('from_number', '+491604366218')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get(['retell_call_id', 'call_status', 'branch_id', 'created_at']);
    
    foreach ($yourCalls as $call) {
        echo "- {$call->retell_call_id} | Status: {$call->call_status} | Branch: " . ($call->branch_id ?: 'KEINE') . " | {$call->created_at}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
} finally {
    \App\Traits\BelongsToCompany::clearCompanyContext();
}