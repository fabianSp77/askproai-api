<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;

echo "🔧 RETELL WEBHOOK SECRET KONFIGURATION\n";
echo str_repeat("=", 60) . "\n\n";

$company = Company::first();
if (!$company || !$company->retell_api_key) {
    die("Error: No company with Retell API key found\n");
}

// Check current webhook secret
if (!$company->retell_webhook_secret) {
    echo "❌ Webhook Secret fehlt in der Datenbank\n";
    
    // Get from environment
    $webhookSecret = env('RETELL_WEBHOOK_SECRET');
    
    if ($webhookSecret) {
        echo "✅ Webhook Secret in .env gefunden\n";
        
        // Update company
        $company->retell_webhook_secret = $webhookSecret;
        $company->save();
        
        echo "✅ Webhook Secret in Datenbank gespeichert\n";
    } else {
        echo "⚠️  Kein Webhook Secret in .env gefunden\n";
        echo "\nBitte füge dies zu deiner .env Datei hinzu:\n";
        echo "RETELL_WEBHOOK_SECRET=dein_webhook_secret_hier\n";
        echo "\nDu findest das Secret in deinem Retell.ai Dashboard unter API Keys.\n";
    }
} else {
    echo "✅ Webhook Secret ist bereits gesetzt\n";
}

// Now import recent calls
echo "\n📞 IMPORTIERE AKTUELLE ANRUFE...\n";

$retellService = new RetellV2Service($company->retell_api_key);

try {
    // Get calls from last 2 hours
    $response = $retellService->getCalls([
        'limit' => 20,
        'sort_order' => 'desc'
    ]);
    
    if ($response && isset($response['calls'])) {
        $calls = $response['calls'];
        echo "Gefunden: " . count($calls) . " Anrufe\n\n";
        
        $imported = 0;
        foreach ($calls as $callData) {
            // Check if call was recent (last 2 hours)
            if (isset($callData['start_timestamp'])) {
                $startTime = \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']);
                
                if ($startTime->isAfter(\Carbon\Carbon::now()->subHours(2))) {
                    echo "[" . $startTime->format('H:i:s') . "] Call: " . substr($callData['call_id'], 0, 20) . "...\n";
                    
                    // Check if call exists
                    $existingCall = \App\Models\Call::withoutGlobalScopes()
                        ->where('call_id', $callData['call_id'])
                        ->first();
                    
                    if (!$existingCall) {
                        // Create call
                        $call = new \App\Models\Call();
                        $call->call_id = $callData['call_id'];
                        $call->company_id = $company->id;
                        $call->from_number = $callData['from_number'] ?? null;
                        $call->to_number = $callData['to_number'] ?? null;
                        $call->status = $callData['call_status'] ?? 'completed';
                        $call->duration = $callData['duration'] ?? 0;
                        $call->started_at = $startTime;
                        $call->metadata = $callData;
                        
                        // Get transcript
                        if (isset($callData['transcript'])) {
                            $call->transcript = json_encode($callData['transcript']);
                        }
                        
                        $call->save();
                        $imported++;
                        echo "✅ Importiert\n";
                    } else {
                        echo "⏭️  Bereits vorhanden\n";
                    }
                }
            }
        }
        
        echo "\n✅ {$imported} neue Anrufe importiert\n";
    } else {
        echo "❌ Keine Anrufe gefunden\n";
    }
} catch (\Exception $e) {
    echo "❌ Fehler beim Abrufen der Anrufe: " . $e->getMessage() . "\n";
}

echo "\n";