#!/usr/bin/env php
<?php

/**
 * Add Two-Step Booking Tools to Conversation Flow
 *
 * Phase 1.2: Status Updates - Tool-Call Splitting (Option A)
 * Adds start_booking and confirm_booking to conversation_flow_a58405e3f67a
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
echo " Add Two-Step Booking Tools to Conversation Flow\n";
echo " Flow ID: conversation_flow_a58405e3f67a\n";
echo " Date: " . Carbon::now('Europe/Berlin')->format('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

// Configuration
$retellApiKey = config('services.retellai.api_key');
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

if (!$retellApiKey) {
    echo "❌ ERROR: Retell API key not configured\n";
    exit(1);
}

// STEP 1: Fetch current conversation flow
echo "🔍 Step 1: Fetching current conversation flow...\n";

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
$currentTools = $conversationFlow['tools'] ?? [];

echo "✅ Conversation flow fetched\n";
echo "📋 Flow ID: {$conversationFlow['conversation_flow_id']}\n";
echo "📋 Current Version: " . ($conversationFlow['version'] ?? 'unknown') . "\n";
echo "📋 Current Tools Count: " . count($currentTools) . "\n";

if (count($currentTools) > 0) {
    echo "📋 Existing Tools:\n";
    foreach ($currentTools as $tool) {
        echo "   - " . ($tool['name'] ?? 'unnamed') . "\n";
    }
}
echo "\n";

// STEP 2: Check if tools already exist
echo "🔍 Step 2: Checking for existing tools...\n";

$existingToolNames = array_column($currentTools, 'name');
$hasStartBooking = in_array('start_booking', $existingToolNames);
$hasConfirmBooking = in_array('confirm_booking', $existingToolNames);

if ($hasStartBooking && $hasConfirmBooking) {
    echo "✅ Both tools already exist! No update needed.\n";
    exit(0);
}

echo "📊 Status:\n";
echo "   - start_booking: " . ($hasStartBooking ? '✅ Found' : '❌ Missing') . "\n";
echo "   - confirm_booking: " . ($hasConfirmBooking ? '✅ Found' : '❌ Missing') . "\n";
echo "\n";

// STEP 3: Define new tools
echo "📝 Step 3: Preparing new tool definitions...\n";

$newTools = [];

if (!$hasStartBooking) {
    $newTools[] = [
        'tool_id' => 'tool-start-booking',
        'timeout_ms' => 5000,
        'name' => 'start_booking',
        'description' => 'Step 1 of 2-step booking: Validates all booking data and returns immediate status (<500ms). Use this BEFORE confirm_booking to provide instant feedback to user. Returns validation status and caches data.',
        'type' => 'custom',
        'url' => config('app.url') . '/api/webhooks/retell/function',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'function_name' => [
                    'type' => 'string',
                    'description' => 'Function name: start_booking'
                ],
                'call_id' => [
                    'type' => 'string',
                    'description' => 'Unique Retell call identifier for session tracking'
                ],
                'datetime' => [
                    'type' => 'string',
                    'description' => 'Appointment date and time in format: DD.MM.YYYY HH:MM (e.g., 06.11.2025 14:00). IMPORTANT: Always use year 2025.'
                ],
                'service' => [
                    'type' => 'string',
                    'description' => 'Service name (e.g., Herrenhaarschnitt, Damenhaarschnitt, Beratung)'
                ],
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Customer full name'
                ],
                'customer_phone' => [
                    'type' => 'string',
                    'description' => 'Customer phone number (format: +49... or 0...)'
                ],
                'customer_email' => [
                    'type' => 'string',
                    'description' => 'Customer email address (optional)'
                ]
            ],
            'required' => ['function_name', 'call_id', 'datetime', 'service', 'customer_name', 'customer_phone']
        ]
    ];
}

if (!$hasConfirmBooking) {
    $newTools[] = [
        'tool_id' => 'tool-confirm-booking',
        'timeout_ms' => 30000,
        'name' => 'confirm_booking',
        'description' => 'Step 2 of 2-step booking: Executes the actual Cal.com booking and database save. Call this IMMEDIATELY AFTER start_booking returns success. Takes 4-5s but user already heard status message.',
        'type' => 'custom',
        'url' => config('app.url') . '/api/webhooks/retell/function',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'function_name' => [
                    'type' => 'string',
                    'description' => 'Function name: confirm_booking'
                ],
                'call_id' => [
                    'type' => 'string',
                    'description' => 'Same call_id used in start_booking to retrieve cached booking data'
                ]
            ],
            'required' => ['function_name', 'call_id']
        ]
    ];
}

echo "✅ Prepared " . count($newTools) . " new tool definition(s)\n";
echo "\n";

// STEP 4: Add new tools and prepare update payload
echo "🔧 Step 4: Adding new tools to conversation flow...\n";

$updatedTools = array_merge($currentTools, $newTools);

$updatePayload = ['tools' => $updatedTools];

echo "📊 Total tools after update: " . count($updatedTools) . "\n";
echo "\n";

// STEP 5: Update conversation flow via API
echo "🚀 Step 5: Updating conversation flow via API...\n";

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

// STEP 6: Verify tools were added
echo "🔍 Step 6: Verifying tools were added...\n";

$updatedToolNames = array_column($result['tools'] ?? [], 'name');
$verifyStartBooking = in_array('start_booking', $updatedToolNames);
$verifyConfirmBooking = in_array('confirm_booking', $updatedToolNames);

echo "   - start_booking: " . ($verifyStartBooking ? '✅ Added' : '❌ Failed') . "\n";
echo "   - confirm_booking: " . ($verifyConfirmBooking ? '✅ Added' : '❌ Failed') . "\n";
echo "\n";

if ($verifyStartBooking && $verifyConfirmBooking) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ SUCCESS - Two-Step Booking Tools Added\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "🎯 Backend Implementation: ✅ COMPLETE\n";
    echo "   - start_booking() function: RetellFunctionCallHandler.php:1567-1724\n";
    echo "   - confirm_booking() function: RetellFunctionCallHandler.php:1726-1918\n";
    echo "   - Cache-based session storage: Laravel Cache (5min TTL)\n";
    echo "   - SAGA compensation: Integrated into confirm_booking\n";
    echo "\n";
    echo "🎯 Conversation Flow: ✅ TOOLS REGISTERED\n";
    echo "   - Tool count: " . count($result['tools']) . " total\n";
    echo "   - start_booking: Registered\n";
    echo "   - confirm_booking: Registered\n";
    echo "\n";
    echo "🎯 Next Steps:\n";
    echo "1. Test the 2-step booking flow:\n";
    echo "   - Make a test call to the agent\n";
    echo "   - Request to book an appointment\n";
    echo "   - Observe timing: should hear status within 500ms\n";
    echo "   - Total perceived wait: <3s (vs 11-13s before)\n";
    echo "\n";
    echo "2. Implement start_reschedule and confirm_reschedule\n";
    echo "   - Same pattern as booking\n";
    echo "   - Will eliminate silent gaps for reschedules too\n";
    echo "\n";
    echo "3. Optional: Update agent prompt to use 2-step flow\n";
    echo "   - Agent should call start_booking first\n";
    echo "   - Then immediately call confirm_booking\n";
    echo "   - User experience will be much better\n";
    echo "\n";
    echo "📖 Expected User Experience:\n";
    echo "   User: \"Ich möchte einen Termin buchen\"\n";
    echo "   Agent: [Collects: date, time, service, name, phone]\n";
    echo "   Agent: start_booking(...)\n";
    echo "   Agent: \"Einen Moment bitte, ich prüfe die Verfügbarkeit...\" [IMMEDIATE!]\n";
    echo "   Agent: confirm_booking(...) [in background, 4-5s]\n";
    echo "   Agent: \"Perfekt! Ihr Termin ist bestätigt für...\"\n";
    echo "   Perceived wait: ~2-3s ✅ (vs 11-13s ❌ before)\n";
    echo "\n";
} else {
    echo "❌ Verification failed - tools may not have been added correctly\n";
    exit(1);
}
