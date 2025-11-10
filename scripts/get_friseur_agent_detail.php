<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\Retell\RetellAgentManagementService;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9'; // Conversation Flow Agent Friseur 1

echo "=== FRISEUR 1 AGENT DETAILS ===\n\n";
echo "Agent ID: $agentId\n\n";

$service = new RetellAgentManagementService();

try {
    $agent = $service->getAgentStatus($agentId);

    if ($agent) {
        echo "✅ Agent Name: " . ($agent['agent_name'] ?? 'Unknown') . "\n";
        echo "Version: " . ($agent['version'] ?? 'unknown') . "\n\n";

        // Check for general_prompt
        if (isset($agent['general_prompt']) && !empty($agent['general_prompt'])) {
            echo "=== GENERAL PROMPT (first 2000 chars) ===\n";
            echo substr($agent['general_prompt'], 0, 2000) . "...\n\n";

            // Check service mentions
            $services = ['Hairdetox', 'Hair Detox', 'Herrenhaarschnitt', 'Balayage', 'Dauerwelle'];
            echo "=== SERVICE-ERWÄHNUNGEN ===\n";
            foreach ($services as $svc) {
                $found = stripos($agent['general_prompt'], $svc) !== false ? '✅' : '❌';
                echo "$found $svc\n";
            }
            echo "\n";
        } else {
            echo "⚠️ Kein 'general_prompt' Feld gefunden\n";
            echo "Verfügbare Felder:\n";
            foreach (array_keys($agent) as $key) {
                echo "  - $key\n";
            }
        }

        // Check LLM configuration
        if (isset($agent['response_engine'])) {
            echo "\n=== RESPONSE ENGINE ===\n";
            $engine = $agent['response_engine'];
            echo "Type: " . ($engine['type'] ?? 'unknown') . "\n";

            if (isset($engine['llm_id'])) {
                echo "LLM ID: " . $engine['llm_id'] . "\n";

                // Try to get LLM data
                echo "\nFetching LLM data...\n";
                $llmData = $service->getLlmData($engine['llm_id']);

                if ($llmData && isset($llmData['general_prompt'])) {
                    echo "\n=== LLM GENERAL PROMPT (first 2000 chars) ===\n";
                    echo substr($llmData['general_prompt'], 0, 2000) . "...\n\n";

                    // Check service mentions in LLM prompt
                    $services = ['Hairdetox', 'Hair Detox', 'Herrenhaarschnitt', 'Balayage'];
                    echo "=== SERVICE-ERWÄHNUNGEN IN LLM ===\n";
                    foreach ($services as $svc) {
                        $found = stripos($llmData['general_prompt'], $svc) !== false ? '✅' : '❌';
                        echo "$found $svc\n";
                    }
                }
            }
        }

    } else {
        echo "❌ Agent nicht gefunden\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
