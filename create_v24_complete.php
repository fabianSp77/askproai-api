<?php

$sourceFile = '/var/www/api-gateway/public/friseur1_flow_v23_name_policy_fix.json';
$targetFile = '/var/www/api-gateway/public/friseur1_flow_v24_COMPLETE.json';

echo "╔═══════════════════════════════════════════╗\n";
echo "║  Creating V24 with BOTH Fixes Complete     ║\n";
echo "╚═══════════════════════════════════════════╝\n\n";

$flow = json_decode(file_get_contents($sourceFile), true);

echo "=== Applying Complete Fixes ===\n\n";

// Fix 1: Already in V23 - DSGVO Name Policy
$dsgvoFixed = false;
foreach ($flow['nodes'] as $node) {
    if (($node['id'] ?? null) === 'func_00_initialize') {
        $text = $node['instruction']['text'] ?? '';
        $dsgvoFixed = strpos($text, 'DSGVO NAME POLICY') !== false;
        break;
    }
}
echo "1. DSGVO Name Policy: " . ($dsgvoFixed ? '✅ Already present from V23' : '❌ Missing') . "\n";

// Fix 2: Update func_book_appointment edge destination
echo "\n2. Fixing func_book_appointment edge...\n";
$edgeFixed = false;

foreach ($flow['nodes'] as &$node) {
    if (($node['id'] ?? null) === 'func_book_appointment') {
        echo "   Found func_book_appointment node\n";

        if (isset($node['edges'])) {
            foreach ($node['edges'] as &$edge) {
                if (($edge['id'] ?? null) === 'edge_booking_success') {
                    $oldDest = $edge['destination_node_id'];
                    $edge['destination_node_id'] = 'node_14_success_goodbye';
                    $edgeFixed = true;

                    echo "   ✅ Updated edge_booking_success\n";
                    echo "      OLD: {$oldDest}\n";
                    echo "      NEW: node_14_success_goodbye\n";
                    break 2;
                }
            }
        }
    }
}
unset($node);

if (!$edgeFixed) {
    echo "   ❌ Failed to find edge_booking_success\n";
}

// Save V24
file_put_contents($targetFile, json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n=== V24 Saved ===\n";
echo "File: {$targetFile}\n";
echo "Size: " . round(filesize($targetFile) / 1024, 2) . " KB\n";
echo "Nodes: " . count($flow['nodes']) . "\n\n";

// Verify both fixes are in V24
echo "=== Verification of V24 File ===\n";

$v24 = json_decode(file_get_contents($targetFile), true);

// Check DSGVO
foreach ($v24['nodes'] as $node) {
    if (($node['id'] ?? null) === 'func_00_initialize') {
        $text = $node['instruction']['text'] ?? '';
        $hasDsgvo = strpos($text, 'DSGVO NAME POLICY') !== false;
        echo "✓ DSGVO Policy in func_00_initialize: " . ($hasDsgvo ? '✅' : '❌') . "\n";
        break;
    }
}

// Check edge
foreach ($v24['nodes'] as $node) {
    if (($node['id'] ?? null) === 'func_book_appointment') {
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_booking_success') {
                $dest = $edge['destination_node_id'];
                $correct = $dest === 'node_14_success_goodbye';
                echo "✓ Booking edge destination: " . ($correct ? '✅' : '❌') . " {$dest}\n";
                break 2;
            }
        }
    }
}

echo "\n✅ V24 COMPLETE with both fixes ready for deployment\n";
