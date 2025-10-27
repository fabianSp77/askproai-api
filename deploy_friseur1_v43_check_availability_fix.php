<?php
/**
 * Deploy Friseur1 Flow V43: check_availability Fix
 *
 * ROOT CAUSE (Call 725, 16:59:08):
 * - AI SAYS it's checking availability but NEVER calls the function
 * - "Bekannter Kunde" node has NO function call actions
 * - Flow jumps directly to Intent node WITHOUT calling check_availability
 *
 * FIX:
 * - Add check_availability_v17 function call action to "Bekannter Kunde" node
 * - Trigger after speaking completion
 * - Use dynamic variables from conversation
 *
 * Date: 2025-10-24 17:15 CEST
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "========================================\n";
echo "FRISEUR1 V43 - CHECK_AVAILABILITY FIX\n";
echo "========================================\n";
echo "Timestamp: " . now()->format('Y-m-d H:i:s') . "\n\n";

// Load base flow V24
$baseFlowPath = __DIR__ . '/public/friseur1_flow_v24_COMPLETE.json';
if (!file_exists($baseFlowPath)) {
    die("ERROR: Base flow V24 not found at: $baseFlowPath\n");
}

$flowData = json_decode(file_get_contents($baseFlowPath), true);
if (!$flowData) {
    die("ERROR: Could not parse flow JSON\n");
}

echo "✅ Loaded base flow V24\n";
echo "   Nodes: " . count($flowData['nodes']) . "\n";
echo "   Edges: " . count($flowData['edges'] ?? []) . "\n\n";

// Find "Bekannter Kunde" node
$bekanterKundeNode = null;
$nodeIndex = null;

foreach ($flowData['nodes'] as $index => $node) {
    if ($node['id'] === 'node_03a_known_customer' || $node['name'] === 'Bekannter Kunde') {
        $bekanterKundeNode = $node;
        $nodeIndex = $index;
        break;
    }
}

if (!$bekanterKundeNode) {
    die("ERROR: Could not find 'Bekannter Kunde' node\n");
}

echo "📍 Found 'Bekannter Kunde' node:\n";
echo "   ID: {$bekanterKundeNode['id']}\n";
echo "   Name: {$bekanterKundeNode['name']}\n";
echo "   Current edges: " . count($bekanterKundeNode['edges'] ?? []) . "\n";
echo "   Current actions: " . count($bekanterKundeNode['actions'] ?? []) . "\n\n";

// Add check_availability function call action
echo "🔧 Adding check_availability_v17 function call action...\n\n";

$checkAvailabilityAction = [
    'type' => 'function_call',
    'function_name' => 'check_availability_v17',
    'description' => 'Check appointment availability in Cal.com',
    'parameters' => [
        'name' => '{{customer_name}}',
        'datum' => '{{datum}}',
        'uhrzeit' => '{{uhrzeit}}',
        'dienstleistung' => '{{dienstleistung}}'
    ],
    'trigger_timing' => 'after_speaking',
    'wait_for_response' => true
];

// Initialize actions array if it doesn't exist
if (!isset($flowData['nodes'][$nodeIndex]['actions'])) {
    $flowData['nodes'][$nodeIndex]['actions'] = [];
}

// Add the new action at the beginning
array_unshift($flowData['nodes'][$nodeIndex]['actions'], $checkAvailabilityAction);

echo "✅ Added check_availability_v17 action\n";
echo "   Function: check_availability_v17\n";
echo "   Parameters: name, datum, uhrzeit, dienstleistung\n";
echo "   Trigger: after_speaking\n";
echo "   Wait for response: true\n\n";

// Save as V43
$v43Path = __DIR__ . '/public/friseur1_flow_v43_availability_fix.json';
file_put_contents($v43Path, json_encode($flowData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "💾 Saved V43 flow to: $v43Path\n";
echo "   File size: " . number_format(filesize($v43Path)) . " bytes\n\n";

// Get Retell API configuration
$retellToken = env('RETELL_TOKEN');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9'; // From logs - this is the ACTIVE agent

if (!$retellToken) {
    echo "⚠️  WARNING: RETELL_TOKEN not set in .env\n";
    echo "   Cannot publish to Retell API automatically\n\n";
    echo "MANUAL STEPS:\n";
    echo "1. Go to: https://dashboard.retellai.com\n";
    echo "2. Navigate to Agent: $agentId\n";
    echo "3. Import conversation flow from: $v43Path\n";
    echo "4. Publish the agent\n\n";
    exit(0);
}

echo "🚀 Publishing to Retell API...\n";
echo "   Agent ID: $agentId\n";
echo "   API: https://api.retellai.com\n\n";

try {
    // Update agent with new conversation flow
    $response = Http::withHeaders([
        'Authorization' => "Bearer $retellToken",
        'Content-Type' => 'application/json',
    ])->patch("https://api.retellai.com/update-agent/$agentId", [
        'conversation_flow' => $flowData
    ]);

    if ($response->successful()) {
        echo "✅ Agent updated successfully!\n";
        $responseData = $response->json();
        echo "   Agent Name: " . ($responseData['agent_name'] ?? 'N/A') . "\n";
        echo "   Agent Version: " . ($responseData['agent_version'] ?? 'N/A') . "\n\n";

        // Publish the agent
        echo "📢 Publishing agent version...\n";

        $publishResponse = Http::withHeaders([
            'Authorization' => "Bearer $retellToken",
            'Content-Type' => 'application/json',
        ])->post("https://api.retellai.com/publish-agent/$agentId");

        if ($publishResponse->successful()) {
            echo "✅ Agent published successfully!\n";
            $publishData = $publishResponse->json();
            echo "   Published Version: " . ($publishData['agent_version'] ?? 'N/A') . "\n";
            echo "   Status: LIVE\n\n";

            echo "========================================\n";
            echo "DEPLOYMENT COMPLETE - V43 IS LIVE!\n";
            echo "========================================\n\n";

            echo "WHAT WAS FIXED:\n";
            echo "✅ 'Bekannter Kunde' node now calls check_availability_v17\n";
            echo "✅ Function triggers after AI speaks\n";
            echo "✅ Uses dynamic variables from conversation\n";
            echo "✅ AI will actually check Cal.com availability\n\n";

            echo "NEXT STEPS:\n";
            echo "1. Make a test call: +493033081738\n";
            echo "2. Say: 'Ich hätte gern einen Termin morgen 10 Uhr Herrenhaarschnitt'\n";
            echo "3. Verify: AI should ACTUALLY check availability (not just say it)\n";
            echo "4. Check logs: tail -f storage/logs/laravel-2025-10-24.log\n";
            echo "5. Look for: 'check_availability_v17' function call\n\n";

        } else {
            echo "❌ Failed to publish agent\n";
            echo "   Status: " . $publishResponse->status() . "\n";
            echo "   Error: " . $publishResponse->body() . "\n\n";
            exit(1);
        }

    } else {
        echo "❌ Failed to update agent\n";
        echo "   Status: " . $response->status() . "\n";
        echo "   Error: " . $response->body() . "\n\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ Exception occurred:\n";
    echo "   " . $e->getMessage() . "\n\n";

    echo "FALLBACK: Manual deployment steps\n";
    echo "1. Download: $v43Path\n";
    echo "2. Go to: https://dashboard.retellai.com\n";
    echo "3. Import and publish manually\n\n";
    exit(1);
}

echo "🎉 SUCCESS! Check_availability fix is now LIVE!\n\n";
