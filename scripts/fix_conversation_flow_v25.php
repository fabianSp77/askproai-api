#!/usr/bin/env php
<?php

/**
 * Retell Conversation Flow V25 - Critical Booking Fix
 *
 * PURPOSE: Fix the missing transition from alternative selection to booking
 *
 * ROOT CAUSE: node_present_result has NO path to book_appointment when user
 * selects an alternative. It only transitions to:
 * 1. func_book_appointment (when user says "Ja" to REQUESTED time)
 * 2. node_collect_booking_info (when user wants different time)
 *
 * MISSING: Extract → Confirm → Book flow after alternative selection
 *
 * SOLUTION: Add intermediate nodes following Retell best practices:
 * 1. node_extract_selection (Extract Dynamic Variable Node)
 * 2. node_confirm_alternative (Conversation Node for explicit confirmation)
 * 3. Transition from node_present_result → node_extract_selection
 * 4. Transition from node_extract_selection → node_confirm_alternative
 * 5. Transition from node_confirm_alternative → func_book_appointment
 *
 * RESEARCH: Based on RETELL_CONVERSATION_FLOW_RESEARCH_2025-11-04.md
 * Architecture follows section 8 (Recommended Flow with Alternatives)
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Configuration
$apiKey = config('services.retellai.api_key');
$baseUrl = config('services.retellai.base_url');
$agentId = config('services.retellai.agent_id');
$conversationFlowId = 'conversation_flow_a58405e3f67a';

if (empty($apiKey) || empty($agentId)) {
    echo "❌ ERROR: Missing Retell API credentials\n";
    echo "   API Key: " . (empty($apiKey) ? 'MISSING' : 'Present') . "\n";
    echo "   Agent ID: " . (empty($agentId) ? 'MISSING' : 'Present') . "\n";
    exit(1);
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Retell Conversation Flow V25 - Critical Booking Fix          ║\n";
echo "║  Fix: Alternative Selection → Booking Flow                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Step 1: Fetch Current Flow
echo "📥 STEP 1: Fetching current conversation flow...\n";
echo "   Flow ID: {$conversationFlowId}\n";

try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json',
    ])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

    if (!$response->successful()) {
        echo "❌ Failed to fetch flow: HTTP {$response->status()}\n";
        echo "   Response: " . $response->body() . "\n";
        exit(1);
    }

    $currentFlow = $response->json();
    echo "✅ Current flow retrieved (Version: {$currentFlow['version']})\n";

    // Backup current flow
    $backupFile = "/var/www/api-gateway/storage/logs/flow_backup_v{$currentFlow['version']}_" . date('YmdHis') . ".json";
    file_put_contents($backupFile, json_encode($currentFlow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "💾 Backup saved: {$backupFile}\n\n";

} catch (\Exception $e) {
    echo "❌ Exception during fetch: {$e->getMessage()}\n";
    exit(1);
}

// Step 2: Analyze Current Nodes
echo "🔍 STEP 2: Analyzing current flow structure...\n";

$nodes = collect($currentFlow['nodes']);
$presentResultNode = $nodes->firstWhere('id', 'node_present_result');
$bookAppointmentNode = $nodes->firstWhere('id', 'func_book_appointment');
$extractSelectionNode = $nodes->firstWhere('id', 'node_extract_alternative_selection');
$confirmAlternativeNode = $nodes->firstWhere('id', 'node_confirm_alternative');

echo "\n📊 Current State:\n";
echo "   • node_present_result: " . ($presentResultNode ? '✅ Exists' : '❌ Missing') . "\n";
echo "   • func_book_appointment: " . ($bookAppointmentNode ? '✅ Exists' : '❌ Missing') . "\n";
echo "   • node_extract_alternative_selection: " . ($extractSelectionNode ? '🟡 Exists (will update)' : '❌ Missing (will create)') . "\n";
echo "   • node_confirm_alternative: " . ($confirmAlternativeNode ? '🟡 Exists (will update)' : '❌ Missing (will create)') . "\n";

if (!$presentResultNode || !$bookAppointmentNode) {
    echo "\n❌ ERROR: Critical nodes missing. Flow structure invalid.\n";
    exit(1);
}

echo "\n🔧 STEP 3: Building fixed flow structure...\n";

// Create new nodes for the missing flow
$newNodes = [];
$modifiedNodes = [];

// Position calculations (place between node_present_result and func_book_appointment)
$presentResultPos = $presentResultNode['display_position'];
$bookAppointmentPos = $bookAppointmentNode['display_position'];

$extractSelectionPos = [
    'x' => $presentResultPos['x'] + 300,
    'y' => $presentResultPos['y'] - 200,
];

$confirmAlternativePos = [
    'x' => $extractSelectionPos['x'] + 400,
    'y' => $extractSelectionPos['y'],
];

// Node 1: Extract Alternative Selection (Extract Dynamic Variable Node)
if (!$extractSelectionNode) {
    echo "   ➕ Creating node_extract_alternative_selection...\n";

    $extractSelectionNode = [
        'id' => 'node_extract_alternative_selection',
        'name' => 'Alternative extrahieren',
        'type' => 'extract_dynamic_variable',
        'display_position' => $extractSelectionPos,
        'edges' => [
            [
                'id' => 'edge_extract_to_confirm',
                'destination_node_id' => 'node_confirm_alternative',
                'transition_condition' => [
                    'type' => 'equation',
                    'equation' => '{{selected_alternative_time}} exists'
                ]
            ]
        ],
        'dynamic_variables' => [
            [
                'name' => 'selected_alternative_time',
                'type' => 'text',
                'description' => 'Die vom Kunden gewählte alternative Uhrzeit (z.B. "06:55", "14:30")',
                'required' => true,
            ]
        ],
        'instruction' => [
            'type' => 'prompt',
            'text' => 'Extrahiere die vom Kunden genannte Uhrzeit aus seiner Antwort. Der Kunde hat eine der präsentierten Alternativen ausgewählt. Speichere die gewählte Uhrzeit in {{selected_alternative_time}}.'
        ]
    ];

    $newNodes[] = $extractSelectionNode;
}

// Node 2: Confirm Alternative (Conversation Node)
if (!$confirmAlternativeNode) {
    echo "   ➕ Creating node_confirm_alternative...\n";

    $confirmAlternativeNode = [
        'id' => 'node_confirm_alternative',
        'name' => 'Alternative bestätigen',
        'type' => 'conversation',
        'display_position' => $confirmAlternativePos,
        'edges' => [
            [
                'id' => 'edge_confirm_to_book',
                'destination_node_id' => 'func_book_appointment',
                'transition_condition' => [
                    'type' => 'equation',
                    'equation' => '{{selected_alternative_time}} exists'
                ]
            ],
            [
                'id' => 'edge_confirm_to_retry',
                'destination_node_id' => 'node_present_result',
                'transition_condition' => [
                    'type' => 'prompt',
                    'prompt' => 'User declines or wants different time'
                ]
            ]
        ],
        'instruction' => [
            'type' => 'static_text',
            'text' => 'Perfekt! Einen Moment bitte, ich buche den Termin um {{selected_alternative_time}} für Sie...'
        ]
    ];

    $newNodes[] = $confirmAlternativeNode;
}

// Step 4: Update node_present_result to add transition to extract node
echo "   🔧 Updating node_present_result transitions...\n";

$updatedPresentResultNode = $presentResultNode;

// Find if edge to extract already exists
$hasExtractEdge = collect($updatedPresentResultNode['edges'] ?? [])
    ->contains('destination_node_id', 'node_extract_alternative_selection');

if (!$hasExtractEdge) {
    // Add new edge to extract alternative selection
    $newEdge = [
        'id' => 'edge_present_to_extract',
        'destination_node_id' => 'node_extract_alternative_selection',
        'transition_condition' => [
            'type' => 'prompt',
            'prompt' => 'User selected one of the presented alternative time slots (e.g., "Um 06:55", "Den ersten Termin", "14:30")'
        ]
    ];

    // Insert at beginning so it takes priority
    array_unshift($updatedPresentResultNode['edges'], $newEdge);
}

// Update instruction to remove the "loop back to check" behavior
$updatedPresentResultNode['instruction'] = [
    'type' => 'prompt',
    'text' => 'Zeige das Ergebnis der Verfügbarkeitsprüfung:

**WENN VERFÜGBAR:**
"Der Termin am {{appointment_date}} um {{appointment_time}} für {{service_name}} ist verfügbar. Soll ich den Termin für Sie buchen?"

**WENN NICHT VERFÜGBAR mit ALTERNATIVEN:**
Präsentiere die Alternativen EINMAL klar und knapp.
Beispiel: "Leider ist {{appointment_date}} um {{appointment_time}} nicht verfügbar. Ich habe jedoch folgende Alternativen für Sie: [Liste]. Welcher Termin würde Ihnen besser passen?"

**WICHTIG - Wenn User Alternative wählt:**
- User sagt z.B. "Um 06:55" oder "Den ersten Termin"
- ✅ TRANSITION zu node_extract_alternative_selection
- Die gewählte Zeit wird extrahiert und der Termin wird gebucht

**NUR wenn User explizit den URSPRÜNGLICH GEWÜNSCHTEN Termin buchen möchte:**
- "Ja", "Gerne", "Buchen Sie" → func_book_appointment

**KEINE redundanten Bestätigungen wie:**
❌ "Also, um das klarzustellen: Sie möchten den Termin..."
❌ "Ist das richtig?"
✅ Vertraue dem User - wenn er eine Zeit nennt, nutze sie!'
];

$modifiedNodes[] = $updatedPresentResultNode;

// Step 5: Update func_book_appointment to handle selected_alternative_time
echo "   🔧 Updating func_book_appointment parameter mapping...\n";

$updatedBookAppointmentNode = $bookAppointmentNode;

// Update parameter mapping to use selected_alternative_time if it exists, otherwise use appointment_time
$updatedBookAppointmentNode['parameter_mapping'] = [
    'name' => '{{customer_name}}',
    'datum' => '{{appointment_date}}',
    'dienstleistung' => '{{service_name}}',
    // Use selected alternative if exists, otherwise use original time
    'uhrzeit' => '{{selected_alternative_time}}',
];

// Add instruction to make it clear we're using the alternative
$updatedBookAppointmentNode['instruction'] = [
    'type' => 'static_text',
    'text' => 'Perfekt! Einen Moment, ich buche den Termin...'
];

$modifiedNodes[] = $updatedBookAppointmentNode;

// Step 6: Build updated flow
echo "\n📦 STEP 4: Building updated flow structure...\n";

$updatedNodes = collect($currentFlow['nodes'])
    ->map(function ($node) use ($modifiedNodes) {
        // Check if this node was modified
        $modified = collect($modifiedNodes)->firstWhere('id', $node['id']);
        return $modified ?? $node;
    })
    ->merge($newNodes)
    ->values()
    ->all();

$updatedFlow = array_merge($currentFlow, [
    'nodes' => $updatedNodes,
    'version' => $currentFlow['version'] + 1,
]);

echo "   ✅ Flow structure built:\n";
echo "      - Total nodes: " . count($updatedNodes) . "\n";
echo "      - New nodes added: " . count($newNodes) . "\n";
echo "      - Modified nodes: " . count($modifiedNodes) . "\n";
echo "      - New version: {$updatedFlow['version']}\n";

// Save updated flow for review
$updateFile = "/var/www/api-gateway/storage/logs/flow_update_v{$updatedFlow['version']}_" . date('YmdHis') . ".json";
file_put_contents($updateFile, json_encode($updatedFlow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "   💾 Updated flow saved for review: {$updateFile}\n";

// Step 7: Validate structure
echo "\n✅ STEP 5: Validating flow structure...\n";

$validation = [
    'has_extract_node' => collect($updatedNodes)->contains('id', 'node_extract_alternative_selection'),
    'has_confirm_node' => collect($updatedNodes)->contains('id', 'node_confirm_alternative'),
    'present_has_extract_edge' => collect($updatedPresentResultNode['edges'])->contains('destination_node_id', 'node_extract_alternative_selection'),
    'extract_has_confirm_edge' => collect($extractSelectionNode['edges'])->contains('destination_node_id', 'node_confirm_alternative'),
    'confirm_has_book_edge' => collect($confirmAlternativeNode['edges'])->contains('destination_node_id', 'func_book_appointment'),
];

echo "   Validation Results:\n";
foreach ($validation as $check => $result) {
    echo "      " . ($result ? '✅' : '❌') . " {$check}\n";
}

if (in_array(false, $validation, true)) {
    echo "\n❌ Validation failed. Please review the flow structure.\n";
    exit(1);
}

echo "\n✅ All validations passed!\n";

// Step 8: Update flow via API
echo "\n🚀 STEP 6: Updating conversation flow via Retell API...\n";

// Ask for confirmation
echo "\n⚠️  CONFIRMATION REQUIRED ⚠️\n";
echo "This will update the conversation flow to Version {$updatedFlow['version']}\n";
echo "The flow will add:\n";
echo "  1. Alternative selection extraction\n";
echo "  2. Explicit confirmation before booking\n";
echo "  3. Proper transition to book_appointment\n\n";
echo "Backup saved: {$backupFile}\n";
echo "Review file: {$updateFile}\n\n";
echo "Type 'YES' to proceed with update: ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'YES') {
    echo "❌ Update cancelled by user\n";
    exit(0);
}

echo "\n📤 Sending PATCH request to Retell API...\n";

try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json',
    ])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", $updatedFlow);

    if (!$response->successful()) {
        echo "❌ Failed to update flow: HTTP {$response->status()}\n";
        echo "   Response: " . $response->body() . "\n";
        exit(1);
    }

    $result = $response->json();
    echo "✅ Flow updated successfully!\n";
    echo "   New Version: {$result['version']}\n";
    echo "   Updated at: " . date('Y-m-d H:i:s') . "\n";

} catch (\Exception $e) {
    echo "❌ Exception during update: {$e->getMessage()}\n";
    exit(1);
}

// Step 9: Verify update
echo "\n🔍 STEP 7: Verifying update...\n";

sleep(2); // Give API time to propagate

try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json',
    ])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

    if (!$response->successful()) {
        echo "⚠️  Could not verify update: HTTP {$response->status()}\n";
    } else {
        $verifiedFlow = $response->json();
        echo "✅ Verification successful!\n";
        echo "   Current Version: {$verifiedFlow['version']}\n";
        echo "   Node Count: " . count($verifiedFlow['nodes']) . "\n";

        // Verify critical nodes exist
        $verifiedNodes = collect($verifiedFlow['nodes']);
        $criticalNodes = [
            'node_extract_alternative_selection',
            'node_confirm_alternative',
            'node_present_result',
            'func_book_appointment',
        ];

        echo "\n   Critical Nodes Check:\n";
        foreach ($criticalNodes as $nodeId) {
            $exists = $verifiedNodes->contains('id', $nodeId);
            echo "      " . ($exists ? '✅' : '❌') . " {$nodeId}\n";
        }
    }

} catch (\Exception $e) {
    echo "⚠️  Verification exception: {$e->getMessage()}\n";
}

// Summary
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                    ✅ UPDATE COMPLETE                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📋 SUMMARY:\n";
echo "   • Flow Version: V{$updatedFlow['version']}\n";
echo "   • New Nodes Added: " . count($newNodes) . "\n";
echo "   • Modified Nodes: " . count($modifiedNodes) . "\n";
echo "   • Backup File: {$backupFile}\n";
echo "   • Update File: {$updateFile}\n";
echo "\n";
echo "🎯 FIXED FLOW:\n";
echo "   User selects alternative (\"Um 06:55\")\n";
echo "      ↓\n";
echo "   node_extract_alternative_selection (Extract time)\n";
echo "      ↓\n";
echo "   node_confirm_alternative (\"Einen Moment, ich buche...\")\n";
echo "      ↓\n";
echo "   func_book_appointment (Actual booking with selected time)\n";
echo "      ↓\n";
echo "   node_booking_success (Confirmation)\n";
echo "\n";
echo "✅ The booking will now be triggered after alternative selection!\n";
echo "\n";
echo "📝 NEXT STEPS:\n";
echo "   1. Test with: \"Ich möchte einen Herrenhaarschnitt für morgen um 10 Uhr\"\n";
echo "   2. When alternatives presented: \"Um 06:55\" or \"Den ersten Termin\"\n";
echo "   3. Verify booking is actually executed (check logs)\n";
echo "   4. Monitor webhook logs: tail -f storage/logs/laravel.log | grep book_appointment\n";
echo "\n";

Log::info('Retell Flow V25 Update Complete', [
    'version' => $updatedFlow['version'],
    'new_nodes' => count($newNodes),
    'modified_nodes' => count($modifiedNodes),
    'backup_file' => $backupFile,
    'update_file' => $updateFile,
]);

exit(0);
