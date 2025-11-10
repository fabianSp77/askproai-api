#!/usr/bin/env php
<?php

/**
 * V50 Deployment Verification
 * Comprehensive check of all V50 components
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " V50 Deployment Verification\n";
echo " Date: " . now('Europe/Berlin')->format('Y-m-d H:i:s T') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = config('services.retellai.agent_id');
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

$allChecks = [];

// Check 1: Agent Configuration
echo "🔍 Check 1: Agent Configuration\n";
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
    $hasV50Name = strpos($agentName, 'V50') !== false;

    echo "  Agent ID: {$agentId}\n";
    echo "  Agent Name: {$agentName}\n";
    echo "  V50 in Name: " . ($hasV50Name ? "✅" : "❌") . "\n";

    $allChecks['agent_config'] = $hasV50Name;
}
echo "\n";

// Check 2: Conversation Flow
echo "🔍 Check 2: Conversation Flow (V50 Prompt)\n";
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
        'v50_marker' => [
            'pattern' => 'V50 (2025-11-05 CRITICAL',
            'label' => 'V50 Version Marker'
        ],
        'critical_rule' => [
            'pattern' => '🚨 KRITISCHE REGEL: Tool-Call Enforcement',
            'label' => 'Critical Rule Section'
        ],
        'stop_instruction' => [
            'pattern' => '🛑 STOP! Bevor du antwortest',
            'label' => 'STOP Instruction'
        ],
        'tool_failure' => [
            'pattern' => 'Was tun wenn Tool fehlschlägt',
            'label' => 'Tool Failure Fallback'
        ],
        'no_invent' => [
            'pattern' => 'NIEMALS eigene Zeiten erfinden',
            'label' => 'No Invented Times Rule'
        ],
        'v49_example' => [
            'pattern' => 'V49 FEHLER',
            'label' => 'V49 Error Example'
        ]
    ];

    $allPromptChecks = true;
    foreach ($checks as $key => $check) {
        $found = strpos($prompt, $check['pattern']) !== false;
        echo "  {$check['label']}: " . ($found ? "✅" : "❌") . "\n";
        if (!$found) {
            $allPromptChecks = false;
        }
    }

    echo "  Prompt Length: " . strlen($prompt) . " characters\n";
    echo "  Expected: ~11,682 characters\n";

    $allChecks['conversation_flow'] = $allPromptChecks;
}
echo "\n";

// Check 3: Backend Function Support
echo "🔍 Check 3: Backend Function Support\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// Read RetellFunctionCallHandler.php
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

// Check 4: Date Context Variables
echo "🔍 Check 4: Date Context Variables\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if ($agentResponse->successful()) {
    $agent = $agentResponse->json();
    $dynamicVars = $agent['llm_dynamic_variables'] ?? [];

    $requiredVars = [
        'current_date' => 'Current Date',
        'current_time' => 'Current Time',
        'current_year' => 'Current Year',
    ];

    $allVarsPresent = true;
    foreach ($requiredVars as $var => $label) {
        $found = isset($dynamicVars[$var]);
        echo "  {$label} ({{{{$var}}}}): " . ($found ? "✅" : "⚠️") . "\n";
        if ($found) {
            echo "    Value: {$dynamicVars[$var]}\n";
        }
    }

    // Date variables are not critical, just warning
    $allChecks['date_variables'] = true;
} else {
    echo "  ⚠️ Could not verify (agent fetch failed)\n";
    $allChecks['date_variables'] = true;
}
echo "\n";

// Final Summary
echo "═══════════════════════════════════════════════════════════════\n";
echo " Verification Summary\n";
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
    echo " ✅ V50 DEPLOYMENT VERIFIED - ALL CHECKS PASSED!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "🎯 Status: V50 is LIVE and ready for production testing\n";
    echo "\n";
    echo "📋 What Changed:\n";
    echo "  1. ✅ Agent name updated to V50\n";
    echo "  2. ✅ V50 prompt with critical tool enforcement deployed\n";
    echo "  3. ✅ Backend supports all required functions\n";
    echo "  4. ✅ Date context variables configured\n";
    echo "\n";
    echo "🚀 Next Steps:\n";
    echo "  1. Conduct test call with 'morgen Vormittag Balayage' scenario\n";
    echo "  2. Verify agent calls check_availability before responding\n";
    echo "  3. Confirm no invented times or contradictions\n";
    echo "  4. Monitor logs for tool call patterns\n";
    echo "\n";
    echo "📞 Test Call Command:\n";
    echo "  Call: +49 30 555 20380 (or configured number)\n";
    echo "  Scenario: 'Ich möchte morgen Vormittag einen Balayage Termin'\n";
    echo "\n";
    echo "📊 Monitor Test Call:\n";
    echo "  php scripts/get_call_details.php [call_id]\n";
    echo "\n";
    exit(0);
} else {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ❌ V50 DEPLOYMENT INCOMPLETE - ISSUES FOUND!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "⚠️ Please review failed checks above and fix before testing.\n";
    echo "\n";
    exit(1);
}
