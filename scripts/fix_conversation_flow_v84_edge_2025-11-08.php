#!/usr/bin/env php
<?php
/**
 * FIX: Change node_collect_booking_info edge condition
 *
 * BUG: Prompt-based condition checks USER INPUT in this node
 * FIX: Equation-based condition checks VARIABLE existence
 *
 * Date: 2025-11-08
 */

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = 'https://api.retellai.com';

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  🔧 FIXING CONVERSATION FLOW V84 - EDGE CONDITION BUG\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

// Fetch current flow
echo "📖 Fetching current flow...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/get-conversation-flow/$conversationFlowId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch flow: $httpCode\n");
}

$flow = json_decode($response, true);
echo "✅ Fetched version " . ($flow['version'] ?? 'N/A') . "\n\n";

// Find and fix node_collect_booking_info
echo "🔍 Finding node_collect_booking_info...\n";

$fixed = false;
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_booking_info') {
        echo "✅ Found node!\n\n";

        echo "BEFORE:\n";
        echo "  Edges: " . count($node['edges']) . "\n";
        echo "  Edge condition type: " . $node['edges'][0]['transition_condition']['type'] . "\n\n";

        // Change edge condition from prompt to equation
        $node['edges'][0]['transition_condition'] = [
            'type' => 'equation',
            'equations' => [
                ['left' => 'service_name', 'operator' => 'exists'],
                ['left' => 'appointment_date', 'operator' => 'exists'],
                ['left' => 'appointment_time', 'operator' => 'exists']
            ],
            'operator' => '&&'
        ];

        echo "AFTER:\n";
        echo "  Edge condition type: equation\n";
        echo "  Checks: service_name EXISTS && appointment_date EXISTS && appointment_time EXISTS\n\n";

        $fixed = true;
        break;
    }
}

if (!$fixed) {
    die("❌ Node not found!\n");
}

echo "✅ Fix applied to flow data\n\n";

// Upload fixed flow
echo "📤 Uploading fixed flow to Retell AI...\n";

$payload = [
    'global_prompt' => $flow['global_prompt'],
    'nodes' => $flow['nodes'],
    'tools' => $flow['tools'],
    'model_choice' => $flow['model_choice'] ?? ['type' => 'cascading', 'model' => 'gpt-4o-mini'],
    'model_temperature' => $flow['model_temperature'] ?? 0.3,
    'start_node_id' => $flow['start_node_id'] ?? 'node_greeting',
    'start_speaker' => $flow['start_speaker'] ?? 'agent',
    'begin_after_user_silence_ms' => $flow['begin_after_user_silence_ms'] ?? 800,
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/update-conversation-flow/$conversationFlowId");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);

    echo "═══════════════════════════════════════════════════════════\n";
    echo "  ✅ FIX UPLOADED SUCCESSFULLY!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "New Version: " . ($result['version'] ?? 'N/A') . "\n\n";

    echo "📋 WHAT WAS FIXED:\n";
    echo "─────────────────────────────────────────────────────────\n";
    echo "Problem: Edge condition checked USER INPUT (prompt type)\n";
    echo "         → If user gave data earlier, edge not triggered\n";
    echo "         → Agent skipped booking, hallucinated success\n\n";

    echo "Solution: Edge condition now checks VARIABLES (equation type)\n";
    echo "         → If variables exist, edge triggers\n";
    echo "         → Booking flow always executes\n\n";

    echo "Impact: Fixes 'direct booking' scenario where user\n";
    echo "        provides all info upfront (name, service, date, time)\n\n";

    echo "═══════════════════════════════════════════════════════════\n";
    echo "  🧪 READY FOR TESTING\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "Test Case:\n";
    echo "1. Call: +493033081738\n";
    echo "2. Say: 'Hans Schuster, Herrenhaarschnitt, Montag 10.11. um 7 Uhr'\n";
    echo "3. Expected: check_availability() called\n";
    echo "4. Expected: book_appointment() or start_booking() called\n";
    echo "5. Expected: Appointment created in database\n\n";

} else {
    echo "❌ UPLOAD FAILED!\n";
    echo "Response: $response\n";
    exit(1);
}
