<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DETAILED RETELL CHECK ===\n\n";

try {
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    echo "Using API Key: " . substr($apiKey, 0, 20) . "...\n\n";
    
    $retellService = new RetellV2Service($apiKey);
    
    // Try different API endpoints
    echo "1. Testing listCalls with different parameters:\n";
    
    // Standard call
    echo "   Standard listCalls(20):\n";
    $response1 = $retellService->listCalls(20);
    echo "   - Found " . count($response1['calls'] ?? []) . " calls\n";
    
    // Try with more calls
    echo "   listCalls(1000):\n";
    $response2 = $retellService->listCalls(1000);
    echo "   - Found " . count($response2['calls'] ?? []) . " calls\n";
    
    // If we have calls, show the latest
    if (!empty($response2['calls'])) {
        $latestCall = $response2['calls'][0];
        echo "\n   Latest call:\n";
        echo "   - Call ID: " . $latestCall['call_id'] . "\n";
        echo "   - Timestamp: " . date('Y-m-d H:i:s', $latestCall['start_timestamp'] / 1000) . "\n";
        echo "   - Agent: " . $latestCall['agent_id'] . "\n";
    }
    
    // Check phone number details
    echo "\n2. Checking phone number +493083793369:\n";
    $phoneNumbers = $retellService->listPhoneNumbers();
    
    $ourPhone = null;
    foreach ($phoneNumbers['phone_numbers'] ?? [] as $phone) {
        if ($phone['phone_number'] === '+493083793369') {
            $ourPhone = $phone;
            break;
        }
    }
    
    if ($ourPhone) {
        echo "   Phone found:\n";
        echo "   - Status: " . ($ourPhone['status'] ?? 'unknown') . "\n";
        echo "   - Inbound Agent: " . ($ourPhone['inbound_agent_id'] ?? 'NONE') . "\n";
        echo "   - Agent ID: " . ($ourPhone['agent_id'] ?? 'NONE') . "\n";
    } else {
        echo "   Phone NOT FOUND in Retell!\n";
    }
    
    // Test API directly with curl to bypass any service issues
    echo "\n3. Testing raw API call:\n";
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
    
    echo "   HTTP Code: $httpCode\n";
    
    if ($httpCode === 200) {
        $data = json_decode($rawResponse, true);
        echo "   Calls found: " . count($data['calls'] ?? []) . "\n";
        
        if (!empty($data['calls'])) {
            echo "\n   First call in raw response:\n";
            $call = $data['calls'][0];
            echo "   - Call ID: " . $call['call_id'] . "\n";
            echo "   - Start: " . date('Y-m-d H:i:s', $call['start_timestamp'] / 1000) . "\n";
        }
    } else {
        echo "   Error: " . substr($rawResponse, 0, 200) . "\n";
    }
    
    // Check webhook logs
    echo "\n4. Recent webhook activity:\n";
    $recentWebhooks = \DB::table('webhook_events')
        ->where('created_at', '>=', now()->subHours(1))
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "   Found " . $recentWebhooks->count() . " webhooks in last hour\n";
    
    foreach ($recentWebhooks as $webhook) {
        $payload = json_decode($webhook->payload, true);
        echo "   - " . $webhook->created_at . ": " . ($payload['event_type'] ?? 'unknown') . " (Status: " . $webhook->status . ")\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
}