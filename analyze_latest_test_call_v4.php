<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 COMPLETE V4 TEST CALL ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get latest call
$callsResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/list-calls", [
        'limit' => 1,
        'sort_order' => 'descending'
    ]);

if (!$callsResp->successful()) {
    echo "❌ Failed to get calls\n";
    exit(1);
}

$calls = $callsResp->json();
if (empty($calls)) {
    echo "❌ No calls found\n";
    exit(1);
}

$latestCall = $calls[0];
$callId = $latestCall['call_id'];

echo "📞 CALL DETAILS:\n";
echo "  Call ID: $callId\n";
echo "  Start Time: {$latestCall['start_timestamp']}\n";
echo "  End Time: {$latestCall['end_timestamp']}\n";
echo "  Duration: " . round($latestCall['call_analysis']['call_summary']['duration_seconds'], 1) . "s\n";
echo "  Status: {$latestCall['call_status']}\n";
echo "  Success: " . ($latestCall['call_analysis']['call_summary']['call_successful'] ? '✅ YES' : '❌ NO') . "\n";
echo "  Disconnection: {$latestCall['disconnection_reason']}\n\n";

// Get full call details
$callResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-call/$callId");

if (!$callResp->successful()) {
    echo "❌ Failed to get call details\n";
    exit(1);
}

$call = $callResp->json();

// Save full call data
file_put_contents(__DIR__ . "/latest_test_call_v4_full.json", json_encode($call, JSON_PRETTY_PRINT));

echo "═══════════════════════════════════════════════════════════\n";
echo "📋 TRANSCRIPT ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$transcript = $call['transcript'] ?? '';
$transcriptLines = explode("\n", $transcript);

$userMessages = [];
$agentMessages = [];

foreach ($transcriptLines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    if (preg_match('/^User:\s*(.+)$/i', $line, $matches)) {
        $userMessages[] = $matches[1];
    } elseif (preg_match('/^Agent:\s*(.+)$/i', $line, $matches)) {
        $agentMessages[] = $matches[1];
    }
}

echo "👤 USER MESSAGES (" . count($userMessages) . "):\n";
foreach ($userMessages as $i => $msg) {
    echo "  " . ($i + 1) . ". " . substr($msg, 0, 100) . (strlen($msg) > 100 ? "..." : "") . "\n";
}

echo "\n🤖 AGENT MESSAGES (". count($agentMessages) . "):\n";
foreach ($agentMessages as $i => $msg) {
    echo "  " . ($i + 1) . ". " . substr($msg, 0, 100) . (strlen($msg) > 100 ? "..." : "") . "\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔄 NODE TRANSITIONS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$transitions = [];
if (isset($call['transcript_object'])) {
    foreach ($call['transcript_object'] as $event) {
        if ($event['role'] === 'system' && isset($event['content'])) {
            $content = is_string($event['content']) ? $event['content'] : json_encode($event['content']);
            if (strpos($content, 'node_transition') !== false) {
                $data = json_decode($content, true);
                if (isset($data['node_transition'])) {
                    $transitions[] = $data['node_transition'];
                }
            }
        }
    }
}

if (!empty($transitions)) {
    echo "Found " . count($transitions) . " transitions:\n\n";
    foreach ($transitions as $i => $trans) {
        echo ($i + 1) . ". {$trans['former_node_name']} → {$trans['new_node_name']}\n";
        echo "   From: {$trans['former_node_id']}\n";
        echo "   To: {$trans['new_node_id']}\n";
        echo "   Time: " . round($trans['time_sec'], 3) . "s\n\n";
    }
} else {
    echo "⚠️  No transitions found in transcript_object\n\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "📞 FUNCTION CALLS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$functionCalls = [];
if (isset($call['transcript_object'])) {
    foreach ($call['transcript_object'] as $event) {
        if ($event['role'] === 'function_call' || $event['role'] === 'tool_call') {
            $functionCalls[] = $event;
        }
    }
}

if (!empty($functionCalls)) {
    echo "Found " . count($functionCalls) . " function calls:\n\n";
    foreach ($functionCalls as $i => $fc) {
        echo "═══ FUNCTION CALL " . ($i + 1) . " ═══\n";
        echo "Name: {$fc['name']}\n";
        echo "Arguments:\n";
        $args = is_string($fc['arguments']) ? json_decode($fc['arguments'], true) : $fc['arguments'];
        echo json_encode($args, JSON_PRETTY_PRINT) . "\n";

        if (isset($fc['result'])) {
            echo "\nResult:\n";
            $result = is_string($fc['result']) ? $fc['result'] : json_encode($fc['result'], JSON_PRETTY_PRINT);
            echo substr($result, 0, 500) . (strlen($result) > 500 ? "...\n" : "\n");
        }
        echo "\n";
    }
} else {
    echo "⚠️  No function calls found\n\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "📊 DYNAMIC VARIABLES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$dynamicVars = $call['call_analysis']['call_summary']['collected_dynamic_variables'] ?? [];
if (!empty($dynamicVars)) {
    echo json_encode($dynamicVars, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "No dynamic variables collected\n\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "🎯 V4 VERIFICATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Check if intent_router was used
$usedIntentRouter = false;
foreach ($transitions as $trans) {
    if ($trans['new_node_id'] === 'intent_router') {
        $usedIntentRouter = true;
        break;
    }
}

if ($usedIntentRouter) {
    echo "✅ V4 Flow confirmed - used intent_router\n";
} else {
    echo "❌ V4 Flow NOT used - intent_router missing\n";
    echo "⚠️  Agent might still be using old flow\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "💾 SAVED TO: latest_test_call_v4_full.json\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Now check Laravel logs for this call
echo "═══════════════════════════════════════════════════════════\n";
echo "📝 LARAVEL LOGS FOR THIS CALL\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $logLines = explode("\n", $logs);

    $relevantLogs = [];
    foreach ($logLines as $line) {
        if (strpos($line, $callId) !== false) {
            $relevantLogs[] = $line;
        }
    }

    if (!empty($relevantLogs)) {
        echo "Found " . count($relevantLogs) . " log entries for this call\n\n";
        echo "Last 20 entries:\n";
        foreach (array_slice($relevantLogs, -20) as $log) {
            echo substr($log, 0, 200) . "\n";
        }
    } else {
        echo "⚠️  No Laravel logs found for call ID: $callId\n";
    }
} else {
    echo "⚠️  Log file not found: $logFile\n";
}

echo "\n";
