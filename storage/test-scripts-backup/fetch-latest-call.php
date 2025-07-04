<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use App\Models\Call;
use App\Models\Company;
use App\Services\CallDataRefresher;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Bypass tenant scope for this operation
app()->bind('current_company_id', function () {
    return Company::first()->id;
});

$apiKey = config('services.retell.api_key');
$baseUrl = rtrim(config('services.retell.base', 'https://api.retellai.com'), '/');

echo "Fetching latest call from Retell...\n";
echo "Looking for call with 'Inge' or recent calls...\n\n";

// Get agent details first to see recent activity
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

$ch = curl_init($baseUrl . '/get-agent/' . $agentId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $agent = json_decode($response, true);
    echo "Agent: " . $agent['agent_name'] . "\n";
    
    // Check if agent has recent activity info
    if (isset($agent['last_activity'])) {
        echo "Last activity: " . $agent['last_activity'] . "\n";
    }
}

// Try different methods to get call history
echo "\nTrying to fetch recent calls...\n";

// Method 1: Try POST request for list-calls (Retell v2 style)
$ch = curl_init($baseUrl . '/v2/list-calls');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'limit' => 10,
    'sort_order' => 'descending'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $data = json_decode($response, true);
    $calls = $data['calls'] ?? $data;
    
    if (is_array($calls) && !empty($calls)) {
        echo "✅ Found " . count($calls) . " recent calls!\n\n";
        
        foreach ($calls as $index => $call) {
            echo "Call #" . ($index + 1) . ":\n";
            echo "  ID: " . $call['call_id'] . "\n";
            echo "  From: " . ($call['from_number'] ?? 'Unknown') . "\n";
            echo "  Status: " . $call['call_status'] . "\n";
            
            if (isset($call['start_timestamp'])) {
                $time = date('Y-m-d H:i:s', $call['start_timestamp'] / 1000);
                echo "  Time: " . $time . "\n";
                
                $minutesAgo = round((time() - ($call['start_timestamp'] / 1000)) / 60);
                echo "  Minutes ago: " . $minutesAgo . "\n";
            }
            
            // Check transcript for "Inge"
            if (isset($call['transcript']) && stripos($call['transcript'], 'inge') !== false) {
                echo "  ⭐ FOUND 'Inge' in transcript!\n";
            }
            
            if (isset($call['call_analysis']['custom_analysis_data']['_name'])) {
                $name = $call['call_analysis']['custom_analysis_data']['_name'];
                echo "  Name: " . $name . "\n";
                if (stripos($name, 'inge') !== false) {
                    echo "  ⭐ FOUND 'Inge' in name!\n";
                }
            }
            
            echo "\n";
            
            // Save the most recent call to database
            if ($index === 0 && $minutesAgo < 10) {
                echo "Saving this recent call to database...\n";
                
                $existingCall = Call::where('call_id', $call['call_id'])
                    ->orWhere('retell_call_id', $call['call_id'])
                    ->first();
                
                if (!$existingCall) {
                    try {
                        $newCall = Call::create([
                            'company_id' => Company::first()->id,
                            'call_id' => $call['call_id'],
                            'retell_call_id' => $call['call_id'],
                            'agent_id' => $call['agent_id'] ?? $agentId,
                            'from_number' => $call['from_number'] ?? '+491604366218',
                            'to_number' => $call['to_number'] ?? '+493083793369',
                            'direction' => $call['call_type'] ?? 'inbound',
                            'status' => $call['call_status'] ?? 'completed',
                            'start_timestamp' => isset($call['start_timestamp']) 
                                ? \Carbon\Carbon::createFromTimestampMs($call['start_timestamp']) 
                                : now(),
                            'end_timestamp' => isset($call['end_timestamp']) 
                                ? \Carbon\Carbon::createFromTimestampMs($call['end_timestamp']) 
                                : now(),
                            'duration_sec' => isset($call['duration_ms']) 
                                ? round($call['duration_ms'] / 1000) 
                                : 60,
                            'transcript' => $call['transcript'] ?? null,
                            'summary' => $call['call_analysis']['call_summary'] ?? null,
                            'analysis' => $call['call_analysis'] ?? null,
                        ]);
                        
                        echo "✅ Call saved to database with ID: " . $newCall->id . "\n";
                    } catch (\Exception $e) {
                        echo "❌ Error saving call: " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "Call already exists in database.\n";
                }
            }
        }
    } else {
        echo "No calls found in response.\n";
    }
} else {
    echo "Failed to fetch calls. HTTP Code: $httpCode\n";
    
    // Try alternative API endpoints
    echo "\nTrying alternative endpoints...\n";
    
    // Some APIs use GET with query params
    $endpoints = [
        '/api/v1/calls?limit=10',
        '/calls?limit=10&sort=desc',
        '/v1/calls/list?limit=10'
    ];
    
    foreach ($endpoints as $endpoint) {
        echo "Trying: $endpoint\n";
        $response = Http::withToken($apiKey)
            ->timeout(10)
            ->get($baseUrl . $endpoint);
        
        if ($response->successful()) {
            echo "✅ Success at $endpoint\n";
            break;
        } else {
            echo "  Failed: " . $response->status() . "\n";
        }
    }
}

echo "\nDone!\n";