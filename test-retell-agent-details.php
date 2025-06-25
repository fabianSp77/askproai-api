<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== RETELL AGENT DETAILS CHECK ===\n\n";

try {
    // Initialize Retell service
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    $retellService = new RetellV2Service($apiKey);
    
    // Agent ID for "Assistent für Fabian Spitzer Rechtliches V33"
    $agentId = 'agent_9a8202a740cd3120d96fcfda1e';
    
    echo "Checking agent: $agentId\n\n";
    
    // Get agent details
    $agent = $retellService->getAgent($agentId);
    
    if ($agent) {
        echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
        echo "Status: " . ($agent['status'] ?? 'N/A') . "\n";
        echo "Voice ID: " . ($agent['voice_id'] ?? 'N/A') . "\n";
        echo "Language: " . ($agent['language'] ?? 'N/A') . "\n";
        echo "Webhook URL: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n";
        echo "Begin Message: " . substr($agent['begin_message'] ?? 'N/A', 0, 100) . "...\n";
        
        if (isset($agent['response_engine'])) {
            echo "\nResponse Engine:\n";
            echo "  Type: " . ($agent['response_engine']['type'] ?? 'N/A') . "\n";
            if (isset($agent['response_engine']['llm_id'])) {
                echo "  LLM ID: " . $agent['response_engine']['llm_id'] . "\n";
            }
        }
        
        // Check recent calls for this agent
        echo "\n\nChecking calls for this agent...\n";
        $response = $retellService->listCalls(100);
        
        if (!empty($response['calls'])) {
            $agentCalls = array_filter($response['calls'], function($call) use ($agentId) {
                return ($call['agent_id'] ?? '') === $agentId;
            });
            
            echo "Found " . count($agentCalls) . " calls for this agent\n";
            
            if (count($agentCalls) > 0) {
                echo "\nLatest 5 calls:\n";
                $latestCalls = array_slice($agentCalls, 0, 5);
                
                foreach ($latestCalls as $call) {
                    $startTime = isset($call['start_timestamp']) ? 
                        date('Y-m-d H:i:s', $call['start_timestamp'] / 1000) : 
                        'Unknown';
                    
                    echo "\n- Call ID: " . $call['call_id'] . "\n";
                    echo "  Time: " . $startTime . "\n";
                    echo "  From: " . ($call['from_number'] ?? 'Unknown') . "\n";
                    echo "  Duration: " . ($call['call_length'] ?? 0) . "s\n";
                    echo "  Status: " . ($call['call_status'] ?? 'Unknown') . "\n";
                }
            }
        }
        
    } else {
        echo "Agent not found!\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}