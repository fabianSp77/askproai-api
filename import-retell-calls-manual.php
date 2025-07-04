<?php

/**
 * Direkter Retell Call Import ohne Job Queue
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Services\RetellV2Service;
use Carbon\Carbon;

echo "\n=== Direkter Retell Call Import ===\n\n";

// Get company
$company = Company::first();
if (!$company) {
    echo "❌ Keine Company gefunden\n";
    exit(1);
}

echo "Company: {$company->name} (ID: {$company->id})\n";

// Get API key
$apiKey = $company->retell_api_key ?: config('services.retell.api_key');
if (!$apiKey) {
    echo "❌ Kein API Key gefunden\n";
    exit(1);
}

// Initialize Retell service
$retellService = new RetellV2Service(is_encrypted($apiKey) ? decrypt($apiKey) : $apiKey);

echo "Hole Calls von Retell API...\n";

try {
    // Get calls from Retell
    $response = $retellService->listCalls(100);
    $retellCalls = $response['calls'] ?? [];
    
    echo "Gefunden: " . count($retellCalls) . " Calls\n\n";
    
    $imported = 0;
    $updated = 0;
    $skipped = 0;
    
    foreach ($retellCalls as $retellCall) {
        $callId = $retellCall['call_id'] ?? null;
        
        if (!$callId) {
            $skipped++;
            continue;
        }
        
        // Check if call exists - ohne TenantScope, prüfe beide Felder
        $existingCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where(function($query) use ($callId) {
                $query->where('call_id', $callId)
                      ->orWhere('retell_call_id', $callId);
            })
            ->where('company_id', $company->id)
            ->first();
        
        $callData = [
            'call_id' => $callId,
            'retell_call_id' => $callId, // Duplicate field for legacy compatibility
            'company_id' => $company->id,
            'from_number' => $retellCall['from_number'] ?? null,
            'to_number' => $retellCall['to_number'] ?? null,
            'direction' => $retellCall['direction'] ?? 'inbound',
            'call_status' => $retellCall['status'] ?? 'completed',
            'start_timestamp' => isset($retellCall['start_timestamp']) 
                ? Carbon::createFromTimestampMs($retellCall['start_timestamp'])->setTimezone('Europe/Berlin')
                : null,
            'end_timestamp' => isset($retellCall['end_timestamp']) 
                ? Carbon::createFromTimestampMs($retellCall['end_timestamp'])->setTimezone('Europe/Berlin')
                : null,
            'duration_seconds' => $retellCall['duration'] ?? null,
            'recording_url' => $retellCall['recording_url'] ?? null,
            'transcript' => $retellCall['transcript'] ?? null,
            'agent_id' => $retellCall['agent_id'] ?? null,
            'call_type' => $retellCall['call_type'] ?? null,
            'disconnection_reason' => $retellCall['disconnection_reason'] ?? null,
            'metadata' => $retellCall['metadata'] ?? null,
        ];
        
        if ($existingCall) {
            // Update existing call
            foreach ($callData as $key => $value) {
                if ($value !== null) {
                    $existingCall->$key = $value;
                }
            }
            $existingCall->save();
            $updated++;
            echo "✓ Updated: {$callId} - " . 
                 ($callData['start_timestamp'] ? $callData['start_timestamp']->format('Y-m-d H:i:s') : 'no timestamp') . 
                 "\n";
        } else {
            // Create new call - ohne TenantScope
            Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->create($callData);
            $imported++;
            echo "✓ Imported: {$callId} - " . 
                 ($callData['start_timestamp'] ? $callData['start_timestamp']->format('Y-m-d H:i:s') : 'no timestamp') . 
                 "\n";
        }
    }
    
    echo "\n=== Import abgeschlossen ===\n";
    echo "✅ Importiert: $imported\n";
    echo "✅ Aktualisiert: $updated\n";
    echo "⏭️ Übersprungen: $skipped\n";
    
    // Zeige Calls von heute
    echo "\n=== Calls von heute ===\n";
    $todayCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('company_id', $company->id)
        ->whereDate('start_timestamp', Carbon::today())
        ->count();
    echo "Calls mit start_timestamp heute: $todayCalls\n";
    
} catch (\Exception $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    echo "Details: " . $e->getTraceAsString() . "\n";
}

function is_encrypted($value) {
    return strpos($value, 'eyJpdiI6') === 0;
}