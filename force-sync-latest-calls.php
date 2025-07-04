#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Services\RetellV2Service;
use App\Services\PhoneNumberResolver;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Traits\BelongsToCompany::setTrustedCompanyContext(1, 'sync-script');

try {
    $company = Company::find(1);
    if (!$company || !$company->retell_api_key) {
        echo "❌ Company 1 braucht Retell API key.\n";
        exit(1);
    }

    echo "🔄 Synchronisiere die letzten 10 Anrufe...\n";
    
    $retellService = new RetellV2Service($company->retell_api_key);
    
    // Hole nur die letzten 10 Anrufe
    $response = $retellService->listCalls(10);
    
    $calls = $response['calls'] ?? [];
    echo "📞 Gefunden: " . count($calls) . " Anrufe\n\n";
    
    $phoneResolver = app(PhoneNumberResolver::class);
    $imported = 0;
    $updated = 0;
    
    foreach ($calls as $callData) {
        $callId = $callData['call_id'];
        
        // Prüfe ob Call existiert
        $existingCall = \App\Models\Call::where('retell_call_id', $callId)->first();
        
        // Resolve context
        $context = $phoneResolver->resolveFromWebhook([
            'to' => $callData['to_number'] ?? null,
            'from' => $callData['from_number'] ?? null,
            'agent_id' => $callData['agent_id'] ?? null,
        ]);
        
        $companyId = $context['company_id'] ?? 1;
        $branchId = $context['branch_id'] ?? null;
        
        if ($existingCall) {
            // Update falls Branch fehlt
            if (!$existingCall->branch_id && $branchId) {
                $existingCall->branch_id = $branchId;
                $existingCall->save();
                echo "✅ Updated Call {$callId} - Branch hinzugefügt\n";
                $updated++;
            }
        } else {
            // Erstelle neuen Call
            $call = \App\Models\Call::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'retell_call_id' => $callData['call_id'],
                'from_number' => $callData['from_number'] ?? 'unknown',
                'to_number' => $callData['to_number'] ?? 'unknown',
                'direction' => $callData['direction'] ?? 'inbound',
                'call_type' => $callData['call_type'] ?? 'phone_call',
                'call_status' => $callData['call_status'] ?? 'completed',
                'agent_id' => $callData['agent_id'] ?? null,
                'start_timestamp' => isset($callData['start_timestamp']) 
                    ? Carbon::createFromTimestampMs($callData['start_timestamp'])->addHours(2) 
                    : null,
                'end_timestamp' => isset($callData['end_timestamp']) 
                    ? Carbon::createFromTimestampMs($callData['end_timestamp'])->addHours(2) 
                    : null,
                'duration_sec' => $callData['call_length'] ?? 0,
                'transcript' => $callData['transcript'] ?? '',
                'transcript_object' => $callData['transcript_object'] ?? [],
                'recording_url' => $callData['recording_url'] ?? null,
                'public_log_url' => $callData['public_log_url'] ?? null,
                'analysis' => $callData['call_analysis'] ?? [],
                'metadata' => $callData['metadata'] ?? [],
                'disconnection_reason' => $callData['disconnection_reason'] ?? null,
            ]);
            
            echo "✅ Neuer Call {$call->retell_call_id}";
            echo " | Von: {$call->from_number}";
            echo " | Branch: " . ($call->branch_id ?: 'KEINE');
            echo " | " . Carbon::parse($call->created_at)->diffForHumans() . "\n";
            
            $imported++;
        }
    }
    
    echo "\n📊 Zusammenfassung:\n";
    echo "- Importiert: $imported\n";
    echo "- Aktualisiert: $updated\n";
    echo "- Übersprungen: " . (count($calls) - $imported - $updated) . "\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
} finally {
    \App\Traits\BelongsToCompany::clearCompanyContext();
}