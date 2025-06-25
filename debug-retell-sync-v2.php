<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== RETELL SYNC DEBUG V2 ===\n\n";

try {
    // Get company directly from DB
    $company = DB::table('companies')->first();
    echo "Company: {$company->name}\n";
    
    // Get API key - try from env first
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    if (!$apiKey && $company->retell_api_key) {
        $apiKey = $company->retell_api_key;
        // Try to decrypt if it looks encrypted
        if (strpos($apiKey, 'eyJ') === 0) {
            try {
                $apiKey = decrypt($apiKey);
            } catch (\Exception $e) {
                // If decryption fails, it might already be plain text
                echo "Note: Could not decrypt API key, using as-is\n";
            }
        }
    }
    echo "Retell API Key: " . substr($apiKey, 0, 20) . "...\n\n";
    
    // Get branches with phone numbers
    $branches = DB::table('branches')
        ->whereNotNull('phone_number')
        ->where('phone_number', '!=', '')
        ->get();
    
    echo "Branches with phone numbers:\n";
    foreach ($branches as $branch) {
        echo "- {$branch->name}: {$branch->phone_number}\n";
        echo "  Agent ID: " . ($branch->retell_agent_id ?? 'NONE') . "\n";
        echo "  Active: " . ($branch->is_active ? 'YES' : 'NO') . "\n";
        echo "  Last sync: " . ($branch->retell_last_sync ?? 'Never') . "\n\n";
    }
    
    // Initialize Retell service with API key
    $retellService = new RetellV2Service($apiKey);
    
    echo "Fetching phone numbers from Retell API...\n";
    $phoneResponse = $retellService->listPhoneNumbers();
    
    if (!empty($phoneResponse['phone_numbers'])) {
        echo "\nPhone numbers in Retell:\n";
        foreach ($phoneResponse['phone_numbers'] as $phone) {
            echo "- Number: {$phone['phone_number']}\n";
            echo "  Agent ID: " . ($phone['agent_id'] ?? 'NONE') . "\n";
            echo "  Inbound Agent: " . ($phone['inbound_agent_id'] ?? 'NONE') . "\n";
            echo "  Status: " . ($phone['status'] ?? 'unknown') . "\n\n";
        }
    } else {
        echo "No phone numbers found in Retell.\n";
    }
    
    // Get recent calls
    echo "\nFetching recent calls from Retell API...\n";
    $callsResponse = $retellService->listCalls(10);
    
    if (!empty($callsResponse['calls'])) {
        echo "\nFound " . count($callsResponse['calls']) . " recent calls:\n";
        foreach ($callsResponse['calls'] as $index => $call) {
            $startTime = isset($call['start_timestamp']) ? 
                date('Y-m-d H:i:s', $call['start_timestamp'] / 1000) : 
                'Unknown';
            
            echo "\nCall #" . ($index + 1) . ":\n";
            echo "  Call ID: {$call['call_id']}\n";
            echo "  From: " . ($call['from_number'] ?? 'Unknown') . "\n";
            echo "  To: " . ($call['to_number'] ?? 'Unknown') . "\n";
            echo "  Agent: " . ($call['agent_id'] ?? 'Unknown') . "\n";
            echo "  Start: {$startTime}\n";
            echo "  Duration: " . ($call['call_length'] ?? 0) . " seconds\n";
            echo "  Status: " . ($call['call_status'] ?? 'Unknown') . "\n";
            echo "  Disconnection: " . ($call['disconnection_reason'] ?? 'Unknown') . "\n";
            
            // Show if this call exists in our DB
            $dbCall = DB::table('calls')->where('call_id', $call['call_id'])->first();
            echo "  In DB: " . ($dbCall ? 'YES' : 'NO') . "\n";
        }
    } else {
        echo "No recent calls found.\n";
    }
    
    // Check webhook configuration
    echo "\n\nWebhook Configuration:\n";
    echo "Webhook URL should be: " . config('app.url') . "/api/retell/webhook\n";
    echo "Webhook Secret in .env: " . (env('RETELL_WEBHOOK_SECRET') ? 'SET' : 'NOT SET') . "\n";
    
    // Check recent webhook events
    $recentWebhooks = DB::table('webhook_events')
        ->where('created_at', '>=', now()->subHours(24))
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "\nRecent webhook events (last 24h):\n";
    foreach ($recentWebhooks as $webhook) {
        echo "- {$webhook->created_at}: Type: {$webhook->type}, Status: {$webhook->status}\n";
        $payload = json_decode($webhook->payload, true);
        if (isset($payload['event_type'])) {
            echo "  Event: {$payload['event_type']}\n";
        }
        if (isset($payload['call_id'])) {
            echo "  Call ID: {$payload['call_id']}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
}