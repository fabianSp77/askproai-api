#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║ Checking Conversational Agent Configuration           ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$retellApiKey = env('RETELL_TOKEN', 'key_6ff998ba48e842092e04a5455d19');
$retellBaseUrl = env('RETELL_BASE', 'https://api.retellai.com');
$agentId = 'agent_616d645570ae613e421edb98e7';

echo "Fetching agent configuration...\n";
echo "Agent ID: $agentId\n\n";

try {
    // Get specific agent
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $retellApiKey,
        'Content-Type' => 'application/json',
    ])->get("{$retellBaseUrl}/get-agent/{$agentId}");

    if ($response->successful()) {
        $agent = $response->json();

        echo "✅ Agent Found!\n\n";
        echo "=== BASIC INFO ===\n";
        echo "Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
        echo "Language: " . ($agent['language'] ?? 'N/A') . "\n";
        echo "Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
        echo "Response Engine: " . ($agent['response_engine']['type'] ?? 'N/A') . "\n";
        echo "LLM Websocket URL: " . ($agent['llm_websocket_url'] ?? 'NOT SET') . "\n";
        echo "Webhook URL: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n\n";

        // Check for functions
        echo "=== FUNCTIONS ===\n";
        if (isset($agent['functions']) && is_array($agent['functions'])) {
            echo "Total Functions: " . count($agent['functions']) . "\n\n";

            foreach ($agent['functions'] as $idx => $func) {
                echo "Function #" . ($idx + 1) . ":\n";
                echo "  Name: " . ($func['name'] ?? 'N/A') . "\n";
                echo "  Description: " . ($func['description'] ?? 'N/A') . "\n";

                if (isset($func['parameters']['properties'])) {
                    echo "  Parameters:\n";
                    foreach ($func['parameters']['properties'] as $paramName => $paramDef) {
                        $type = $paramDef['type'] ?? 'unknown';
                        $required = in_array($paramName, $func['parameters']['required'] ?? []) ? ' (required)' : ' (optional)';
                        echo "    - $paramName: $type$required\n";
                    }
                }
                echo "\n";
            }

            // Check specifically for our needed functions
            echo "=== CRITICAL CHECKS ===\n";

            $functionNames = array_column($agent['functions'], 'name');

            // Check list_services
            if (in_array('list_services', $functionNames)) {
                echo "✅ list_services function EXISTS\n";
            } else {
                echo "❌ list_services function MISSING\n";
            }

            // Check collect_appointment_data
            $collectFunc = null;
            foreach ($agent['functions'] as $func) {
                if ($func['name'] === 'collect_appointment_data') {
                    $collectFunc = $func;
                    break;
                }
            }

            if ($collectFunc) {
                echo "✅ collect_appointment_data function EXISTS\n";

                $params = $collectFunc['parameters']['properties'] ?? [];
                if (isset($params['service_id'])) {
                    echo "  ✅ Has service_id parameter\n";
                    echo "     Type: " . ($params['service_id']['type'] ?? 'N/A') . "\n";
                } else {
                    echo "  ❌ MISSING service_id parameter!\n";
                    echo "  ⚠️ This is the ROOT CAUSE of the booking failure!\n\n";
                    echo "  Current parameters:\n";
                    foreach ($params as $name => $def) {
                        echo "    - $name\n";
                    }
                }
            } else {
                echo "❌ collect_appointment_data function MISSING\n";
            }

        } else {
            echo "⚠️ No functions configured!\n";
        }

        echo "\n=== FULL RESPONSE (JSON) ===\n";
        echo json_encode($agent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

    } else {
        echo "❌ Failed to fetch agent!\n";
        echo "Status: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
