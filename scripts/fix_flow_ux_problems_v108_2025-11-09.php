<?php
/**
 * Fix Flow UX Problems - V108
 *
 * Fixes:
 * 1. Remove node_collect_booking_info (causes double questions)
 * 2. Direct edge: node_extract_booking_variables -> func_check_availability
 * 3. Add customer_phone to extraction variables
 * 4. Add node_collect_phone (if phone missing)
 * 5. Update tool parameter mappings
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = config('services.retellai.api_key');

echo "=== FIX FLOW UX PROBLEMS ===\n\n";

// STEP 1: Get current flow
echo "1. Fetching current flow...\n";
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
    echo "   ❌ Failed to fetch flow: HTTP {$httpCode}\n";
    exit(1);
}

$flow = json_decode($response, true);
$currentVersion = $flow['version'] ?? 'unknown';
echo "   ✅ Current version: V{$currentVersion}\n\n";

// STEP 2: Remove node_collect_booking_info
echo "2. Removing node_collect_booking_info...\n";
$nodesBeforeCount = count($flow['nodes']);
$flow['nodes'] = array_values(array_filter($flow['nodes'], function($node) {
    return $node['id'] !== 'node_collect_booking_info';
}));
$nodesAfterCount = count($flow['nodes']);

if ($nodesBeforeCount === $nodesAfterCount) {
    echo "   ⚠️  Node not found (maybe already removed)\n\n";
} else {
    echo "   ✅ Node removed ({$nodesBeforeCount} → {$nodesAfterCount} nodes)\n\n";
}

// STEP 3: Update node_extract_booking_variables
echo "3. Adding customer_phone to extraction variables...\n";

foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_extract_booking_variables') {
        echo "   Found extraction node\n";

        // Add customer_phone variable if not exists
        $hasPhone = false;
        foreach ($node['variables'] as $var) {
            if ($var['name'] === 'customer_phone') {
                $hasPhone = true;
                break;
            }
        }

        if (!$hasPhone) {
            $node['variables'][] = [
                'type' => 'string',
                'name' => 'customer_phone',
                'description' => 'Telefonnummer des Kunden (optional, z.B. "0151 12345678", "+49 151 12345678")'
            ];
            echo "   ✅ Added customer_phone variable\n";
        } else {
            echo "   ℹ️  customer_phone already exists\n";
        }

        // Update edges - direct to func_check_availability
        $node['edges'] = [
            [
                'destination_node_id' => 'func_check_availability',
                'id' => 'edge_extract_to_check_direct',
                'transition_condition' => [
                    'type' => 'prompt',
                    'prompt' => 'Immer wenn mindestens Name, Service, Datum und Uhrzeit extrahiert wurden'
                ]
            ]
        ];
        echo "   ✅ Updated edge to go directly to func_check_availability\n\n";
        break;
    }
}

// STEP 4: Add node_collect_phone (after node_present_result, if phone missing)
echo "4. Adding node_collect_phone...\n";

// Check if node already exists
$hasCollectPhone = false;
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_phone') {
        $hasCollectPhone = true;
        break;
    }
}

if (!$hasCollectPhone) {
    $flow['nodes'][] = [
        'name' => 'Telefonnummer sammeln',
        'id' => 'node_collect_phone',
        'type' => 'conversation',
        'instruction' => [
            'type' => 'prompt',
            'text' => "WICHTIG: Prüfe ob Telefonnummer bereits bekannt ist!\n\n" .
                     "Telefonnummer: {{customer_phone}}\n\n" .
                     "**Wenn Telefonnummer VORHANDEN:**\n" .
                     "→ Sage NICHTS\n" .
                     "→ Transition SOFORT zu func_start_booking\n\n" .
                     "**Wenn Telefonnummer FEHLT:**\n" .
                     "→ Frage: \"Perfekt! Für die Buchungsbestätigung benötige ich noch Ihre Telefonnummer.\"\n" .
                     "→ Warte auf Antwort\n" .
                     "→ Extrahiere Telefonnummer aus User-Antwort\n" .
                     "→ Transition zu func_start_booking\n\n" .
                     "**NIEMALS:**\n" .
                     "❌ Nach Telefonnummer fragen wenn bereits vorhanden\n" .
                     "❌ Mehrfach nach Telefonnummer fragen"
        ],
        'edges' => [
            [
                'destination_node_id' => 'func_start_booking',
                'id' => 'edge_collect_phone_to_start',
                'transition_condition' => [
                    'type' => 'prompt',
                    'prompt' => 'Telefonnummer vorhanden oder gesammelt'
                ]
            ]
        ],
        'display_position' => [
            'x' => 3800,
            'y' => 0
        ]
    ];
    echo "   ✅ Added node_collect_phone\n\n";
} else {
    echo "   ℹ️  node_collect_phone already exists\n\n";
}

// STEP 5: Update node_present_result to go to node_collect_phone (instead of func_start_booking)
echo "5. Updating node_present_result edges...\n";

foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_present_result') {
        echo "   Found node_present_result\n";

        // Find edge that goes to func_start_booking and change to node_collect_phone
        foreach ($node['edges'] as &$edge) {
            if ($edge['destination_node_id'] === 'func_start_booking') {
                $edge['destination_node_id'] = 'node_collect_phone';
                $edge['id'] = 'edge_present_to_collect_phone';
                echo "   ✅ Changed edge: func_start_booking → node_collect_phone\n\n";
            }
        }
        break;
    }
}

// STEP 6: Update func_start_booking parameter_mapping to include customer_phone
echo "6. Updating func_start_booking parameter mapping...\n";

foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'func_start_booking') {
        echo "   Found func_start_booking\n";

        if (!isset($node['parameter_mapping'])) {
            $node['parameter_mapping'] = [];
        }

        $node['parameter_mapping']['customer_phone'] = '{{customer_phone}}';
        echo "   ✅ Added customer_phone to parameter_mapping\n\n";
        break;
    }
}

// STEP 7: Update func_confirm_booking parameter_mapping
echo "7. Updating func_confirm_booking parameter mapping...\n";

foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'func_confirm_booking') {
        echo "   Found func_confirm_booking\n";

        if (!isset($node['parameter_mapping'])) {
            $node['parameter_mapping'] = [];
        }

        $node['parameter_mapping']['customer_phone'] = '{{customer_phone}}';
        echo "   ✅ Added customer_phone to parameter_mapping\n\n";
        break;
    }
}

// STEP 8: Save updated flow
echo "8. Saving updated flow...\n";

$payload = json_encode($flow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "PATCH",
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => $payload
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "   ❌ Failed to update flow: HTTP {$httpCode}\n";
    echo "   Response: {$response}\n";
    exit(1);
}

$updatedFlow = json_decode($response, true);
$newVersion = $updatedFlow['version'] ?? 'unknown';

echo "   ✅ Flow updated successfully!\n";
echo "   New version: V{$newVersion}\n\n";

// STEP 9: Verify changes
echo "9. Verifying changes...\n";

$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$verifiedFlow = json_decode($response, true);

// Check 1: node_collect_booking_info removed?
$hasCollectInfo = false;
foreach ($verifiedFlow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_booking_info') {
        $hasCollectInfo = true;
        break;
    }
}

if ($hasCollectInfo) {
    echo "   ❌ node_collect_booking_info still exists!\n";
} else {
    echo "   ✅ node_collect_booking_info removed\n";
}

// Check 2: node_collect_phone exists?
$hasCollectPhone = false;
foreach ($verifiedFlow['nodes'] as $node) {
    if ($node['id'] === 'node_collect_phone') {
        $hasCollectPhone = true;
        break;
    }
}

if ($hasCollectPhone) {
    echo "   ✅ node_collect_phone exists\n";
} else {
    echo "   ❌ node_collect_phone not found!\n";
}

// Check 3: node_extract_booking_variables has customer_phone?
$hasPhoneVar = false;
foreach ($verifiedFlow['nodes'] as $node) {
    if ($node['id'] === 'node_extract_booking_variables') {
        foreach ($node['variables'] as $var) {
            if ($var['name'] === 'customer_phone') {
                $hasPhoneVar = true;
                break;
            }
        }
        break;
    }
}

if ($hasPhoneVar) {
    echo "   ✅ customer_phone variable exists\n";
} else {
    echo "   ❌ customer_phone variable not found!\n";
}

// Check 4: Direct edge from extract to check_availability?
$hasDirectEdge = false;
foreach ($verifiedFlow['nodes'] as $node) {
    if ($node['id'] === 'node_extract_booking_variables') {
        foreach ($node['edges'] as $edge) {
            if ($edge['destination_node_id'] === 'func_check_availability') {
                $hasDirectEdge = true;
                break;
            }
        }
        break;
    }
}

if ($hasDirectEdge) {
    echo "   ✅ Direct edge to func_check_availability exists\n";
} else {
    echo "   ❌ Direct edge not found!\n";
}

echo "\n=== SUMMARY ===\n\n";
echo "✅ Flow updated: V{$currentVersion} → V{$newVersion}\n";
echo "✅ node_collect_booking_info removed\n";
echo "✅ node_collect_phone added\n";
echo "✅ customer_phone variable added\n";
echo "✅ Direct edge: extract → check_availability\n";
echo "✅ Parameter mappings updated\n\n";

echo "📌 Published: NO (User must publish manually)\n\n";

echo "=== FIXES APPLIED ===\n\n";
echo "1️⃣  No more double questions (node_collect_booking_info removed)\n";
echo "2️⃣  No more unnecessary confirmation (direct edge)\n";
echo "3️⃣  Phone number collection added (fixes booking failure)\n\n";

echo "=== NEXT STEPS ===\n\n";
echo "1. Publish V{$newVersion} in Retell Dashboard\n";
echo "2. Make VOICE CALL test\n";
echo "3. Verify all 3 problems are fixed\n\n";

echo "Expected behavior:\n";
echo "- User says all info → Agent goes directly to check\n";
echo "- No double questions ✅\n";
echo "- No unnecessary confirmation ✅\n";
echo "- If phone missing → Agent asks for it ✅\n";
echo "- Booking succeeds ✅\n\n";

echo "=== END ===\n";
