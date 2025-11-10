<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$conversationFlowId = 'conversation_flow_1607b81c8f93';
$apiKey = config('services.retellai.api_key');
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

echo "=== CONVERSATION FLOW DETAILS ===\n\n";
echo "Flow ID: $conversationFlowId\n\n";

try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

    if (!$response->successful()) {
        echo "❌ API Error: " . $response->status() . "\n";
        echo $response->body() . "\n";
        exit(1);
    }

    $flow = $response->json();

    // Save full response for analysis
    file_put_contents(__DIR__ . '/../conversation_flow_current.json', json_encode($flow, JSON_PRETTY_PRINT));
    echo "✅ Full flow saved to: conversation_flow_current.json\n\n";

    // Display key information
    echo "=== GLOBAL PROMPT (first 1000 chars) ===\n";
    $globalPrompt = $flow['global_prompt'] ?? '';
    echo substr($globalPrompt, 0, 1000) . "...\n\n";

    echo "=== SERVICE MENTIONS IN GLOBAL PROMPT ===\n";
    $services = ['Hairdetox', 'Hair Detox', 'Herrenhaarschnitt', 'Balayage', 'Dauerwelle'];
    foreach ($services as $svc) {
        $found = stripos($globalPrompt, $svc) !== false ? '✅' : '❌';
        echo "$found $svc\n";
    }
    echo "\n";

    echo "=== NODES ===\n";
    $nodes = $flow['nodes'] ?? [];
    echo "Total Nodes: " . count($nodes) . "\n\n";

    foreach ($nodes as $node) {
        $nodeId = $node['id'] ?? 'unknown';
        $nodeType = $node['type'] ?? 'unknown';
        echo "Node: $nodeId (Type: $nodeType)\n";

        if (isset($node['instruction']['text'])) {
            $instruction = $node['instruction']['text'];
            echo "  Instruction (first 200 chars): " . substr($instruction, 0, 200) . "...\n";
        }
        echo "\n";
    }

    echo "=== TOOLS ===\n";
    $tools = $flow['tools'] ?? [];
    echo "Total Tools: " . count($tools) . "\n";
    foreach ($tools as $tool) {
        echo "  - " . ($tool['name'] ?? 'unknown') . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
