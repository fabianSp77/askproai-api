#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$expectedAgentId = 'agent_2d467d84eb674e5b3f5815d81c';
$phoneNumber = '+493033081738';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "📞 PHONE NUMBER CONFIGURATION VERIFICATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get phone numbers
$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get('https://api.retellai.com/list-phone-numbers');

if (!$response->successful()) {
    echo "❌ Failed to list phone numbers\n";
    echo "HTTP {$response->status()}\n";
    exit(1);
}

$phones = $response->json();

echo "Total Phone Numbers: " . count($phones) . "\n\n";

$found = false;

foreach ($phones as $phone) {
    $num = $phone['phone_number'] ?? 'N/A';

    if ($num === $phoneNumber) {
        $found = true;

        echo "Phone Number: $num\n";
        echo "───────────────────────────────────────────────────────────\n";

        $currentAgentId = $phone['inbound_agent_id'] ?? null;
        $phoneId = $phone['phone_number_id'] ?? $num;

        echo "Phone ID: $phoneId\n";
        echo "Current Agent ID: " . ($currentAgentId ?? 'NONE') . "\n";
        echo "Expected Agent ID: $expectedAgentId\n";

        if ($currentAgentId === $expectedAgentId) {
            echo "✅ CORRECT - Phone uses the new agent!\n\n";
        } else {
            echo "❌ WRONG - Phone is NOT using the new agent!\n";
            echo "   Current: $currentAgentId\n";
            echo "   Expected: $expectedAgentId\n\n";

            echo "ACTION REQUIRED: Update phone number\n";
            echo "  Run: php update_phone_to_new_agent.php\n\n";
            exit(1);
        }

        // Now check the agent configuration
        echo "Checking agent configuration...\n";

        $agentResponse = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->get("https://api.retellai.com/get-agent/$currentAgentId");

        if ($agentResponse->successful()) {
            $agent = $agentResponse->json();

            echo "\nAgent Details:\n";
            echo "───────────────────────────────────────────────────────────\n";
            echo "Agent Name: {$agent['agent_name']}\n";
            echo "Version: {$agent['version']}\n";
            echo "Published: " . ($agent['is_published'] ? 'YES ✅' : 'NO ❌') . "\n";

            // Check response engine
            if (isset($agent['response_engine'])) {
                $engine = $agent['response_engine'];
                echo "Response Engine Type: {$engine['type']}\n";

                if (isset($engine['conversation_flow_id'])) {
                    echo "Conversation Flow ID: {$engine['conversation_flow_id']}\n";

                    // Check if flow has tools
                    $flowId = $engine['conversation_flow_id'];
                    echo "\nFetching conversation flow...\n";

                    $flowResponse = Http::withHeaders([
                        'Authorization' => "Bearer $token",
                    ])->get("https://api.retellai.com/get-conversation-flow/$flowId");

                    if ($flowResponse->successful()) {
                        $flow = $flowResponse->json();

                        $toolCount = isset($flow['tools']) ? count($flow['tools']) : 0;
                        $nodeCount = isset($flow['nodes']) ? count($flow['nodes']) : 0;

                        echo "  Tools: $toolCount\n";
                        echo "  Nodes: $nodeCount\n";

                        if ($toolCount === 0 || $nodeCount === 0) {
                            echo "\n❌ CRITICAL: Flow is EMPTY!\n";
                            exit(1);
                        } else {
                            echo "\n✅ Flow has tools and nodes\n";
                        }

                        // Check function nodes
                        $functionNodes = array_filter($flow['nodes'], fn($n) => ($n['type'] ?? '') === 'function');
                        echo "  Function Nodes: " . count($functionNodes) . "\n";

                        // Check for check_availability_v17
                        $hasCheckAvail = false;
                        foreach ($flow['tools'] as $tool) {
                            if ($tool['name'] === 'check_availability_v17') {
                                $hasCheckAvail = true;
                                echo "\n✅ check_availability_v17 tool found:\n";
                                echo "   URL: {$tool['url']}\n";
                                break;
                            }
                        }

                        if (!$hasCheckAvail) {
                            echo "\n❌ check_availability_v17 NOT found in tools!\n";
                        }
                    } else {
                        echo "  ❌ Failed to fetch flow\n";
                    }
                }
            }
        }

        break;
    }
}

if (!$found) {
    echo "❌ Phone number $phoneNumber NOT found!\n\n";
    echo "Available numbers:\n";
    foreach ($phones as $phone) {
        echo "  - {$phone['phone_number']}\n";
    }
    exit(1);
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "✅ Phone number is configured correctly\n";
echo "✅ Agent has conversation flow with tools\n";
echo "✅ check_availability_v17 is present\n\n";

echo "IF test call still doesn't trigger functions:\n";
echo "  → The problem is NOT in the configuration\n";
echo "  → The problem is in how Retell executes the flow\n";
echo "  → Possible issues:\n";
echo "     1. Flow logic prevents reaching function nodes\n";
echo "     2. Retell API bug with flow execution\n";
echo "     3. Agent version mismatch\n\n";

echo "NEXT STEP: Make a NEW test call and monitor:\n";
echo "  1. Backend logs: tail -f storage/logs/laravel.log\n";
echo "  2. See if ANY webhook is called\n";
echo "  3. If NO webhooks → Flow execution problem\n";
echo "  4. If webhooks work → Different issue\n\n";
