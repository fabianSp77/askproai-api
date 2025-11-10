#!/usr/bin/env php
<?php

/**
 * Fix Flow V26 - Add Alternative Selection with CORRECT Schema
 *
 * Problem: Previous attempts used wrong field names/structure
 * Solution: Match EXACT schema from current flow + working examples
 *
 * Changes:
 * 1. Add node_extract_alternative_selection (extract_dynamic_variables)
 * 2. Add node_confirm_alternative (conversation)
 * 3. Modify node_present_result (add edge to extract)
 * 4. Modify func_book_appointment (update parameter mapping)
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$apiKey = config('services.retellai.api_key');
$baseUrl = config('services.retellai.base_url', 'https://api.retellai.com');
$conversationFlowId = 'conversation_flow_a58405e3f67a';

if (!$apiKey) {
    echo "❌ RETELLAI_API_KEY not configured\n";
    exit(1);
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🔧 Fix Flow V26 - Correct Schema                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// STEP 1: Fetch current flow
echo "📥 Step 1: Fetching current flow...\n";
$response = Http::withHeaders([
    'Authorization' => "Bearer {$apiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

if (!$response->successful()) {
    echo "❌ Failed to fetch flow: HTTP {$response->status()}\n";
    echo $response->body() . "\n";
    exit(1);
}

$flow = $response->json();
$currentVersion = $flow['version'];
echo "✅ Fetched V{$currentVersion}\n";
echo "   Total nodes: " . count($flow['nodes']) . "\n\n";

// STEP 2: Create extract node with EXACT correct schema
echo "🏗️  Step 2: Creating new nodes...\n";

// Extract node - matches schema from deploy_friseur1_v35_COMPLETE_CORRECT.php
$extractNode = [
    'id' => 'node_extract_alternative_selection',
    'type' => 'extract_dynamic_variables',  // ✅ PLURAL - critical!
    'name' => 'Alternative extrahieren',
    'display_position' => [
        'x' => 3050,
        'y' => -20
    ],
    'variables' => [  // ✅ 'variables' not 'dynamic_variables'
        [
            'type' => 'string',  // ✅ 'string' not 'text'
            'name' => 'selected_alternative_time',
            'description' => 'Die vom Kunden gewählte alternative Uhrzeit (z.B. "06:55", "14:30", "den ersten Termin")'
        ]
    ],
    'edges' => [
        [
            'id' => 'edge_extract_to_confirm',
            'destination_node_id' => 'node_confirm_alternative',
            'transition_condition' => [
                'type' => 'equation',
                'equations' => [  // ✅ PLURAL - critical!
                    [
                        'left' => 'selected_alternative_time',
                        'operator' => 'exists'
                    ]
                ],
                'operator' => '&&'  // ✅ Even with single equation, include operator
            ]
        ]
    ]
];

// Confirmation node - matches existing conversation nodes EXACTLY
$confirmNode = [
    'id' => 'node_confirm_alternative',
    'name' => 'Alternative bestätigen',
    'type' => 'conversation',
    'display_position' => [
        'x' => 3400,
        'y' => -20
    ],
    'instruction' => [  // ✅ Match existing: type + text, not type + static_text
        'type' => 'prompt',  // ✅ 'prompt' not 'static' - check node_present_result line 167
        'text' => 'Sage: "Perfekt! Einen Moment, ich prüfe die Verfügbarkeit für {{selected_alternative_time}} Uhr..." und fahre direkt fort.'
    ],
    'edges' => [
        [
            'id' => 'edge_confirm_to_check',
            'destination_node_id' => 'func_check_availability',
            'transition_condition' => [
                'type' => 'prompt',
                'prompt' => 'Alternative confirmed'
            ]
        ]
    ]
];

echo "✅ node_extract_alternative_selection (extract_dynamic_variables)\n";
echo "   - Variable: selected_alternative_time (type: string)\n";
echo "   - Transition: equation (exists)\n";
echo "✅ node_confirm_alternative (conversation)\n";
echo "   - Updates appointment_time with selected alternative\n\n";

// STEP 3: Modify existing nodes
echo "🔧 Step 3: Modifying existing nodes...\n";

$nodes = collect($flow['nodes']);

// 3a. Update node_present_result - add new edge FIRST (higher priority)
$presentNode = $nodes->firstWhere('id', 'node_present_result');
if (!$presentNode) {
    echo "❌ node_present_result not found!\n";
    exit(1);
}

// Add new edge at the beginning (higher priority)
array_unshift($presentNode['edges'], [
    'id' => 'edge_present_to_extract',
    'destination_node_id' => 'node_extract_alternative_selection',
    'transition_condition' => [
        'type' => 'prompt',
        'prompt' => 'User selected one of the presented alternative time slots (e.g., "Um 06:55", "Den ersten Termin", "14:30")'
    ]
]);

echo "✅ Modified node_present_result\n";
echo "   - Added edge to extract node (priority: first)\n";

// 3b. Update func_book_appointment - use fallback for parameter mapping
$bookNode = $nodes->firstWhere('id', 'func_book_appointment');
if (!$bookNode) {
    echo "❌ func_book_appointment not found!\n";
    exit(1);
}

// Update uhrzeit parameter to try selected_alternative_time first, fallback to appointment_time
$bookNode['parameter_mapping']['uhrzeit'] = '{{selected_alternative_time || appointment_time}}';

echo "✅ Modified func_book_appointment\n";
echo "   - Updated uhrzeit: {{selected_alternative_time || appointment_time}}\n\n";

// STEP 4: Build updated flow
echo "📦 Step 4: Building updated flow...\n";

$updatedNodes = $nodes->map(function($node) use ($presentNode, $bookNode) {
    if ($node['id'] === 'node_present_result') {
        return $presentNode;
    }
    if ($node['id'] === 'func_book_appointment') {
        return $bookNode;
    }
    return $node;
})
->push($extractNode)
->push($confirmNode)
->values()
->toArray();

$updatedFlow = $flow;
$updatedFlow['nodes'] = $updatedNodes;
$updatedFlow['version'] = $currentVersion + 1;
unset($updatedFlow['is_published']); // Remove to avoid conflicts

echo "✅ Flow built\n";
echo "   Total nodes: " . count($updatedNodes) . " (added 2)\n";
echo "   Version: V{$currentVersion} → V{$updatedFlow['version']}\n\n";

// STEP 5: Dry run validation
echo "🔍 Step 5: Dry-run validation...\n";

$extractNodesCount = collect($updatedNodes)->filter(fn($n) => ($n['type'] ?? '') === 'extract_dynamic_variables')->count();
$conversationNodesCount = collect($updatedNodes)->filter(fn($n) => ($n['type'] ?? '') === 'conversation')->count();
$functionNodesCount = collect($updatedNodes)->filter(fn($n) => ($n['type'] ?? '') === 'function')->count();

echo "   Extract nodes: {$extractNodesCount}\n";
echo "   Conversation nodes: {$conversationNodesCount}\n";
echo "   Function nodes: {$functionNodesCount}\n";

// Check extract node structure
$addedExtract = collect($updatedNodes)->firstWhere('id', 'node_extract_alternative_selection');
if (!$addedExtract) {
    echo "❌ Extract node not found in updated flow!\n";
    exit(1);
}

echo "\n   Validating extract node:\n";
echo "   - Type: " . ($addedExtract['type'] ?? 'MISSING') . "\n";
echo "   - Variables field: " . (isset($addedExtract['variables']) ? 'present' : 'MISSING') . "\n";
echo "   - Variables count: " . count($addedExtract['variables'] ?? []) . "\n";
echo "   - First var type: " . ($addedExtract['variables'][0]['type'] ?? 'MISSING') . "\n";
echo "   - Edges count: " . count($addedExtract['edges'] ?? []) . "\n";
echo "   - Transition type: " . ($addedExtract['edges'][0]['transition_condition']['type'] ?? 'MISSING') . "\n";
echo "   - Equations field: " . (isset($addedExtract['edges'][0]['transition_condition']['equations']) ? 'present' : 'MISSING') . "\n";

if ($addedExtract['type'] !== 'extract_dynamic_variables') {
    echo "❌ Wrong type: expected 'extract_dynamic_variables'\n";
    exit(1);
}

if (!isset($addedExtract['variables'])) {
    echo "❌ Missing 'variables' field\n";
    exit(1);
}

if (!isset($addedExtract['edges'][0]['transition_condition']['equations'])) {
    echo "❌ Missing 'equations' field in transition\n";
    exit(1);
}

echo "✅ Structure validation passed\n\n";

// Save to file for inspection
file_put_contents('/tmp/flow_v26_dry_run.json', json_encode($updatedFlow, JSON_PRETTY_PRINT));
echo "💾 Dry-run saved to: /tmp/flow_v26_dry_run.json\n\n";

// STEP 6: Ask for confirmation
echo "═══════════════════════════════════════════════════════════\n";
echo "Ready to apply changes:\n";
echo "  • Add: node_extract_alternative_selection\n";
echo "  • Add: node_confirm_alternative\n";
echo "  • Modify: node_present_result (+ edge)\n";
echo "  • Modify: func_book_appointment (parameter)\n";
echo "  • Version: V{$currentVersion} → V{$updatedFlow['version']}\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Apply changes? [y/N]: ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'y') {
    echo "❌ Aborted by user\n";
    exit(0);
}

// STEP 7: Apply to API
echo "\n🚀 Step 7: Applying to Retell API...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$apiKey}",
    'Content-Type' => 'application/json',
])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", $updatedFlow);

if (!$response->successful()) {
    echo "❌ Failed: HTTP {$response->status()}\n";
    echo "Response:\n";
    echo $response->body() . "\n\n";

    // Save error response
    file_put_contents('/tmp/flow_v26_error.json', json_encode([
        'status' => $response->status(),
        'body' => $response->json(),
        'sent_flow' => $updatedFlow
    ], JSON_PRETTY_PRINT));
    echo "💾 Error details saved to: /tmp/flow_v26_error.json\n";
    exit(1);
}

echo "✅ Flow updated successfully!\n";
echo "   New version: V{$updatedFlow['version']}\n\n";

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ SUCCESS - Flow V{$updatedFlow['version']} Applied                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Next steps:\n";
echo "  1. Test flow in Retell dashboard\n";
echo "  2. If working, publish agent:\n";
echo "     php scripts/publish_agent_v16.php\n\n";
