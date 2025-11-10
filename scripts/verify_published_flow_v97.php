<?php
/**
 * Verify Published Flow V97
 * Checks all critical points before E2E testing
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "=== PUBLISHED FLOW VERIFICATION ===\n\n";

// 1. Fetch published flow
echo "1. Fetching published flow...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch flow. HTTP {$httpCode}\n");
}

$flow = json_decode($response, true);
echo "✅ Fetched Flow\n";
echo "   Version: {$flow['version']}\n";
echo "   Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n\n";

// 2. Check if it's V97
echo "2. Version Check...\n";
if ($flow['version'] >= 97) {
    echo "✅ Version is {$flow['version']} (expected 97+)\n\n";
} else {
    echo "❌ WARNING: Version is {$flow['version']}, expected 97!\n";
    echo "   → User might have published wrong version\n\n";
}

// 3. Check node_collect_booking_info instruction type
echo "3. Checking node_collect_booking_info...\n";
$collectNode = null;
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_booking_info') {
        $collectNode = $node;
        break;
    }
}

if (!$collectNode) {
    echo "❌ node_collect_booking_info NOT FOUND!\n\n";
} else {
    $instructionType = $collectNode['instruction']['type'] ?? 'unknown';
    echo "   Instruction Type: {$instructionType}\n";

    if ($instructionType === 'prompt') {
        echo "✅ CORRECT: Type is 'prompt' (Agent will NOT read aloud)\n\n";
    } elseif ($instructionType === 'static_text') {
        echo "❌ PROBLEM: Type is 'static_text' (Agent WILL read aloud!)\n";
        echo "   → User published wrong version or fix didn't apply\n\n";
    } else {
        echo "❌ UNKNOWN type: {$instructionType}\n\n";
    }
}

// 4. Check node_present_result edges
echo "4. Checking node_present_result edges...\n";
$presentNode = null;
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_present_result') {
        $presentNode = $node;
        break;
    }
}

if (!$presentNode) {
    echo "❌ node_present_result NOT FOUND!\n\n";
} else {
    $edges = $presentNode['edges'] ?? [];
    echo "   Total edges: " . count($edges) . "\n\n";

    $edgeResults = [
        'exact_match' => false,
        'alternatives_in_response' => false,
        'no_alternatives' => false
    ];

    foreach ($edges as $idx => $edge) {
        $dest = $edge['destination_node_id'] ?? 'MISSING';
        $prompt = $edge['transition_condition']['prompt'] ?? '';

        echo "   Edge #{$idx}:\n";
        echo "     Destination: {$dest}\n";
        echo "     Condition: " . substr($prompt, 0, 60) . "...\n";

        // Check which edge this is
        if (strpos($prompt, 'available:true') !== false || strpos($prompt, 'available AND') !== false) {
            if ($dest === 'func_start_booking') {
                echo "     ✅ CORRECT: Exact match → func_start_booking\n";
                $edgeResults['exact_match'] = true;
            } else {
                echo "     ❌ WRONG: Should point to func_start_booking, points to {$dest}\n";
            }
        } elseif (strpos($prompt, 'alternatives array is not empty') !== false) {
            if ($dest === 'node_present_alternatives') {
                echo "     ✅ CORRECT: Alternatives found → node_present_alternatives\n";
                $edgeResults['alternatives_in_response'] = true;
            } else {
                echo "     ❌ WRONG: Should point to node_present_alternatives, points to {$dest}\n";
            }
        } elseif (strpos($prompt, 'empty or not present') !== false || strpos($prompt, 'no alternatives') !== false) {
            if ($dest === 'func_get_alternatives') {
                echo "     ✅ CORRECT: No alternatives → func_get_alternatives\n";
                $edgeResults['no_alternatives'] = true;
            } else {
                echo "     ❌ WRONG: Should point to func_get_alternatives, points to {$dest}\n";
            }
        }

        echo "\n";
    }

    // Summary
    echo "   Edge Summary:\n";
    if ($edgeResults['exact_match'] && $edgeResults['alternatives_in_response'] && $edgeResults['no_alternatives']) {
        echo "   ✅ ALL 3 EDGES CORRECT!\n\n";
    } else {
        echo "   ❌ MISSING EDGES:\n";
        if (!$edgeResults['exact_match']) echo "     - Exact match edge\n";
        if (!$edgeResults['alternatives_in_response']) echo "     - Alternatives in response edge\n";
        if (!$edgeResults['no_alternatives']) echo "     - No alternatives edge\n";
        echo "\n";
    }
}

// 5. Check node_present_result instruction
echo "5. Checking node_present_result instruction...\n";
if ($presentNode) {
    $instruction = $presentNode['instruction']['text'] ?? '';

    // Check if it has the 3-case logic
    $hasFall1 = strpos($instruction, 'FALL 1') !== false;
    $hasFall2 = strpos($instruction, 'FALL 2') !== false;
    $hasFall3 = strpos($instruction, 'FALL 3') !== false;

    if ($hasFall1 && $hasFall2 && $hasFall3) {
        echo "   ✅ CORRECT: Has 3-case logic (FALL 1, 2, 3)\n\n";
    } else {
        echo "   ❌ MISSING: 3-case logic incomplete\n";
        if (!$hasFall1) echo "     - FALL 1 missing\n";
        if (!$hasFall2) echo "     - FALL 2 missing\n";
        if (!$hasFall3) echo "     - FALL 3 missing\n";
        echo "\n";
    }
}

// 6. Final Summary
echo "=== FINAL VERIFICATION ===\n\n";

$allGood = true;
$issues = [];

if ($flow['version'] < 97) {
    $allGood = false;
    $issues[] = "Version is {$flow['version']}, expected 97+";
}

if (isset($instructionType) && $instructionType !== 'prompt') {
    $allGood = false;
    $issues[] = "node_collect_booking_info instruction type is '{$instructionType}', should be 'prompt'";
}

if (!($edgeResults['exact_match'] ?? false)) {
    $allGood = false;
    $issues[] = "Missing exact match edge";
}

if (!($edgeResults['alternatives_in_response'] ?? false)) {
    $allGood = false;
    $issues[] = "Missing alternatives in response edge";
}

if (!($edgeResults['no_alternatives'] ?? false)) {
    $allGood = false;
    $issues[] = "Missing no alternatives edge";
}

if ($allGood) {
    echo "✅✅✅ PERFECT! Flow V{$flow['version']} is ready for testing! ✅✅✅\n\n";
    echo "Next steps:\n";
    echo "1. Make test call\n";
    echo "2. Check that Agent does NOT read instructions aloud\n";
    echo "3. Test all 3 cases:\n";
    echo "   - Exact match available → direct booking\n";
    echo "   - Alternatives found → presents alternatives\n";
    echo "   - No alternatives → searches wider\n";
} else {
    echo "❌ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
    echo "\nAction required:\n";
    echo "1. Check which version was published in Retell Dashboard\n";
    echo "2. Make sure Version 97 is selected\n";
    echo "3. Click Publish again if needed\n";
}

echo "\n=== END VERIFICATION ===\n";
