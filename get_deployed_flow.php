<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$flowId = trim(file_get_contents(__DIR__ . '/flow_v2_fixed_id.txt'));

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 CHECKING DEPLOYED FLOW CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Flow ID: $flowId\n\n";

$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-conversation-flow/$flowId");

if (!$response->successful()) {
    echo "❌ Failed to get flow\n";
    echo "Status: {$response->status()}\n";
    echo "Body: {$response->body()}\n";
    exit(1);
}

$flow = $response->json();

// Save full config
file_put_contents(__DIR__ . '/deployed_flow_v2_actual.json', json_encode($flow, JSON_PRETTY_PRINT));

echo "✅ Flow Retrieved\n\n";

// Check tools
if (isset($flow['tools'])) {
    echo "📦 TOOLS IN DEPLOYED FLOW:\n";
    echo "Number of tools: " . count($flow['tools']) . "\n\n";

    foreach ($flow['tools'] as $index => $tool) {
        echo "─────────────────────────────────────────\n";
        echo "Tool #" . ($index + 1) . ": {$tool['name']}\n";
        echo "  tool_id: " . ($tool['tool_id'] ?? 'N/A') . "\n";
        echo "  url: " . ($tool['url'] ?? 'N/A') . "\n";

        if (isset($tool['type'])) echo "  type: {$tool['type']}\n";
        if (isset($tool['timeout_ms'])) echo "  timeout_ms: {$tool['timeout_ms']}\n";
        if (isset($tool['speak_during_execution'])) echo "  speak_during_execution: " . ($tool['speak_during_execution'] ? 'true' : 'false') . "\n";
        if (isset($tool['speak_during_execution_message'])) echo "  speak_during_execution_message: {$tool['speak_during_execution_message']}\n";

        echo "  Parameters:\n";
        if (isset($tool['parameters']['required'])) {
            echo "    Required: " . implode(', ', $tool['parameters']['required']) . "\n";
        }
        if (isset($tool['parameters']['properties'])) {
            echo "    Properties: " . implode(', ', array_keys($tool['parameters']['properties'])) . "\n";
        }
        echo "\n";
    }
}

// Check nodes with parameter_mapping
echo "\n📋 FUNCTION NODES WITH PARAMETER_MAPPING:\n\n";
if (isset($flow['nodes'])) {
    foreach ($flow['nodes'] as $node) {
        if (isset($node['type']) && $node['type'] === 'function' && isset($node['parameter_mapping'])) {
            echo "─────────────────────────────────────────\n";
            echo "Node: {$node['name']}\n";
            echo "  ID: {$node['id']}\n";
            echo "  tool_id: {$node['tool_id']}\n";
            echo "  parameter_mapping:\n";
            foreach ($node['parameter_mapping'] as $key => $value) {
                echo "    $key: $value\n";
            }
            echo "\n";
        }
    }
}

echo "\n✅ Full deployed config saved to: deployed_flow_v2_actual.json\n\n";
