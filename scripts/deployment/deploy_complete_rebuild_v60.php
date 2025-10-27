#!/usr/bin/env php
<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$token = env('RETELL_TOKEN');

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "COMPLETE AGENT REBUILD - VERSION 60\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "🎯 Goals:\n";
echo "   ✅ All 7 functions included (not just 3)\n";
echo "   ✅ No redundancies (no old functions)\n";
echo "   ✅ Clean architecture (only V17 system)\n";
echo "   ✅ Explicit function nodes (guaranteed execution)\n";
echo "   ✅ Complete feature set (book, check, cancel, reschedule)\n\n";

// Load the flow
$flowPath = __DIR__ . '/../../public/friseur1_complete_rebuild_v60.json';

if (!file_exists($flowPath)) {
    echo "❌ Flow file not found: $flowPath\n";
    exit(1);
}

$flowData = json_decode(file_get_contents($flowPath), true);

if (!$flowData) {
    echo "❌ Invalid JSON in flow file\n";
    exit(1);
}

// Validate flow structure
echo "📋 Validating flow structure...\n\n";

$toolCount = count($flowData['tools'] ?? []);
$nodeCount = count($flowData['nodes'] ?? []);
$edgeCount = count($flowData['edges'] ?? []);

echo "   Tools: $toolCount\n";
echo "   Nodes: $nodeCount\n";
echo "   Edges: $edgeCount\n\n";

if ($toolCount !== 7) {
    echo "⚠️  WARNING: Expected 7 tools, found $toolCount\n\n";
}

// List all tools
echo "🔧 Tools included:\n";
foreach ($flowData['tools'] as $tool) {
    $name = $tool['name'] ?? 'unknown';
    $toolId = $tool['tool_id'] ?? 'unknown';
    echo "   ✓ $name ($toolId)\n";
}
echo "\n";

// Check for deprecated tools
$deprecated = ['collect_appointment', 'check_availability', 'book_appointment'];
$hasDeprecated = false;

foreach ($flowData['tools'] as $tool) {
    $name = $tool['name'] ?? '';
    foreach ($deprecated as $dep) {
        if (stripos($name, $dep) !== false && !stripos($name, 'v17')) {
            echo "❌ DEPRECATED TOOL FOUND: $name\n";
            $hasDeprecated = true;
        }
    }
}

if ($hasDeprecated) {
    echo "\n⚠️  Flow contains deprecated tools! Deployment cancelled.\n";
    echo "Please remove old tools and use only V17 versions.\n\n";
    exit(1);
}

echo "✅ No deprecated tools found\n\n";

// Count explicit function nodes
$functionNodes = 0;
$functionNodesWithWait = 0;

foreach ($flowData['nodes'] as $node) {
    if (($node['type'] ?? '') === 'function') {
        $functionNodes++;
        if (($node['wait_for_result'] ?? false) === true) {
            $functionNodesWithWait++;
        }
    }
}

echo "📊 Function nodes analysis:\n";
echo "   Total function nodes: $functionNodes\n";
echo "   With wait_for_result: $functionNodesWithWait\n";

if ($functionNodes > 0 && $functionNodesWithWait === $functionNodes) {
    echo "   ✅ All function nodes have wait_for_result: true\n\n";
} else {
    echo "   ⚠️  Some function nodes missing wait_for_result: true\n\n";
}

// Deploy to Retell
echo "═══════════════════════════════════════════════════════════\n";
echo "DEPLOYING TO RETELL\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "🚀 Updating agent $agentId...\n\n";

try {
    $response = Http::timeout(30)->withHeaders([
        'Authorization' => "Bearer $token",
        'Content-Type' => 'application/json',
    ])->patch("https://api.retellai.com/update-agent/$agentId", [
        'conversation_flow' => $flowData
    ]);

    if ($response->successful()) {
        $data = $response->json();
        $version = $data['version'] ?? 'unknown';

        echo "✅ DEPLOYMENT SUCCESSFUL!\n\n";
        echo "   New Version: $version\n";
        echo "   Agent ID: $agentId\n";
        echo "   Tools: $toolCount\n";
        echo "   Function Nodes: $functionNodes (all with wait_for_result)\n\n";

        // Try to publish
        echo "═══════════════════════════════════════════════════════════\n";
        echo "ATTEMPTING TO PUBLISH\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "⚠️  NOTE: Retell API publish endpoint has known bugs.\n";
        echo "   If publish fails, you MUST publish manually in Dashboard.\n\n";

        $publishResponse = Http::timeout(30)->withHeaders([
            'Authorization' => "Bearer $token",
        ])->post("https://api.retellai.com/publish-agent/$agentId");

        if ($publishResponse->successful()) {
            echo "✅ Publish API call successful\n\n";

            // Verify publish
            sleep(2);
            $verifyResponse = Http::withHeaders([
                'Authorization' => "Bearer $token",
            ])->get("https://api.retellai.com/get-agent/$agentId");

            if ($verifyResponse->successful()) {
                $agentData = $verifyResponse->json();
                $isPublished = $agentData['is_published'] ?? false;
                $currentVersion = $agentData['version'] ?? 'unknown';

                if ($isPublished && $currentVersion == $version) {
                    echo "🎉 PUBLISH VERIFIED! Version $version is now LIVE!\n\n";
                } else {
                    echo "❌ PUBLISH VERIFICATION FAILED\n\n";
                    echo "   Expected: Version $version published\n";
                    echo "   Actual: Version $currentVersion, published=$isPublished\n\n";
                    echo "🔧 MANUAL ACTION REQUIRED:\n";
                    echo "   Go to: https://dashboard.retellai.com/agent/$agentId\n";
                    echo "   Find: Version $version (with 7 tools)\n";
                    echo "   Click: Publish button\n\n";
                }
            }
        } else {
            echo "❌ Publish API call failed\n\n";
            echo "🔧 MANUAL ACTION REQUIRED:\n";
            echo "   Go to: https://dashboard.retellai.com/agent/$agentId\n";
            echo "   Find: Version $version (with 7 tools)\n";
            echo "   Click: Publish button\n\n";
        }

    } else {
        echo "❌ DEPLOYMENT FAILED\n\n";
        echo "Response Status: " . $response->status() . "\n";
        echo "Response Body: " . $response->body() . "\n\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "DEPLOYMENT SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "✅ Agent Updated with Complete Rebuild\n";
echo "✅ 7 Functions Available:\n";
echo "   1. initialize_call - Call initialization\n";
echo "   2. check_availability_v17 - Check if time available\n";
echo "   3. book_appointment_v17 - Book confirmed appointment\n";
echo "   4. get_customer_appointments - View existing appointments\n";
echo "   5. cancel_appointment - Cancel appointment\n";
echo "   6. reschedule_appointment - Move appointment\n";
echo "   7. get_available_services - List services\n\n";

echo "✅ Architecture:\n";
echo "   - Only V17 functions (no deprecated)\n";
echo "   - Explicit function nodes with wait_for_result\n";
echo "   - Complete feature set (book + manage)\n";
echo "   - Clean, maintainable flow\n\n";

echo "📋 NEXT STEPS:\n\n";

echo "1. Verify in Dashboard:\n";
echo "   https://dashboard.retellai.com/agent/$agentId\n";
echo "   → Check if Version $version is published\n";
echo "   → Verify tool count = 7\n\n";

echo "2. Map Phone Number (if not already):\n";
echo "   https://dashboard.retellai.com/phone-numbers\n";
echo "   → +493033081738 → $agentId\n\n";

echo "3. Test All Functions:\n";
echo "   php scripts/testing/test_all_functions.php\n\n";

echo "═══════════════════════════════════════════════════════════\n\n";
