#!/usr/bin/env php
<?php

/**
 * Update Conversational Agent with service_id parameter
 *
 * Agent ID: agent_616d645570ae613e421edb98e7
 * Problem: collect_appointment_data function is missing service_id parameter
 * Solution: Update function definition to include service_id
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║ Updating Conversational Agent - service_id Fix        ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

$retellApiKey = config('services.retellai.api_key');
$retellBaseUrl = config('services.retellai.base_url');
$agentId = 'agent_616d645570ae613e421edb98e7';

if (!$retellApiKey) {
    echo "❌ ERROR: RETELLAI_API_KEY not configured in .env\n";
    echo "Please add: RETELLAI_API_KEY=your_key_here\n";
    exit(1);
}

echo "Configuration:\n";
echo "  API Base URL: $retellBaseUrl\n";
echo "  Agent ID: $agentId\n";
echo "  API Key: " . substr($retellApiKey, 0, 10) . "...\n\n";

echo "Step 1: Fetching current agent configuration...\n";

try {
    // Get current agent
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $retellApiKey,
        'Content-Type' => 'application/json',
    ])->get("{$retellBaseUrl}/get-agent/{$agentId}");

    if (!$response->successful()) {
        echo "❌ Failed to fetch agent!\n";
        echo "Status: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
        exit(1);
    }

    $agent = $response->json();
    echo "✅ Agent fetched successfully!\n";
    echo "   Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "   Functions: " . count($agent['functions'] ?? []) . "\n\n";

    // Find collect_appointment_data function
    $collectFunc = null;
    $collectIndex = null;
    foreach ($agent['functions'] ?? [] as $idx => $func) {
        if ($func['name'] === 'collect_appointment_data') {
            $collectFunc = $func;
            $collectIndex = $idx;
            break;
        }
    }

    if (!$collectFunc) {
        echo "❌ collect_appointment_data function not found!\n";
        echo "Please configure this function first.\n";
        exit(1);
    }

    echo "Step 2: Checking current parameters...\n";
    $currentParams = array_keys($collectFunc['parameters']['properties'] ?? []);
    echo "Current parameters: " . implode(', ', $currentParams) . "\n";

    if (isset($collectFunc['parameters']['properties']['service_id'])) {
        echo "✅ service_id already exists! No update needed.\n";
        exit(0);
    }

    echo "❌ service_id parameter MISSING!\n\n";

    echo "Step 3: Preparing updated function definition...\n";

    // Add service_id parameter
    $collectFunc['parameters']['properties']['service_id'] = [
        'type' => 'integer',
        'description' => 'Numeric ID des gewählten Services aus list_services (z.B. 32 für 15min, 47 für 30min). WICHTIG: Immer mitgeben!'
    ];

    // Update required fields
    if (!in_array('service_id', $collectFunc['parameters']['required'] ?? [])) {
        $collectFunc['parameters']['required'][] = 'service_id';
    }

    // Update functions array
    $agent['functions'][$collectIndex] = $collectFunc;

    echo "✅ Function definition updated!\n\n";

    echo "Step 4: Updating agent via Retell API...\n";

    // Prepare update payload
    $payload = [
        'agent_name' => $agent['agent_name'],
        'functions' => $agent['functions'],
        // Keep other settings
        'agent_prompt' => $agent['agent_prompt'] ?? '',
        'language' => $agent['language'] ?? 'de',
    ];

    // Update agent
    $updateResponse = Http::withHeaders([
        'Authorization' => 'Bearer ' . $retellApiKey,
        'Content-Type' => 'application/json',
    ])->patch("{$retellBaseUrl}/agent/{$agentId}", $payload);

    if ($updateResponse->successful()) {
        echo "✅ Agent successfully updated!\n\n";

        echo "╔════════════════════════════════════════════════════════╗\n";
        echo "║ ✅ UPDATE COMPLETE                                    ║\n";
        echo "╚════════════════════════════════════════════════════════╝\n\n";

        echo "Changes made:\n";
        echo "  ✅ Added service_id parameter to collect_appointment_data\n";
        echo "  ✅ Marked service_id as required\n\n";

        echo "Next steps:\n";
        echo "1. Test the agent with a new call\n";
        echo "2. Agent should now receive service_id from function calls\n";
        echo "3. Backend will use correct service for availability & booking\n\n";

        echo "Updated collect_appointment_data parameters:\n";
        foreach ($collectFunc['parameters']['properties'] as $name => $def) {
            $type = $def['type'];
            $required = in_array($name, $collectFunc['parameters']['required']) ? '*' : '';
            echo "  - $name ($type) $required\n";
        }
        echo "\n* = required\n";

    } else {
        echo "❌ Failed to update agent!\n";
        echo "Status: " . $updateResponse->status() . "\n";
        echo "Response: " . $updateResponse->body() . "\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
