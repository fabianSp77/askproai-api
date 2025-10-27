<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;
$flowId = 'conversation_flow_1607b81c8f93';
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "═══════════════════════════════════════════════════════\n";
echo "🔍 V39 FIX VERIFICATION\n";
echo "═══════════════════════════════════════════════════════\n\n";

// STEP 1: Get Flow
echo "📥 STEP 1: Fetching current flow...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-conversation-flow/$flowId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch flow! HTTP $httpCode\n");
}

$flow = json_decode($response, true);

echo "✅ Flow fetched!\n";
echo "   Total Nodes: " . count($flow['nodes']) . "\n";
echo "   Version: " . ($flow['version'] ?? 'N/A') . "\n";
echo "   Last Modified: " . ($flow['last_modification_timestamp'] ?? 'N/A') . "\n";

// Convert timestamp to readable format
if (isset($flow['last_modification_timestamp'])) {
    $timestamp = $flow['last_modification_timestamp'] / 1000; // Convert from ms
    $dateTime = new DateTime('@' . $timestamp);
    $dateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
    echo "   Last Modified (Berlin Time): " . $dateTime->format('Y-m-d H:i:s') . "\n";
}

echo "\n";

// STEP 2: Check if node_03c has edges to check_availability
echo "🔍 STEP 2: Checking node_03c_anonymous_customer edges...\n";

$node03c = null;
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'node_03c_anonymous_customer') {
        $node03c = $node;
        break;
    }
}

if (!$node03c) {
    die("❌ node_03c_anonymous_customer not found!\n");
}

echo "✅ Found node_03c_anonymous_customer\n";
echo "   Edges: " . count($node03c['edges'] ?? []) . "\n\n";

if (isset($node03c['edges'])) {
    foreach ($node03c['edges'] as $index => $edge) {
        echo "   Edge #" . ($index + 1) . ":\n";
        echo "      ID: " . ($edge['id'] ?? 'N/A') . "\n";
        echo "      Destination: " . ($edge['destination_node_id'] ?? 'N/A') . "\n";
        echo "      Condition: " . json_encode($edge['transition_condition'] ?? null) . "\n\n";
    }
}

// STEP 3: Check for check_availability function nodes
echo "🔍 STEP 3: Looking for check_availability function nodes...\n";

$checkAvailNodes = [];
foreach ($flow['nodes'] as $node) {
    if ($node['type'] === 'function' &&
        isset($node['tool_id']) &&
        (strpos($node['tool_id'], 'check_availability') !== false ||
         strpos($node['tool_id'], 'check-availability') !== false)) {
        $checkAvailNodes[] = $node;
    }
}

if (count($checkAvailNodes) === 0) {
    echo "❌ NO check_availability function nodes found!\n";
    echo "   This means the fix did NOT work!\n\n";
} else {
    echo "✅ Found " . count($checkAvailNodes) . " check_availability node(s)!\n\n";

    foreach ($checkAvailNodes as $index => $node) {
        echo "   Node #" . ($index + 1) . ":\n";
        echo "      ID: " . ($node['id'] ?? 'N/A') . "\n";
        echo "      Name: " . ($node['name'] ?? 'N/A') . "\n";
        echo "      Tool ID: " . ($node['tool_id'] ?? 'N/A') . "\n";
        echo "      Speak During: " . (($node['speak_during_execution'] ?? false) ? 'YES' : 'NO') . "\n";
        echo "      Wait for Result: " . (($node['wait_for_result'] ?? false) ? 'YES' : 'NO') . "\n";
        echo "      Edges: " . count($node['edges'] ?? []) . "\n\n";
    }
}

// STEP 4: Check if node_03c has edge TO any check_availability node
echo "🔍 STEP 4: Checking if node_03c connects to check_availability...\n";

$hasEdgeToCheckAvail = false;
foreach ($node03c['edges'] ?? [] as $edge) {
    $destId = $edge['destination_node_id'] ?? '';
    foreach ($checkAvailNodes as $checkNode) {
        if ($checkNode['id'] === $destId) {
            $hasEdgeToCheckAvail = true;
            echo "✅ YES! Edge found: node_03c → " . $checkNode['id'] . "\n\n";
            break 2;
        }
    }
}

if (!$hasEdgeToCheckAvail) {
    echo "❌ NO! node_03c does NOT connect to check_availability!\n";
    echo "   This is the problem - edges are missing!\n\n";
}

// STEP 5: Get Agent to check version
echo "🔍 STEP 5: Checking Agent configuration...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$agentResponse = curl_exec($ch);
$agentHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($agentHttpCode === 200) {
    $agent = json_decode($agentResponse, true);

    echo "✅ Agent Details:\n";
    echo "   Agent ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
    echo "   Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "   Version: " . ($agent['version'] ?? 'N/A') . "\n";
    echo "   Published: " . ($agent['is_published'] ? 'YES' : 'NO') . "\n";

    if (isset($agent['response_engine']['conversation_flow_id'])) {
        echo "   Flow ID: " . $agent['response_engine']['conversation_flow_id'] . "\n";
        echo "   Flow Version: " . ($agent['response_engine']['version'] ?? 'N/A') . "\n";

        if ($agent['response_engine']['conversation_flow_id'] === $flowId) {
            echo "   ✅ Agent uses correct flow!\n";
        } else {
            echo "   ❌ Agent uses DIFFERENT flow!\n";
        }
    }

    if (isset($agent['last_modification_timestamp'])) {
        $agentTimestamp = $agent['last_modification_timestamp'] / 1000;
        $agentDateTime = new DateTime('@' . $agentTimestamp);
        $agentDateTime->setTimezone(new DateTimeZone('Europe/Berlin'));
        echo "   Last Modified: " . $agentDateTime->format('Y-m-d H:i:s') . " (Berlin)\n";
    }

    echo "\n";
}

// FINAL VERDICT
echo "═══════════════════════════════════════════════════════\n";
echo "📊 FINAL VERDICT\n";
echo "═══════════════════════════════════════════════════════\n\n";

$allGood = true;

if (count($checkAvailNodes) === 0) {
    echo "❌ NO check_availability function nodes found\n";
    $allGood = false;
}

if (!$hasEdgeToCheckAvail) {
    echo "❌ node_03c does NOT connect to check_availability\n";
    $allGood = false;
}

if ($allGood) {
    echo "✅ ALL CHECKS PASSED!\n";
    echo "   The fix appears to be in place.\n\n";
    echo "🧪 Next: Make test call to verify behavior\n";
} else {
    echo "❌ FIX NOT APPLIED CORRECTLY!\n";
    echo "   The flow was not updated as expected.\n\n";
    echo "🔧 Possible issues:\n";
    echo "   1. PATCH request returned success but didn't actually update\n";
    echo "   2. Agent is not using the updated flow version\n";
    echo "   3. Flow needs to be published to agent\n\n";
}

echo "═══════════════════════════════════════════════════════\n";
