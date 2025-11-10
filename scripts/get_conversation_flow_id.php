<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Retell\RetellAgentManagementService;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9'; // Conversation Flow Agent Friseur 1

echo "=== CONVERSATION FLOW ID ===\n\n";
echo "Agent ID: $agentId\n\n";

$service = new RetellAgentManagementService();

try {
    $agent = $service->getAgentStatus($agentId);

    if ($agent) {
        echo "✅ Agent gefunden: " . ($agent['agent_name'] ?? 'Unknown') . "\n";
        echo "Version: " . ($agent['version'] ?? 'unknown') . "\n\n";

        if (isset($agent['response_engine'])) {
            $engine = $agent['response_engine'];
            echo "=== RESPONSE ENGINE ===\n";
            echo "Type: " . ($engine['type'] ?? 'unknown') . "\n";

            if (isset($engine['conversation_flow_id'])) {
                echo "\n✅ Conversation Flow ID: " . $engine['conversation_flow_id'] . "\n";
                echo "\n📋 Use this ID to fetch flow details:\n";
                echo "   GET /get-conversation-flow/{$engine['conversation_flow_id']}\n";
            } else {
                echo "\n⚠️ Kein 'conversation_flow_id' in response_engine gefunden\n";
                echo "\nVerfügbare Felder in response_engine:\n";
                foreach (array_keys($engine) as $key) {
                    echo "  - $key\n";
                }
            }
        } else {
            echo "❌ Kein response_engine gefunden\n";
        }

    } else {
        echo "❌ Agent nicht gefunden\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
