<?php

/**
 * Check V20 Tool Definitions for call_id Parameter
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "🔍 CHECKING V20 TOOL DEFINITIONS\n";
echo str_repeat('=', 80) . "\n\n";

// Get flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);

echo "Flow Version: V{$flow['version']}\n\n";

// Check check_availability_v17 and book_appointment_v17
$toolNames = ['check_availability_v17', 'book_appointment_v17'];

foreach ($flow['tools'] as $tool) {
    if (in_array($tool['name'], $toolNames)) {
        echo "Tool: {$tool['name']}\n";
        echo str_repeat('-', 80) . "\n";

        echo "Parameters:\n";
        $hasCallId = false;

        if (isset($tool['parameters']) && is_array($tool['parameters'])) {
            foreach ($tool['parameters'] as $param) {
                $name = is_array($param) ? ($param['name'] ?? 'unknown') : 'ERROR';
                $desc = is_array($param) ? ($param['description'] ?? '') : '';
                $required = is_array($param) ? ($param['required'] ?? false) : false;

                $icon = ($name === 'call_id') ? '🔑' : '  ';
                $req = $required ? '[required]' : '[optional]';

                echo "  {$icon} {$name} {$req}: {$desc}\n";

                if ($name === 'call_id') {
                    $hasCallId = true;
                }
            }
        }

        if (!$hasCallId) {
            echo "  ❌ call_id parameter is MISSING from tool definition!\n";
        }

        echo "\n";
    }
}

echo str_repeat('=', 80) . "\n";
echo "ROOT CAUSE ANALYSIS:\n";
echo str_repeat('=', 80) . "\n\n";

echo "The call_id must be defined in TWO places:\n";
echo "1. Tool Definition (tools array) - defines the parameter\n";
echo "2. Function Node (parameter_mapping) - maps the value\n\n";

echo "If call_id is missing from tool definition, Retell won't send it!\n";
