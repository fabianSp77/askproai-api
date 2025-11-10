#!/usr/bin/env php
<?php

/**
 * Verify Published Agent Functions
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Published Agent Functions Verification\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

// Get agent details
$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to get agent\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
    exit(1);
}

$agent = $response->json();

echo "📋 Agent Info:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Agent ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
echo "Voice ID: " . ($agent['voice_id'] ?? 'N/A') . "\n";
echo "Language: " . ($agent['language'] ?? 'N/A') . "\n\n";

// Check response engine
if (isset($agent['response_engine'])) {
    echo "🔧 Response Engine:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Type: " . ($agent['response_engine']['type'] ?? 'N/A') . "\n";

    if (isset($agent['response_engine']['conversation_flow_id'])) {
        echo "Conversation Flow ID: " . $agent['response_engine']['conversation_flow_id'] . "\n";
    }
    echo "\n";
}

// Expected tools
$expectedTools = [
    'check_availability_v17',
    'book_appointment_v17',
    'start_booking',
    'confirm_booking',
    'get_customer_appointments',
    'cancel_appointment',
    'reschedule_appointment',
    'get_available_services'
];

echo "🛠️  Functions/Tools Verification:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Expected: " . count($expectedTools) . " tools\n\n";

$foundTools = [];

// Check if functions/tools exist
if (isset($agent['response_engine']['conversation_flow_id'])) {
    $flowId = $agent['response_engine']['conversation_flow_id'];

    // Get conversation flow to check tools
    $flowResponse = Http::withHeaders([
        'Authorization' => "Bearer {$retellApiKey}",
    ])->get("{$baseUrl}/get-conversation-flow/{$flowId}");

    if ($flowResponse->successful()) {
        $flow = $flowResponse->json();

        // Check for tools in flow
        if (isset($flow['tools'])) {
            $tools = $flow['tools'];
            echo "Found: " . count($tools) . " tools in conversation flow\n\n";

            foreach ($tools as $tool) {
                $toolName = $tool['name'] ?? 'Unknown';
                $foundTools[] = $toolName;

                $isExpected = in_array($toolName, $expectedTools);
                $icon = $isExpected ? '✅' : '⚠️';

                echo "{$icon} {$toolName}\n";

                if (isset($tool['description'])) {
                    echo "   Description: " . substr($tool['description'], 0, 80) . "...\n";
                }

                if (isset($tool['url'])) {
                    echo "   URL: " . $tool['url'] . "\n";
                }

                if (isset($tool['parameters'])) {
                    $params = $tool['parameters'];
                    if (isset($params['properties'])) {
                        $propCount = count($params['properties']);
                        echo "   Parameters: {$propCount} properties\n";

                        foreach ($params['properties'] as $propName => $propDef) {
                            $required = isset($params['required']) && in_array($propName, $params['required']) ? 'required' : 'optional';
                            $type = $propDef['type'] ?? 'unknown';
                            echo "      - {$propName} ({$type}, {$required})\n";
                        }
                    }
                }

                echo "\n";
            }
        }
    }
}

// Check for missing tools
echo "\n📊 Verification Summary:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$missingTools = array_diff($expectedTools, $foundTools);
$extraTools = array_diff($foundTools, $expectedTools);

if (empty($missingTools)) {
    echo "✅ All expected tools present\n";
} else {
    echo "❌ Missing tools:\n";
    foreach ($missingTools as $tool) {
        echo "   - {$tool}\n";
    }
}

if (!empty($extraTools)) {
    echo "\n⚠️  Extra tools found:\n";
    foreach ($extraTools as $tool) {
        echo "   - {$tool}\n";
    }
}

echo "\n";
echo "Expected: " . count($expectedTools) . " tools\n";
echo "Found: " . count($foundTools) . " tools\n";
echo "Missing: " . count($missingTools) . " tools\n";
echo "Extra: " . count($extraTools) . " tools\n";

$allCorrect = empty($missingTools) && empty($extraTools);

echo "\n";
if ($allCorrect) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ ALL FUNCTIONS CORRECT!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
} else {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ⚠️  FUNCTION DISCREPANCIES FOUND\n";
    echo "═══════════════════════════════════════════════════════════════\n";
}

echo "\n";
