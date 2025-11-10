#!/usr/bin/env php
<?php

/**
 * Add Two-Step Booking Tools to Retell Conversation Flow
 *
 * Phase 1.2: Status Updates - Tool-Call Splitting (Option A)
 * Adds start_booking and confirm_booking functions to conversation flow
 *
 * PURPOSE: Eliminate 11-13s silent gap by providing immediate status feedback
 * CREATED: 2025-11-05
 * ISSUE: P0-2 (User experience - silent gaps during booking)
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Add Two-Step Booking Tools to Retell Conversation Flow\n";
echo " Date: " . Carbon::now('Europe/Berlin')->format('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Configuration
$retellApiKey = config('services.retellai.api_key');
$agentId = config('services.retellai.agent_id');
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

if (!$retellApiKey || !$agentId) {
    echo "❌ ERROR: Retell API key or Agent ID not configured\n";
    exit(1);
}

echo "📋 Configuration:\n";
echo "   Agent ID: {$agentId}\n";
echo "   Base URL: {$baseUrl}\n";
echo "\n";

// STEP 1: Get agent to find conversation flow ID
echo "🔍 Step 1: Fetching agent configuration...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch agent (HTTP {$response->status()})\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}

$agentConfig = $response->json();
$conversationFlowId = $agentConfig['conversation_flow_id'] ?? null;

if (!$conversationFlowId) {
    echo "❌ ERROR: Agent does not have a conversation flow assigned\n";
    exit(1);
}

echo "✅ Agent fetched successfully\n";
echo "📋 Conversation Flow ID: {$conversationFlowId}\n";
echo "\n";

// STEP 2: Fetch current conversation flow
echo "🔍 Step 2: Fetching current conversation flow...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch conversation flow (HTTP {$response->status()})\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}

$conversationFlow = $response->json();
echo "✅ Conversation flow fetched\n";
echo "📋 Current Tools Count: " . count($conversationFlow['tools']) . "\n";
echo "\n";

// STEP 3: Check if tools already exist
echo "🔍 Step 3: Checking for existing tools...\n";

$existingToolIds = array_column($conversationFlow['tools'], 'tool_id');
$hasStartBooking = in_array('tool-start-booking', $existingToolIds);
$hasConfirmBooking = in_array('tool-confirm-booking', $existingToolIds);

if ($hasStartBooking && $hasConfirmBooking) {
    echo "✅ Both tools already exist! No update needed.\n";
    echo "   - tool-start-booking: Found\n";
    echo "   - tool-confirm-booking: Found\n";
    exit(0);
}

echo "📊 Status:\n";
echo "   - tool-start-booking: " . ($hasStartBooking ? '✅ Found' : '❌ Missing') . "\n";
echo "   - tool-confirm-booking: " . ($hasConfirmBooking ? '✅ Found' : '❌ Missing') . "\n";
echo "\n";

// STEP 4: Define new tools
echo "📝 Step 4: Preparing new tool definitions...\n";

$newTools = [];

if (!$hasStartBooking) {
    $newTools[] = [
        'tool_id' => 'tool-start-booking',
        'name' => 'start_booking',
        'description' => 'Validates booking data and returns immediate status (Step 1 of 2-step booking). Use this BEFORE confirm_booking to provide user with instant feedback while checking availability.',
        'url' => config('app.url') . '/api/retell/function-call',
        'method' => 'POST',
        'headers' => new \stdClass(),
        'query_params' => new \stdClass(),
        'speak_after_execution' => true,
        'speak_during_execution' => true,
        'speak_during_execution_prompt' => 'Ich prüfe jetzt die Verfügbarkeit...',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'function_name' => [
                    'type' => 'string',
                    'description' => 'Function name: start_booking',
                    'const' => 'start_booking'
                ],
                'call_id' => [
                    'type' => 'string',
                    'description' => 'Unique Retell call identifier for session tracking'
                ],
                'datetime' => [
                    'type' => 'string',
                    'description' => 'Appointment date and time in format: DD.MM.YYYY HH:MM (e.g., 06.11.2025 14:00)'
                ],
                'service' => [
                    'type' => 'string',
                    'description' => 'Service name (e.g., Herrenhaarschnitt, Damenhaarschnitt)'
                ],
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Customer full name'
                ],
                'customer_phone' => [
                    'type' => 'string',
                    'description' => 'Customer phone number (format: +49...)'
                ],
                'customer_email' => [
                    'type' => 'string',
                    'description' => 'Customer email address (optional)'
                ]
            ],
            'required' => ['function_name', 'call_id', 'datetime', 'service', 'customer_name', 'customer_phone']
        ],
        'response_variables' => new \stdClass(),
        'user_dtmf_options' => new \stdClass()
    ];
}

if (!$hasConfirmBooking) {
    $newTools[] = [
        'tool_id' => 'tool-confirm-booking',
        'name' => 'confirm_booking',
        'description' => 'Executes the actual booking (Step 2 of 2-step booking). Call this AFTER start_booking returns success status. This performs the Cal.com booking and database save.',
        'url' => config('app.url') . '/api/retell/function-call',
        'method' => 'POST',
        'headers' => new \stdClass(),
        'query_params' => new \stdClass(),
        'speak_after_execution' => true,
        'speak_during_execution' => false,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'function_name' => [
                    'type' => 'string',
                    'description' => 'Function name: confirm_booking',
                    'const' => 'confirm_booking'
                ],
                'call_id' => [
                    'type' => 'string',
                    'description' => 'Same call_id used in start_booking to retrieve cached data'
                ]
            ],
            'required' => ['function_name', 'call_id']
        ],
        'response_variables' => new \stdClass(),
        'user_dtmf_options' => new \stdClass()
    ];
}

echo "✅ Prepared " . count($newTools) . " new tool definition(s)\n";
echo "\n";

// STEP 5: Add new tools to conversation flow
echo "🔧 Step 5: Adding new tools to conversation flow...\n";

$updatedTools = array_merge($conversationFlow['tools'], $newTools);

// Convert empty arrays to objects for API compatibility
foreach ($updatedTools as &$tool) {
    if (isset($tool['headers']) && is_array($tool['headers']) && empty($tool['headers'])) {
        $tool['headers'] = new \stdClass();
    }
    if (isset($tool['query_params']) && is_array($tool['query_params']) && empty($tool['query_params'])) {
        $tool['query_params'] = new \stdClass();
    }
    if (isset($tool['response_variables']) && is_array($tool['response_variables']) && empty($tool['response_variables'])) {
        $tool['response_variables'] = new \stdClass();
    }
    if (isset($tool['user_dtmf_options']) && is_array($tool['user_dtmf_options']) && empty($tool['user_dtmf_options'])) {
        $tool['user_dtmf_options'] = new \stdClass();
    }
}

$updatePayload = ['tools' => $updatedTools];

echo "📊 Total tools after update: " . count($updatedTools) . "\n";
echo "\n";

// STEP 6: Update conversation flow via API
echo "🚀 Step 6: Updating conversation flow via API...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", $updatePayload);

if (!$response->successful()) {
    echo "❌ ERROR: Failed to update conversation flow (HTTP {$response->status()})\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}

$result = $response->json();
echo "✅ SUCCESS! Conversation flow updated\n";
echo "📋 New Version: " . ($result['version'] ?? 'unknown') . "\n";
echo "📋 Total Tools: " . count($result['tools']) . "\n";
echo "\n";

// STEP 7: Verify tools were added
echo "🔍 Step 7: Verifying tools were added...\n";

$updatedToolIds = array_column($result['tools'], 'tool_id');
$verifyStartBooking = in_array('tool-start-booking', $updatedToolIds);
$verifyConfirmBooking = in_array('tool-confirm-booking', $updatedToolIds);

echo "   - tool-start-booking: " . ($verifyStartBooking ? '✅ Added' : '❌ Failed') . "\n";
echo "   - tool-confirm-booking: " . ($verifyConfirmBooking ? '✅ Added' : '❌ Failed') . "\n";
echo "\n";

if ($verifyStartBooking && $verifyConfirmBooking) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ SUCCESS - Two-Step Booking Tools Added\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "🎯 Next Steps:\n";
    echo "1. Update agent prompt to use 2-step booking sequence\n";
    echo "2. Test with real call: booking should take <3s perceived wait\n";
    echo "3. Monitor logs for cache operations\n";
    echo "4. Implement start_reschedule and confirm_reschedule (same pattern)\n";
    echo "\n";
    echo "📖 Expected Flow:\n";
    echo "   User: \"Ich möchte einen Termin buchen\"\n";
    echo "   Agent: start_booking(...) → <500ms response\n";
    echo "   Agent: \"Ich prüfe jetzt die Verfügbarkeit...\" [user hears this!]\n";
    echo "   Agent: confirm_booking(...) → 4-5s actual booking\n";
    echo "   Agent: \"Perfekt! Ihr Termin ist bestätigt.\"\n";
    echo "   Total perceived wait: ~2-3s (vs 11-13s before)\n";
    echo "\n";
} else {
    echo "❌ Verification failed - tools may not have been added correctly\n";
    exit(1);
}
