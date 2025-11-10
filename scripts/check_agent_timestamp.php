<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Retell\RetellAgentManagementService;
use Illuminate\Support\Facades\Http;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$conversationFlowId = 'conversation_flow_1607b81c8f93';

echo "=== TIMESTAMPS CHECK ===\n\n";

$service = new RetellAgentManagementService();

try {
    // Check Agent
    echo "📍 AGENT:\n";
    $agent = $service->getAgentStatus($agentId);
    if ($agent) {
        echo "  Agent Name: " . ($agent['agent_name'] ?? 'Unknown') . "\n";
        echo "  Version: " . ($agent['version'] ?? 'unknown') . "\n";
        echo "  Last Modified: " . ($agent['last_modification_timestamp'] ?? 'N/A') . "\n";

        if (isset($agent['last_modification_timestamp'])) {
            $timestamp = $agent['last_modification_timestamp'];
            $date = date('Y-m-d H:i:s', $timestamp);
            echo "  Human Readable: $date\n";
        }
    }

    echo "\n";

    // Check Conversation Flow
    echo "📍 CONVERSATION FLOW:\n";
    $apiKey = config('services.retellai.api_key');
    $baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

    $response = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

    if ($response->successful()) {
        $flow = $response->json();
        echo "  Flow ID: " . ($flow['conversation_flow_id'] ?? 'Unknown') . "\n";
        echo "  Version: " . ($flow['version'] ?? 'unknown') . "\n";
        echo "  Is Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n";

        // Check if global_prompt has our updated content
        $globalPrompt = $flow['global_prompt'] ?? '';
        echo "\n📊 CONTENT CHECK:\n";
        echo "  Global Prompt Length: " . strlen($globalPrompt) . " chars\n";
        echo "  Contains 'VOLLSTÄNDIGE LISTE': " . (stripos($globalPrompt, 'VOLLSTÄNDIGE LISTE') !== false ? '✅' : '❌') . "\n";
        echo "  Contains 'Hairdetox': " . (stripos($globalPrompt, 'Hairdetox') !== false ? '✅' : '❌') . "\n";
        echo "  Contains 'Hair Detox': " . (stripos($globalPrompt, 'Hair Detox') !== false ? '✅' : '❌') . "\n";
        echo "  Contains 'Balayage': " . (stripos($globalPrompt, 'Balayage') !== false ? '✅' : '❌') . "\n";

        // Show first part of service list
        if (preg_match('/## Unsere Services.*?(?=##|$)/s', $globalPrompt, $matches)) {
            echo "\n📝 SERVICE SECTION (first 500 chars):\n";
            echo substr($matches[0], 0, 500) . "...\n";
        }
    }

    echo "\n";

    // Check if agent is using this flow
    echo "📍 AGENT → FLOW LINK:\n";
    if (isset($agent['response_engine']['conversation_flow_id'])) {
        $linkedFlowId = $agent['response_engine']['conversation_flow_id'];
        echo "  Agent uses Flow: $linkedFlowId\n";
        echo "  Target Flow: $conversationFlowId\n";
        echo "  Match: " . ($linkedFlowId === $conversationFlowId ? '✅ CORRECT' : '❌ MISMATCH!') . "\n";
    } else {
        echo "  ❌ Agent has no conversation_flow_id!\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
