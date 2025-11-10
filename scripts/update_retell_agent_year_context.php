#!/usr/bin/env php
<?php

/**
 * Update Retell Agent Dynamic Variables - Add Current Year Context
 *
 * FIX 2025-11-04: Add current_year and current_date to agent dynamic variables
 * to prevent agent from using wrong year (2023 instead of 2025)
 *
 * Bug: Agent was sending dates like "05.11.2023" instead of "05.11.2025"
 * Fix: Add dynamic variables with current date/year information
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo " Retell Agent Update - Year Context Fix\n";
echo " Date: " . now()->format('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

// Configuration
$retellApiKey = config('services.retellai.api_key');
$agentId = config('services.retellai.agent_id');

if (!$retellApiKey || !$agentId) {
    echo "❌ ERROR: Retell API key or Agent ID not configured\n";
    echo "   Check: config/services.php → 'retellai.api_key' and 'retellai.agent_id'\n";
    exit(1);
}

echo "📋 Configuration:\n";
echo "   Agent ID: {$agentId}\n";
echo "   API Key: " . substr($retellApiKey, 0, 20) . "...\n";
echo "\n";

// Get current agent configuration
echo "🔍 Step 1: Fetching current agent configuration...\n";

$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch agent configuration\n";
    echo "   Status: {$response->status()}\n";
    echo "   Response: {$response->body()}\n";
    exit(1);
}

$currentConfig = $response->json();
echo "✅ Current configuration fetched\n";
echo "\n";

// Display current dynamic variables
echo "📊 Current Dynamic Variables:\n";
$currentVariables = $currentConfig['llm_dynamic_variables'] ?? [];
if (empty($currentVariables)) {
    echo "   (none configured)\n";
} else {
    foreach ($currentVariables as $key => $value) {
        echo "   - {$key}: {$value}\n";
    }
}
echo "\n";

// Prepare new dynamic variables with year context
$now = Carbon::now('Europe/Berlin');
$newVariables = array_merge($currentVariables, [
    'current_year' => (string) $now->year,
    'current_date' => $now->format('Y-m-d'),
    'current_month' => (string) $now->month,
    'current_month_name' => $now->format('F'),
    'current_day' => (string) $now->day,
    'current_weekday' => $now->format('l'),
    'current_weekday_german' => $now->translatedFormat('l'),
    'timezone' => 'Europe/Berlin'
]);

echo "📝 New Dynamic Variables:\n";
foreach ($newVariables as $key => $value) {
    $isNew = !isset($currentVariables[$key]);
    $prefix = $isNew ? '  🆕' : '  ✏️';
    echo "{$prefix} {$key}: {$value}\n";
}
echo "\n";

// Prompt user for confirmation
echo "⚠️  This will UPDATE the Retell Agent configuration.\n";
echo "   Do you want to proceed? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes' && strtolower($line) !== 'y') {
    echo "\n❌ Aborted by user\n";
    exit(0);
}

// Update agent configuration
echo "\n🚀 Step 2: Updating agent configuration...\n";

$updatePayload = [
    'llm_dynamic_variables' => $newVariables
];

$updateResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-agent/{$agentId}", $updatePayload);

if (!$updateResponse->successful()) {
    echo "❌ ERROR: Failed to update agent configuration\n";
    echo "   Status: {$updateResponse->status()}\n";
    echo "   Response: {$updateResponse->body()}\n";
    exit(1);
}

$updatedConfig = $updateResponse->json();
echo "✅ Agent configuration updated successfully\n";
echo "\n";

// Verify update
echo "🔍 Step 3: Verifying update...\n";

$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$verifyResponse->successful()) {
    echo "⚠️  WARNING: Could not verify update\n";
    exit(0);
}

$verifiedConfig = $verifyResponse->json();
$verifiedVariables = $verifiedConfig['llm_dynamic_variables'] ?? [];

echo "📊 Verified Dynamic Variables:\n";
foreach ($verifiedVariables as $key => $value) {
    echo "   - {$key}: {$value}\n";
}
echo "\n";

// Check if all new variables are present
$allPresent = true;
foreach (['current_year', 'current_date', 'current_month', 'current_weekday_german'] as $requiredKey) {
    if (!isset($verifiedVariables[$requiredKey])) {
        echo "❌ ERROR: Required variable '{$requiredKey}' not found in updated config\n";
        $allPresent = false;
    }
}

if (!$allPresent) {
    echo "\n⚠️  WARNING: Not all variables were set correctly!\n";
    exit(1);
}

// Success
echo "═══════════════════════════════════════════════════════════\n";
echo " ✅ SUCCESS: Agent updated with year context\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";
echo "📋 Summary:\n";
echo "   Agent ID: {$agentId}\n";
echo "   Current Year: {$newVariables['current_year']}\n";
echo "   Current Date: {$newVariables['current_date']}\n";
echo "   Weekday (German): {$newVariables['current_weekday_german']}\n";
echo "   Timezone: {$newVariables['timezone']}\n";
echo "\n";
echo "🎯 Next Steps:\n";
echo "   1. Perform test call to verify year is correct\n";
echo "   2. Check logs for 'YEAR CORRECTION' messages\n";
echo "   3. Verify agent sends dates with year 2025 (not 2023)\n";
echo "\n";
echo "📝 Log Monitoring:\n";
echo "   tail -f storage/logs/laravel.log | grep -E '(YEAR CORRECTION|book_appointment_v17)'\n";
echo "\n";

exit(0);
