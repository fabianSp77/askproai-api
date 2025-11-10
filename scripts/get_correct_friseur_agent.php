<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Retell\RetellAgentManagementService;

$agentId = 'agent_45daa54928c5768b52ba3db736'; // Friseur1 Fixed V2

echo "=== RICHTIGER FRISEUR 1 AGENT ===\n\n";
echo "Agent ID: $agentId\n\n";

$service = new RetellAgentManagementService();

try {
    $agent = $service->getAgentStatus($agentId);

    if ($agent) {
        echo "✅ Agent gefunden: " . ($agent['agent_name'] ?? 'Unknown') . "\n";
        echo "Version: " . ($agent['version'] ?? 'unknown') . "\n";
        echo "Is Published: " . ($agent['is_published'] ? 'YES' : 'NO') . "\n\n";

        if (isset($agent['response_engine'])) {
            $engine = $agent['response_engine'];
            echo "=== RESPONSE ENGINE ===\n";
            echo "Type: " . ($engine['type'] ?? 'unknown') . "\n";

            if ($engine['type'] === 'conversation-flow' && isset($engine['conversation_flow_id'])) {
                echo "Conversation Flow ID: " . $engine['conversation_flow_id'] . "\n";
            } elseif (isset($engine['llm_id'])) {
                echo "LLM ID: " . $engine['llm_id'] . "\n";
            }
        }

    } else {
        echo "❌ Agent nicht gefunden\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
