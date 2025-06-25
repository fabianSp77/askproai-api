<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Call;
use App\Models\WebhookEvent;
use App\Services\RetellV2Service;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECKING LATEST TEST CALLS ===\n\n";

try {
    // 1. Check local database for recent calls
    echo "1. RECENT CALLS IN LOCAL DATABASE:\n";
    $recentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('to_number', '+493083793369')
        ->where('created_at', '>=', Carbon::now()->subHours(24))
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "Found " . $recentCalls->count() . " calls in last 24 hours\n\n";
    
    foreach ($recentCalls as $call) {
        echo "Call " . $call->id . ":\n";
        echo "  - Time: " . $call->created_at->format('Y-m-d H:i:s') . "\n";
        echo "  - From: " . $call->from_number . "\n";
        echo "  - Retell ID: " . $call->retell_call_id . "\n";
        echo "  - Status: " . $call->status . "\n";
        echo "  - Duration: " . $call->duration . "s\n";
        echo "  - Has Appointment: " . ($call->appointment_id ? "YES (ID: {$call->appointment_id})" : "NO") . "\n";
        
        if ($call->metadata) {
            $metadata = is_string($call->metadata) ? json_decode($call->metadata, true) : $call->metadata;
            if (isset($metadata['appointment_booked'])) {
                echo "  - Appointment Booked: " . ($metadata['appointment_booked'] ? 'YES' : 'NO') . "\n";
            }
        }
        echo "\n";
    }
    
    // 2. Check webhook events
    echo "2. RECENT WEBHOOK EVENTS:\n";
    $recentWebhooks = WebhookEvent::where('provider', 'retell')
        ->where('created_at', '>=', Carbon::now()->subHours(24))
        ->whereJsonContains('payload->call->to_number', '+493083793369')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "Found " . $recentWebhooks->count() . " webhook events\n\n";
    
    foreach ($recentWebhooks as $webhook) {
        $payload = is_string($webhook->payload) ? json_decode($webhook->payload, true) : $webhook->payload;
        echo "Webhook " . $webhook->id . ":\n";
        echo "  - Time: " . $webhook->created_at->format('Y-m-d H:i:s') . "\n";
        echo "  - Event: " . $webhook->event_type . "\n";
        echo "  - Status: " . $webhook->status . "\n";
        echo "  - Call ID: " . ($payload['call']['call_id'] ?? 'N/A') . "\n";
        echo "\n";
    }
    
    // 3. Check Retell API
    echo "3. CHECKING RETELL API:\n";
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    $retellService = new RetellV2Service($apiKey);
    
    $response = $retellService->listCalls(100);
    $calls = $response['calls'] ?? [];
    
    echo "Total calls in Retell API: " . count($calls) . "\n\n";
    
    // Filter calls to our test number
    $testCalls = array_filter($calls, function($call) {
        return ($call['to_number'] ?? '') === '+493083793369';
    });
    
    echo "Calls to +493083793369: " . count($testCalls) . "\n\n";
    
    if (count($testCalls) > 0) {
        $latestCalls = array_slice($testCalls, 0, 3);
        foreach ($latestCalls as $call) {
            $startTime = isset($call['start_timestamp']) ? 
                Carbon::createFromTimestamp($call['start_timestamp'] / 1000) : null;
                
            echo "Retell Call:\n";
            echo "  - Call ID: " . $call['call_id'] . "\n";
            echo "  - Time: " . ($startTime ? $startTime->format('Y-m-d H:i:s') : 'N/A') . "\n";
            echo "  - From: " . ($call['from_number'] ?? 'N/A') . "\n";
            echo "  - Duration: " . ($call['call_length'] ?? 0) . "s\n";
            echo "  - Agent: " . ($call['agent_id'] ?? 'N/A') . "\n";
            
            // Check if this call is in our local DB
            $localCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('retell_call_id', $call['call_id'])
                ->first();
                
            echo "  - In Local DB: " . ($localCall ? "YES" : "NO") . "\n";
            echo "\n";
        }
    }
    
    // 4. Check for cached appointment data
    echo "4. CHECKING CACHED APPOINTMENT DATA:\n";
    if ($recentCalls->count() > 0) {
        foreach ($recentCalls->take(2) as $call) {
            $cacheKey = "retell_appointment_data:{$call->retell_call_id}";
            $cachedData = \Cache::get($cacheKey);
            
            echo "Call " . $call->retell_call_id . ":\n";
            if ($cachedData) {
                echo "  ✓ Has cached appointment data:\n";
                echo "    - Reference: " . ($cachedData['reference_id'] ?? 'N/A') . "\n";
                echo "    - Date: " . ($cachedData['datum'] ?? 'N/A') . "\n";
                echo "    - Time: " . ($cachedData['uhrzeit'] ?? 'N/A') . "\n";
                echo "    - Name: " . ($cachedData['name'] ?? 'N/A') . "\n";
                echo "    - Service: " . ($cachedData['dienstleistung'] ?? 'N/A') . "\n";
            } else {
                echo "  ✗ No cached appointment data\n";
            }
            echo "\n";
        }
    }
    
    // Summary
    echo "\n=== SUMMARY ===\n";
    if ($recentCalls->count() > 0 || count($testCalls) > 0) {
        echo "✓ Test calls are being received and processed\n";
        
        $hasAppointments = $recentCalls->where('appointment_id', '!=', null)->count() > 0;
        if ($hasAppointments) {
            echo "✓ Some calls have successfully created appointments\n";
        } else {
            echo "✗ No appointments have been created yet\n";
        }
    } else {
        echo "✗ No test calls found in the system\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}