#!/usr/bin/env php
<?php

/**
 * Add Two-Step Booking Functions to Retell LLM Configuration
 *
 * Phase 1.2: Status Updates - Tool-Call Splitting (Option A)
 * Adds start_booking and confirm_booking functions to LLM tools
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
echo " Add Two-Step Booking Functions to Retell LLM\n";
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

// STEP 1: Get agent to find LLM ID
echo "🔍 Step 1: Fetching agent configuration...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch agent (HTTP {$response->status()})\n";
    exit(1);
}

$agentConfig = $response->json();
$llmId = $agentConfig['response_engine']['llm_id'] ?? null;

if (!$llmId) {
    echo "❌ ERROR: Agent does not have an LLM configured\n";
    exit(1);
}

echo "✅ Agent fetched successfully\n";
echo "📋 LLM ID: {$llmId}\n";
echo "📋 LLM Version: " . ($agentConfig['response_engine']['version'] ?? 'unknown') . "\n";
echo "\n";

// STEP 2: Fetch current LLM configuration
echo "🔍 Step 2: Fetching current LLM configuration...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-retell-llm/{$llmId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch LLM (HTTP {$response->status()})\n";
    exit(1);
}

$llmConfig = $response->json();
$currentTools = $llmConfig['tools'] ?? [];

echo "✅ LLM configuration fetched\n";
echo "📋 Current Tools Count: " . count($currentTools) . "\n";

if (count($currentTools) > 0) {
    echo "📋 Existing Tools:\n";
    foreach ($currentTools as $tool) {
        echo "   - {$tool['name']}\n";
    }
}
echo "\n";

// STEP 3: Check if tools already exist
echo "🔍 Step 3: Checking for existing tools...\n";

$existingToolNames = array_column($currentTools, 'name');
$hasStartBooking = in_array('start_booking', $existingToolNames);
$hasConfirmBooking = in_array('confirm_booking', $existingToolNames);

if ($hasStartBooking && $hasConfirmBooking) {
    echo "✅ Both tools already exist! No update needed.\n";
    echo "   - start_booking: Found\n";
    echo "   - confirm_booking: Found\n";
    exit(0);
}

echo "📊 Status:\n";
echo "   - start_booking: " . ($hasStartBooking ? '✅ Found' : '❌ Missing') . "\n";
echo "   - confirm_booking: " . ($hasConfirmBooking ? '✅ Found' : '❌ Missing') . "\n";
echo "\n";

// STEP 4: Define new tools
echo "📝 Step 4: Preparing new tool definitions...\n";

$newTools = [];

if (!$hasStartBooking) {
    $newTools[] = [
        'name' => 'start_booking',
        'description' => 'Validates booking data and returns immediate status (Step 1 of 2-step booking). Use this BEFORE confirm_booking to provide user with instant feedback while checking availability. Returns status within 500ms.',
        'url' => config('app.url') . '/api/retell/function-call',
        'speak_after_execution' => true,
        'speak_during_execution' => true,
        'speak_during_execution_prompt' => 'Einen Moment bitte, ich prüfe die Verfügbarkeit...',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'function_name' => [
                    'type' => 'string',
                    'description' => 'Function name: start_booking',
                    'enum' => ['start_booking']
                ],
                'call_id' => [
                    'type' => 'string',
                    'description' => 'Unique Retell call identifier for session tracking'
                ],
                'datetime' => [
                    'type' => 'string',
                    'description' => 'Appointment date and time in format: DD.MM.YYYY HH:MM (e.g., 06.11.2025 14:00). IMPORTANT: Always use year 2025 for current bookings.'
                ],
                'service' => [
                    'type' => 'string',
                    'description' => 'Service name (e.g., Herrenhaarschnitt, Damenhaarschnitt, Beratung)'
                ],
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Customer full name (first and last name)'
                ],
                'customer_phone' => [
                    'type' => 'string',
                    'description' => 'Customer phone number (format: +49... or 0...)'
                ],
                'customer_email' => [
                    'type' => 'string',
                    'description' => 'Customer email address (optional, use null if not provided)'
                ]
            ],
            'required' => ['function_name', 'call_id', 'datetime', 'service', 'customer_name', 'customer_phone']
        ]
    ];
}

if (!$hasConfirmBooking) {
    $newTools[] = [
        'name' => 'confirm_booking',
        'description' => 'Executes the actual booking (Step 2 of 2-step booking). Call this IMMEDIATELY AFTER start_booking returns success status. This performs the Cal.com booking and database save. Takes 4-5 seconds but user already heard status update.',
        'url' => config('app.url') . '/api/retell/function-call',
        'speak_after_execution' => true,
        'speak_during_execution' => false,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'function_name' => [
                    'type' => 'string',
                    'description' => 'Function name: confirm_booking',
                    'enum' => ['confirm_booking']
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

// STEP 5: Add new tools to LLM configuration
echo "🔧 Step 5: Adding new tools to LLM configuration...\n";

$updatedTools = array_merge($currentTools, $newTools);

$updatePayload = ['tools' => $updatedTools];

echo "📊 Total tools after update: " . count($updatedTools) . "\n";
echo "\n";

// STEP 6: Update LLM via API
echo "🚀 Step 6: Updating LLM configuration via API...\n";
echo "⚠️  This will create a NEW version of the LLM\n";
echo "\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-retell-llm/{$llmId}", $updatePayload);

if (!$response->successful()) {
    echo "❌ ERROR: Failed to update LLM (HTTP {$response->status()})\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}

$result = $response->json();
echo "✅ SUCCESS! LLM updated\n";
echo "📋 LLM ID: " . ($result['llm_id'] ?? $llmId) . "\n";
echo "📋 Total Tools: " . count($result['tools'] ?? []) . "\n";
echo "\n";

// STEP 7: Verify tools were added
echo "🔍 Step 7: Verifying tools were added...\n";

$updatedToolNames = array_column($result['tools'] ?? [], 'name');
$verifyStartBooking = in_array('start_booking', $updatedToolNames);
$verifyConfirmBooking = in_array('confirm_booking', $updatedToolNames);

echo "   - start_booking: " . ($verifyStartBooking ? '✅ Added' : '❌ Failed') . "\n";
echo "   - confirm_booking: " . ($verifyConfirmBooking ? '✅ Added' : '❌ Failed') . "\n";
echo "\n";

if ($verifyStartBooking && $verifyConfirmBooking) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ SUCCESS - Two-Step Booking Functions Added to LLM\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "🎯 Next Steps:\n";
    echo "1. Update agent to use new LLM version (version will auto-increment)\n";
    echo "2. Update agent prompt to use 2-step booking sequence:\n";
    echo "   - Call start_booking() first (validates, returns <500ms)\n";
    echo "   - User hears status message immediately\n";
    echo "   - Call confirm_booking() second (books, takes 4-5s)\n";
    echo "3. Test with real call: booking should take <3s perceived wait\n";
    echo "4. Implement start_reschedule and confirm_reschedule (same pattern)\n";
    echo "\n";
    echo "📖 Expected Flow:\n";
    echo "   User: \"Ich möchte einen Termin buchen\"\n";
    echo "   Agent: Collects booking info (name, phone, datetime, service)\n";
    echo "   Agent: start_booking(...) → <500ms response\n";
    echo "   Agent: \"Einen Moment bitte, ich prüfe die Verfügbarkeit...\" [IMMEDIATE]\n";
    echo "   Agent: confirm_booking(...) → 4-5s actual booking\n";
    echo "   Agent: \"Perfekt! Ihr Termin ist bestätigt für...\"\n";
    echo "   Total perceived wait: ~2-3s (vs 11-13s before)\n";
    echo "\n";
    echo "⚠️  IMPORTANT: The LLM version may have changed. You may need to\n";
    echo "    publish a new agent version to use the updated LLM.\n";
    echo "\n";
} else {
    echo "❌ Verification failed - tools may not have been added correctly\n";
    exit(1);
}
