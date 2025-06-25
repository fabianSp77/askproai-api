<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Call;
use App\Models\WebhookEvent;
use App\Services\RetellV2Service;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECKING ALL RECENT CALLS AND WEBHOOKS ===\n\n";

try {
    // 1. Check ALL recent calls (not just to test number)
    echo "1. ALL RECENT CALLS IN DATABASE:\n";
    $allRecentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where('created_at', '>=', Carbon::now()->subDays(7))
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    echo "Found " . $allRecentCalls->count() . " calls in last 7 days\n\n";
    
    foreach ($allRecentCalls as $call) {
        echo "Call " . $call->id . ":\n";
        echo "  - Time: " . $call->created_at->format('Y-m-d H:i:s') . "\n";
        echo "  - From: " . $call->from_number . "\n";
        echo "  - To: " . $call->to_number . "\n";
        echo "  - Retell ID: " . $call->retell_call_id . "\n";
        echo "  - Status: " . $call->status . "\n";
        echo "\n";
    }
    
    // 2. Check ALL webhook events
    echo "2. ALL RECENT WEBHOOK EVENTS:\n";
    $allWebhooks = WebhookEvent::where('created_at', '>=', Carbon::now()->subDays(2))
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    echo "Found " . $allWebhooks->count() . " webhook events in last 2 days\n\n";
    
    foreach ($allWebhooks as $webhook) {
        echo "Webhook " . $webhook->id . ":\n";
        echo "  - Time: " . $webhook->created_at->format('Y-m-d H:i:s') . "\n";
        echo "  - Provider: " . $webhook->provider . "\n";
        echo "  - Event: " . $webhook->event_type . "\n";
        echo "  - Status: " . $webhook->status . "\n";
        
        if ($webhook->provider === 'retell') {
            $payload = is_string($webhook->payload) ? json_decode($webhook->payload, true) : $webhook->payload;
            echo "  - Call ID: " . ($payload['call']['call_id'] ?? $payload['call_id'] ?? 'N/A') . "\n";
            echo "  - To Number: " . ($payload['call']['to_number'] ?? 'N/A') . "\n";
        }
        echo "\n";
    }
    
    // 3. Check Retell API with debug
    echo "3. RETELL API DEBUG:\n";
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    echo "Using API Key: " . substr($apiKey, 0, 20) . "...\n\n";
    
    $retellService = new RetellV2Service($apiKey);
    
    // Try direct API call
    echo "Making direct API call to list calls...\n";
    $ch = curl_init('https://api.retellai.com/v2/list-calls');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['limit' => 10]));
    
    $rawResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Response Code: $httpCode\n";
    
    if ($httpCode === 200) {
        $data = json_decode($rawResponse, true);
        echo "API Response: " . count($data['calls'] ?? []) . " calls found\n\n";
        
        if (!empty($data['calls'])) {
            foreach (array_slice($data['calls'], 0, 3) as $call) {
                $startTime = isset($call['start_timestamp']) ? 
                    date('Y-m-d H:i:s', $call['start_timestamp'] / 1000) : 'N/A';
                    
                echo "API Call:\n";
                echo "  - Call ID: " . $call['call_id'] . "\n";
                echo "  - Time: " . $startTime . "\n";
                echo "  - To: " . ($call['to_number'] ?? 'N/A') . "\n";
                echo "  - From: " . ($call['from_number'] ?? 'N/A') . "\n";
                echo "\n";
            }
        }
    } else {
        echo "API Error: " . substr($rawResponse, 0, 200) . "\n";
    }
    
    // 4. Check logs for call activity
    echo "\n4. CHECKING LOGS FOR RETELL ACTIVITY:\n";
    $logFile = storage_path('logs/laravel.log');
    $recentLogs = `tail -n 1000 $logFile | grep -i "retell" | tail -n 10`;
    
    if ($recentLogs) {
        echo "Recent Retell-related log entries:\n";
        echo substr($recentLogs, 0, 1000) . "...\n";
    } else {
        echo "No recent Retell activity in logs\n";
    }
    
    // 5. Check if webhooks are being processed
    echo "\n5. WEBHOOK PROCESSING STATUS:\n";
    $pendingWebhooks = WebhookEvent::where('status', 'pending')->count();
    $failedWebhooks = WebhookEvent::where('status', 'failed')->count();
    
    echo "Pending webhooks: $pendingWebhooks\n";
    echo "Failed webhooks: $failedWebhooks\n";
    
    // 6. Check phone number configuration
    echo "\n6. PHONE NUMBER CONFIGURATION:\n";
    $phones = $retellService->listPhoneNumbers();
    echo "Total phone numbers configured: " . count($phones['phone_numbers'] ?? []) . "\n";
    
    foreach ($phones['phone_numbers'] ?? [] as $phone) {
        echo "\nPhone: " . $phone['phone_number'] . "\n";
        echo "  - Status: " . ($phone['status'] ?? 'unknown') . "\n";
        echo "  - Agent: " . ($phone['inbound_agent_id'] ?? 'NONE') . "\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}