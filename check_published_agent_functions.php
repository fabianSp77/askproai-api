#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 PUBLISHED AGENT FUNCTION CONFIGURATION CHECK\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId");

$agent = $response->json();

$version = $agent['version'] ?? 'unknown';
$isPublished = $agent['is_published'] ?? false;

echo "Agent Version: $version\n";
echo "Published: " . ($isPublished ? "YES ✅" : "NO ❌") . "\n\n";

// Check tools
if (isset($agent['conversation_flow']['tools'])) {
    $tools = $agent['conversation_flow']['tools'];
    $toolCount = count($tools);

    echo "Tools Count: $toolCount\n";
    echo "───────────────────────────────────────────────────────────\n\n";

    if ($toolCount === 0) {
        echo "❌ CRITICAL: Agent has 0 tools!\n";
        echo "   This means function calls will NOT work!\n\n";
    } else {
        foreach ($tools as $idx => $tool) {
            $num = $idx + 1;
            $toolId = $tool['tool_id'] ?? 'N/A';
            $name = $tool['name'] ?? 'N/A';
            $url = $tool['url'] ?? null;
            $description = $tool['description'] ?? 'N/A';

            echo "[$num] $name\n";
            echo "    Tool ID: $toolId\n";

            if ($url) {
                echo "    URL: ✅ $url\n";
            } else {
                echo "    URL: ❌ MISSING - Function will NOT be triggered!\n";
            }

            echo "    Description: " . substr($description, 0, 60) . "...\n";

            // Check parameters
            if (isset($tool['parameters']['properties'])) {
                $params = array_keys($tool['parameters']['properties']);
                echo "    Parameters: " . implode(', ', $params) . "\n";
            }

            echo "\n";
        }
    }
} else {
    echo "❌ CRITICAL: No conversation_flow.tools found!\n\n";
}

// Check function nodes
if (isset($agent['conversation_flow']['nodes'])) {
    $nodes = $agent['conversation_flow']['nodes'];
    $functionNodes = array_filter($nodes, fn($n) => ($n['type'] ?? '') === 'function');

    echo "\nFunction Nodes Count: " . count($functionNodes) . "\n";
    echo "───────────────────────────────────────────────────────────\n\n";

    if (count($functionNodes) === 0) {
        echo "❌ CRITICAL: No function nodes!\n";
        echo "   Functions will never be called!\n\n";
    } else {
        foreach ($functionNodes as $node) {
            $nodeId = $node['id'] ?? 'N/A';
            $toolId = $node['tool_id'] ?? null;
            $waitForResult = $node['wait_for_result'] ?? false;
            $speakDuring = $node['speak_during_execution'] ?? false;
            $speakAfter = $node['speak_after_execution'] ?? false;

            echo "Node: $nodeId\n";

            if ($toolId) {
                echo "  Tool ID: ✅ $toolId\n";
            } else {
                echo "  Tool ID: ❌ MISSING - Node won't call any function!\n";
            }

            echo "  Wait for Result: " . ($waitForResult ? "✅ YES" : "⚠️ NO") . "\n";
            echo "  Speak During Execution: " . ($speakDuring ? "YES" : "NO") . "\n";
            echo "  Speak After Execution: " . ($speakAfter ? "YES" : "NO") . "\n";
            echo "\n";
        }
    }
} else {
    echo "\n❌ CRITICAL: No conversation_flow.nodes found!\n\n";
}

// Analysis
echo "═══════════════════════════════════════════════════════════\n";
echo "ANALYSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$hasTools = isset($agent['conversation_flow']['tools']) && count($agent['conversation_flow']['tools']) > 0;
$hasFunctionNodes = isset($agent['conversation_flow']['nodes']) && count(array_filter($agent['conversation_flow']['nodes'], fn($n) => ($n['type'] ?? '') === 'function')) > 0;
$allToolsHaveUrls = true;

if ($hasTools) {
    foreach ($agent['conversation_flow']['tools'] as $tool) {
        if (empty($tool['url'])) {
            $allToolsHaveUrls = false;
            break;
        }
    }
}

if ($hasTools && $hasFunctionNodes && $allToolsHaveUrls) {
    echo "✅ Configuration looks CORRECT:\n";
    echo "   ✅ Agent has tools\n";
    echo "   ✅ Agent has function nodes\n";
    echo "   ✅ All tools have webhook URLs\n\n";

    if (!$isPublished) {
        echo "⚠️  BUT: Agent is NOT published!\n";
        echo "   → Calls will use old published version\n\n";
        echo "ACTION: Publish this version in Dashboard\n";
    } else {
        echo "✅ Agent is published - Ready for test calls!\n";
    }
} else {
    echo "❌ Configuration BROKEN:\n";

    if (!$hasTools) {
        echo "   ❌ No tools defined\n";
    }
    if (!$hasFunctionNodes) {
        echo "   ❌ No function nodes\n";
    }
    if ($hasTools && !$allToolsHaveUrls) {
        echo "   ❌ Some tools missing webhook URLs\n";
    }

    echo "\n";

    if ($isPublished) {
        echo "⚠️  This broken version IS PUBLISHED!\n";
        echo "   → All calls will fail to trigger functions\n";
        echo "   → Users will experience hallucinated responses\n\n";
        echo "ACTION: Publish a working version (V69 or V71)\n";
    } else {
        echo "✅ At least this broken version is NOT published\n";
        echo "   → But check which version IS published\n";
    }
}

echo "\n";
