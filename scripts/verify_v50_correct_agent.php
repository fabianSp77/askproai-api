#!/usr/bin/env php
<?php

/**
 * V50 Deployment Verification - CORRECT Agent
 * Agent: agent_45daa54928c5768b52ba3db736
 * Flow: conversation_flow_a58405e3f67a
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " V50 Final Verification - Correct Agent\n";
echo " Date: " . now('Europe/Berlin')->format('Y-m-d H:i:s T') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = 'https://api.retellai.com';

$allChecks = [];

// Check 1: Correct Agent Configuration
echo "🔍 Check 1: Correct Agent Configuration\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$agentResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$agentResponse->successful()) {
    echo "❌ FAILED: Could not fetch agent configuration\n";
    $allChecks['agent_config'] = false;
} else {
    $agent = $agentResponse->json();
    $agentName = $agent['agent_name'] ?? 'Unknown';
    $responseEngine = $agent['response_engine']['type'] ?? 'unknown';

    $hasV50Name = strpos($agentName, 'V50') !== false;
    $isConversationFlow = $responseEngine === 'conversation-flow';
    $hasCorrectFlow = strpos(json_encode($agent), $conversationFlowId) !== false;

    echo "  Agent ID: {$agentId}\n";
    echo "  Agent Name: {$agentName}\n";
    echo "  Response Engine: {$responseEngine}\n";
    echo "  V50 in Name: " . ($hasV50Name ? "✅" : "❌") . "\n";
    echo "  Is Conversation Flow: " . ($isConversationFlow ? "✅" : "❌") . "\n";
    echo "  Linked to V50 Flow: " . ($hasCorrectFlow ? "✅" : "❌") . "\n";

    $allChecks['agent_config'] = $hasV50Name && $isConversationFlow && $hasCorrectFlow;
}
echo "\n";

// Check 2: Conversation Flow V50 Prompt
echo "🔍 Check 2: Conversation Flow V50 Prompt\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$flowResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

if (!$flowResponse->successful()) {
    echo "❌ FAILED: Could not fetch conversation flow\n";
    $allChecks['conversation_flow'] = false;
} else {
    $flow = $flowResponse->json();
    $prompt = $flow['global_prompt'] ?? '';

    // Check for V50 critical sections
    $checks = [
        'v50_marker' => 'V50 (2025-11-05 CRITICAL',
        'critical_rule' => '🚨 KRITISCHE REGEL: Tool-Call Enforcement',
        'stop_instruction' => '🛑 STOP! Bevor du antwortest',
        'tool_failure' => 'Was tun wenn Tool fehlschlägt',
        'no_invent' => 'NIEMALS eigene Zeiten erfinden',
        'v49_example' => 'V49 FEHLER',
    ];

    $allPromptChecks = true;
    foreach ($checks as $key => $pattern) {
        $found = strpos($prompt, $pattern) !== false;
        echo "  " . ucwords(str_replace('_', ' ', $key)) . ": " . ($found ? "✅" : "❌") . "\n";
        if (!$found) {
            $allPromptChecks = false;
        }
    }

    echo "  Prompt Length: " . strlen($prompt) . " characters\n";

    $allChecks['conversation_flow'] = $allPromptChecks;
}
echo "\n";

// Check 3: Backend Function Support
echo "🔍 Check 3: Backend Function Support\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$handlerPath = __DIR__ . '/../app/Http/Controllers/RetellFunctionCallHandler.php';
$handlerContent = file_get_contents($handlerPath);

$requiredFunctions = [
    'get_available_services' => 'Service Listing',
    'check_availability' => 'Availability Check',
    'book_appointment' => 'Appointment Booking',
];

$allFunctionsPresent = true;
foreach ($requiredFunctions as $func => $label) {
    $found = strpos($handlerContent, "'{$func}'") !== false;
    echo "  {$label} ({$func}): " . ($found ? "✅" : "❌") . "\n";
    if (!$found) {
        $allFunctionsPresent = false;
    }
}

$allChecks['backend_functions'] = $allFunctionsPresent;
echo "\n";

// Final Summary
echo "═══════════════════════════════════════════════════════════════\n";
echo " Final Verification Summary\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$allPassed = true;
foreach ($allChecks as $checkName => $result) {
    $label = ucwords(str_replace('_', ' ', $checkName));
    echo "  {$label}: " . ($result ? "✅" : "❌") . "\n";
    if (!$result) {
        $allPassed = false;
    }
}

echo "\n";

if ($allPassed) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ V50 FULLY DEPLOYED - CORRECT AGENT VERIFIED!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "🎯 Status: V50 is LIVE on the CORRECT Friseur 1 Agent\n";
    echo "\n";
    echo "📋 Configuration:\n";
    echo "  Agent ID: {$agentId}\n";
    echo "  Agent Name: Friseur 1 Agent V50 - CRITICAL Tool Enforcement\n";
    echo "  Conversation Flow: {$conversationFlowId}\n";
    echo "  Response Engine: conversation-flow\n";
    echo "\n";
    echo "✅ V50 Features Active:\n";
    echo "  - 🚨 Mandatory tool call enforcement\n";
    echo "  - 🛑 STOP instruction before responding\n";
    echo "  - 🚫 NO invented times rule\n";
    echo "  - 🔧 Tool failure fallback behavior\n";
    echo "  - 📝 V49 error examples in prompt\n";
    echo "\n";
    echo "🚀 Ready for Testing:\n";
    echo "  1. Call the Friseur 1 phone number\n";
    echo "  2. Test scenario: 'Ich möchte morgen Vormittag einen Balayage Termin'\n";
    echo "  3. Verify agent calls check_availability before responding\n";
    echo "  4. Confirm no invented times or contradictions\n";
    echo "\n";
    echo "📊 Monitor Test:\n";
    echo "  php scripts/get_call_details.php [call_id]\n";
    echo "\n";
    exit(0);
} else {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ❌ VERIFICATION FAILED - ISSUES FOUND!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "⚠️ Please review failed checks above.\n";
    echo "\n";
    exit(1);
}
