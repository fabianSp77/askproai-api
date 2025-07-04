<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\RetellV2Service;
use App\Models\Company;
use App\Models\Call;
use App\Models\WebhookEvent;
use App\Scopes\TenantScope;
use Carbon\Carbon;

echo "=== Fetching Latest Calls from Retell API ===\n\n";

// Get API key
$company = Company::withoutGlobalScope(TenantScope::class)->first();
$apiKey = $company->retell_api_key ?? env('DEFAULT_RETELL_API_KEY');

echo "Using API Key: " . substr($apiKey, 0, 20) . "...\n\n";

// Fetch recent calls from Retell
try {
    $retellService = new RetellV2Service($apiKey);
    
    // Try to list recent calls
    echo "1. Fetching recent calls from Retell:\n";
    echo str_repeat("-", 80) . "\n";
    
    $response = $retellService->makeRequest('GET', '/list-calls', [
        'limit' => 10,
        'sort_order' => 'descending'
    ]);
    
    if (isset($response['calls']) && is_array($response['calls'])) {
        foreach ($response['calls'] as $call) {
            echo "\nCall ID: " . ($call['call_id'] ?? 'N/A') . "\n";
            echo "Created: " . ($call['start_timestamp'] ? date('Y-m-d H:i:s', $call['start_timestamp']/1000) : 'N/A') . "\n";
            echo "From: " . ($call['from_number'] ?? 'N/A') . "\n";
            echo "To: " . ($call['to_number'] ?? 'N/A') . "\n";
            echo "Status: " . ($call['call_status'] ?? 'N/A') . "\n";
            echo "Agent ID: " . ($call['agent_id'] ?? 'N/A') . "\n";
            
            // Check if this call is for 16:00 appointment
            if (isset($call['call_analysis']['custom_analysis_data']['appointment_date_time'])) {
                $appointmentTime = $call['call_analysis']['custom_analysis_data']['appointment_date_time'];
                echo "✅ APPOINTMENT TIME: {$appointmentTime}\n";
                
                if (strpos($appointmentTime, '16:00') !== false || strpos($appointmentTime, '16 Uhr') !== false) {
                    echo "🎯 THIS IS THE 16:00 APPOINTMENT!\n";
                }
            }
            
            // Check if call exists in our database
            $existingCall = Call::withoutGlobalScope(TenantScope::class)
                ->where('retell_call_id', $call['call_id'])
                ->first();
                
            if ($existingCall) {
                echo "✅ Call exists in database (ID: {$existingCall->id})\n";
            } else {
                echo "❌ Call NOT in database - needs to be imported!\n";
                
                // Create webhook event for this call
                echo "   Creating webhook event...\n";
                
                $webhookPayload = [
                    'event' => 'call_ended',
                    'call' => $call
                ];
                
                $webhookEvent = WebhookEvent::withoutGlobalScope(TenantScope::class)->create([
                    'provider' => 'retell',
                    'event_type' => 'call_ended',
                    'payload' => $webhookPayload,
                    'status' => 'pending',
                    'created_at' => now()
                ]);
                
                echo "   Created webhook event ID: {$webhookEvent->id}\n";
            }
            
            echo str_repeat("-", 40) . "\n";
        }
        
        echo "\nTotal calls found: " . count($response['calls']) . "\n";
        
    } else {
        echo "No calls found or unexpected response format\n";
        print_r($response);
    }
    
} catch (\Exception $e) {
    echo "❌ Error fetching calls: " . $e->getMessage() . "\n";
    echo "Error type: " . get_class($e) . "\n";
    
    // Try alternative approach
    echo "\n2. Trying alternative API endpoint:\n";
    
    try {
        // Try the v1 endpoint
        $ch = curl_init('https://api.retellai.com/v1/list-calls');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "HTTP Code: {$httpCode}\n";
        
        if ($httpCode === 200) {
            $data = json_decode($result, true);
            if (isset($data['calls'])) {
                echo "✅ Found " . count($data['calls']) . " calls via direct API\n";
                
                // Show first few
                foreach (array_slice($data['calls'], 0, 3) as $call) {
                    echo "- Call ID: " . ($call['call_id'] ?? 'N/A') . " from " . ($call['from_number'] ?? 'N/A') . "\n";
                }
            }
        } else {
            echo "Response: " . substr($result, 0, 200) . "\n";
        }
        
    } catch (\Exception $e2) {
        echo "Alternative approach also failed: " . $e2->getMessage() . "\n";
    }
}

// Check if webhooks are being queued
echo "\n3. Checking webhook queue:\n";
echo str_repeat("-", 80) . "\n";

$pendingWebhooks = WebhookEvent::withoutGlobalScope(TenantScope::class)
    ->where('provider', 'retell')
    ->where('status', 'pending')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "Pending webhooks: " . $pendingWebhooks->count() . "\n";

foreach ($pendingWebhooks as $webhook) {
    echo "- ID: {$webhook->id} | Type: {$webhook->event_type} | Created: {$webhook->created_at}\n";
}

echo "\n=== Check Complete ===\n";