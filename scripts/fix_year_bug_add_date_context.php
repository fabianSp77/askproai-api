#!/usr/bin/env php
<?php

/**
 * Fix Year Bug: Add Date Context Variables to Conversation Flow
 *
 * ISSUE: Agent using "2023" instead of "2025" for all bookings
 * ROOT CAUSE: Missing date context variables (current_year, current_date)
 * FIX: Add global date context to conversation flow
 *
 * Test Call Evidence:
 * - call_e9c30b72096503fda911be8ffa3
 * - check_availability_v17: "datum": "10.11.2023" ❌
 * - book_appointment_v17: "datum": "10.11.2023" ❌
 *
 * CREATED: 2025-11-05
 * PRIORITY: P0-CRITICAL
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Fix Year Bug: Add Date Context to Conversation Flow\n";
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

// Get current Berlin time
$now = Carbon::now('Europe/Berlin');

echo "📅 Current Date/Time Context:\n";
echo "   - Date: {$now->format('Y-m-d')}\n";
echo "   - Year: {$now->format('Y')}\n";
echo "   - Month: {$now->format('m')}\n";
echo "   - Day: {$now->format('d')}\n";
echo "   - Weekday: {$now->locale('de')->dayName}\n";
echo "   - Time: {$now->format('H:i:s')}\n";
echo "\n";

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

echo "✅ Conversation flow fetched\n";
echo "📋 Flow ID: {$conversationFlow['conversation_flow_id']}\n";
echo "📋 Current Version: " . ($conversationFlow['version'] ?? 'unknown') . "\n";
echo "\n";

// STEP 2: Prepare date context variables
echo "📝 Step 2: Preparing date context variables...\n";

$dateContext = [
    'current_year' => $now->format('Y'),
    'current_month' => $now->format('m'),
    'current_day' => $now->format('d'),
    'current_date' => $now->format('Y-m-d'),
    'current_date_german' => $now->format('d.m.Y'),
    'current_weekday' => $now->locale('de')->dayName,
    'current_weekday_en' => $now->format('l'),
    'current_time' => $now->format('H:i:s'),
    'today' => $now->format('Y-m-d'),
    'tomorrow' => $now->copy()->addDay()->format('Y-m-d'),
    'day_after_tomorrow' => $now->copy()->addDays(2)->format('Y-m-d'),
];

echo "📊 Date Context Variables:\n";
foreach ($dateContext as $key => $value) {
    echo "   - {$key}: {$value}\n";
}
echo "\n";

// STEP 3: Check if conversation flow has global_state
$currentGlobalState = $conversationFlow['global_state'] ?? [];

echo "🔍 Step 3: Checking current global state...\n";
if (empty($currentGlobalState)) {
    echo "⚠️  No global_state found - will create new\n";
} else {
    echo "📋 Current global_state keys: " . implode(', ', array_keys($currentGlobalState)) . "\n";
}
echo "\n";

// STEP 4: Merge date context with existing global state
echo "🔧 Step 4: Merging date context into global state...\n";

$updatedGlobalState = array_merge($currentGlobalState, $dateContext);

echo "✅ Merged global state will have " . count($updatedGlobalState) . " variables\n";
echo "\n";

// STEP 5: Update conversation flow
echo "🚀 Step 5: Updating conversation flow via API...\n";

$updatePayload = [
    'global_state' => $updatedGlobalState
];

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
echo "\n";

// STEP 6: Verify date context was added
echo "🔍 Step 6: Verifying date context was added...\n";

$updatedGlobalState = $result['global_state'] ?? [];
$hasCurrentYear = isset($updatedGlobalState['current_year']);
$hasCurrentDate = isset($updatedGlobalState['current_date']);

echo "   - current_year: " . ($hasCurrentYear ? "✅ {$updatedGlobalState['current_year']}" : "❌ Missing") . "\n";
echo "   - current_date: " . ($hasCurrentDate ? "✅ {$updatedGlobalState['current_date']}" : "❌ Missing") . "\n";
echo "\n";

if ($hasCurrentYear && $hasCurrentDate) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ SUCCESS - Year Bug Fixed\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "🎯 Date Context Added:\n";
    foreach ($dateContext as $key => $value) {
        echo "   ✅ {$key}: {$value}\n";
    }
    echo "\n";
    echo "🎯 How to Use in Prompts:\n";
    echo "   - Reference: {{global_state.current_year}}\n";
    echo "   - Example: \"Today is {{global_state.current_date}}\"\n";
    echo "   - Example: \"Use year {{global_state.current_year}} for bookings\"\n";
    echo "\n";
    echo "🎯 Next Steps:\n";
    echo "1. Update conversation flow nodes to use date context\n";
    echo "2. Test with new call to verify year is now 2025\n";
    echo "3. Investigate database save failure (P0-2)\n";
    echo "\n";
    echo "📖 Expected Behavior:\n";
    echo "   User: \"Termin am Freitag um 17 Uhr\"\n";
    echo "   Agent: Will now use 2025 instead of 2023 ✅\n";
    echo "\n";
} else {
    echo "❌ Verification failed - date context may not have been added correctly\n";
    exit(1);
}
