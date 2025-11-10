#!/usr/bin/env php
<?php

/**
 * Verify Flow V26 - Alternative Selection Extract Node
 *
 * Quick verification that the extract node was applied correctly
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$apiKey = config('services.retellai.api_key');
$baseUrl = config('services.retellai.base_url', 'https://api.retellai.com');
$flowId = 'conversation_flow_a58405e3f67a';

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  🔍 Flow V26 Verification                                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Fetch flow
$response = Http::withHeaders([
    'Authorization' => "Bearer {$apiKey}",
])->get("{$baseUrl}/get-conversation-flow/{$flowId}");

if (!$response->successful()) {
    echo "❌ Failed to fetch flow\n";
    exit(1);
}

$flow = $response->json();
$nodes = collect($flow['nodes']);

echo "Flow Version: V{$flow['version']}\n";
echo "Total Nodes: {$nodes->count()}\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "VERIFICATION CHECKLIST\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$allPassed = true;

// Check 1: Extract node exists
echo "1. Extract Node (node_extract_alternative_selection)\n";
$extractNode = $nodes->firstWhere('id', 'node_extract_alternative_selection');
if ($extractNode) {
    echo "   ✅ Node exists\n";

    // Check type
    if ($extractNode['type'] === 'extract_dynamic_variables') {
        echo "   ✅ Type: extract_dynamic_variables\n";
    } else {
        echo "   ❌ Type: {$extractNode['type']} (expected: extract_dynamic_variables)\n";
        $allPassed = false;
    }

    // Check variables
    if (isset($extractNode['variables'])) {
        echo "   ✅ Variables field present\n";
        $var = $extractNode['variables'][0] ?? null;
        if ($var) {
            echo "      - Name: {$var['name']}\n";
            echo "      - Type: {$var['type']}\n";

            if ($var['type'] === 'string') {
                echo "   ✅ Variable type: string\n";
            } else {
                echo "   ❌ Variable type: {$var['type']} (expected: string)\n";
                $allPassed = false;
            }
        }
    } else {
        echo "   ❌ Variables field missing\n";
        $allPassed = false;
    }

    // Check edges
    $edge = $extractNode['edges'][0] ?? null;
    if ($edge && isset($edge['transition_condition']['equations'])) {
        echo "   ✅ Transition uses 'equations' (correct)\n";
    } else {
        echo "   ❌ Transition missing 'equations' field\n";
        $allPassed = false;
    }
} else {
    echo "   ❌ Node not found\n";
    $allPassed = false;
}

echo "\n2. Confirm Node (node_confirm_alternative)\n";
$confirmNode = $nodes->firstWhere('id', 'node_confirm_alternative');
if ($confirmNode) {
    echo "   ✅ Node exists\n";
    echo "   ✅ Type: {$confirmNode['type']}\n";
    echo "   ✅ Target: {$confirmNode['edges'][0]['destination_node_id']}\n";
} else {
    echo "   ❌ Node not found\n";
    $allPassed = false;
}

echo "\n3. Present Result Modification\n";
$presentNode = $nodes->firstWhere('id', 'node_present_result');
if ($presentNode) {
    $extractEdge = collect($presentNode['edges'])->firstWhere('destination_node_id', 'node_extract_alternative_selection');
    if ($extractEdge) {
        echo "   ✅ Edge to extract node exists\n";

        // Check if it's first (highest priority)
        if ($presentNode['edges'][0]['destination_node_id'] === 'node_extract_alternative_selection') {
            echo "   ✅ Edge is FIRST (highest priority)\n";
        } else {
            echo "   ⚠️  Edge exists but not first (may have lower priority)\n";
        }
    } else {
        echo "   ❌ Edge to extract node not found\n";
        $allPassed = false;
    }
} else {
    echo "   ❌ node_present_result not found\n";
    $allPassed = false;
}

echo "\n4. Book Function Parameter Mapping\n";
$bookNode = $nodes->firstWhere('id', 'func_book_appointment');
if ($bookNode) {
    $uhrzeit = $bookNode['parameter_mapping']['uhrzeit'] ?? null;
    echo "   uhrzeit: {$uhrzeit}\n";

    if (str_contains($uhrzeit, 'selected_alternative_time') && str_contains($uhrzeit, '||')) {
        echo "   ✅ Fallback logic present\n";
    } else {
        echo "   ❌ Fallback logic missing or incorrect\n";
        $allPassed = false;
    }
} else {
    echo "   ❌ func_book_appointment not found\n";
    $allPassed = false;
}

echo "\n═══════════════════════════════════════════════════════════\n";

if ($allPassed) {
    echo "✅ ALL CHECKS PASSED\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo "🚀 Ready for testing!\n\n";
    echo "Test scenario:\n";
    echo "  1. Request: 'Herrenhaarschnitt für morgen 14 Uhr, Max'\n";
    echo "  2. Agent offers alternatives\n";
    echo "  3. Say: 'Um 06:55' (or any alternative time)\n";
    echo "  4. Verify: Extract triggers, new time used\n\n";
    echo "Enable logging:\n";
    echo "  php scripts/enable_testcall_logging.sh\n\n";
    exit(0);
} else {
    echo "❌ SOME CHECKS FAILED\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    echo "Review the failures above and re-apply fix if needed.\n\n";
    exit(1);
}
