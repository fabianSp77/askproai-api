<?php

/**
 * Check Which Version is Published
 */

$agentId = 'agent_45daa54928c5768b52ba3db736';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔍 CHECKING PUBLISHED VERSION\n";
echo str_repeat('=', 80) . "\n\n";

// Get agent versions list
$ch = curl_init("https://api.retellai.com/get-agent-versions/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$versions = json_decode($response, true);

echo "AGENT VERSIONS:\n";
echo str_repeat('-', 80) . "\n";

if (isset($versions['versions'])) {
    foreach ($versions['versions'] as $version) {
        $icon = $version['is_published'] ? '🟢 PUBLISHED' : '⚪ DRAFT';
        $flowVersion = $version['response_engine']['version'] ?? 'N/A';
        echo "{$icon} - V{$version['version']} (Flow V{$flowVersion})\n";
    }
} else {
    echo "Could not retrieve versions list\n";
    echo "Response: " . json_encode($versions) . "\n";
}
echo "\n";

// Get current agent (this shows the draft)
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);

echo "CURRENT AGENT (what dashboard shows):\n";
echo str_repeat('-', 80) . "\n";
echo "   Version: V{$agent['version']}\n";
echo "   Is Published: " . ($agent['is_published'] ? 'YES' : 'NO') . "\n";
echo "   Flow Version: V{$agent['response_engine']['version']}\n\n";

echo str_repeat('=', 80) . "\n";
echo "SUMMARY:\n";
echo str_repeat('=', 80) . "\n\n";

if (isset($versions['versions'])) {
    $publishedVersion = null;
    foreach ($versions['versions'] as $version) {
        if ($version['is_published']) {
            $publishedVersion = $version;
            break;
        }
    }

    if ($publishedVersion) {
        $flowVersion = $publishedVersion['response_engine']['version'];
        echo "✅ Published Version: V{$publishedVersion['version']}\n";
        echo "✅ Using Flow: V{$flowVersion}\n\n";

        // Check if published version has correct syntax
        $ch = curl_init("https://api.retellai.com/get-conversation-flow/{$publishedVersion['response_engine']['conversation_flow_id']}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$apiKey}",
            "Content-Type: application/json"
        ]);
        $flowResponse = curl_exec($ch);
        curl_close($ch);

        $flow = json_decode($flowResponse, true);

        if ($flow['version'] == $flowVersion) {
            echo "PARAMETER MAPPINGS IN PUBLISHED VERSION:\n";
            echo str_repeat('-', 80) . "\n";

            $functionNodes = array_filter($flow['nodes'], fn($n) => $n['type'] === 'function');
            $allCorrect = true;

            foreach ($functionNodes as $node) {
                $callId = $node['parameter_mapping']['call_id'] ?? 'NOT SET';
                $icon = ($callId === '{{call_id}}') ? '✅' : '❌';
                echo "{$icon} {$node['name']}: {$callId}\n";

                if ($callId !== '{{call_id}}') {
                    $allCorrect = false;
                }
            }

            echo "\n";

            if ($allCorrect) {
                echo "🎉 PERFECT! Published version uses correct {{call_id}} syntax!\n\n";
                echo "READY FOR TEST CALLS!\n";
            } else {
                echo "⚠️  WARNING: Published version still has incorrect syntax!\n";
            }
        }

    } else {
        echo "❌ No published version found!\n";
    }
}
