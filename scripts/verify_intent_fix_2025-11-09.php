<?php
/**
 * Verify Intent Router fix was saved correctly
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = config('services.retellai.api_key');

echo "=== VERIFY INTENT ROUTER FIX ===\n\n";

// Fetch current flow
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json'
])->get("https://api.retellai.com/get-conversation-flow/{$flowId}");

if (!$response->successful()) {
    echo "❌ Failed to fetch flow\n";
    exit(1);
}

$flow = $response->json();

echo "Flow Version: V{$flow['version']}\n";
echo "Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n\n";

// Find Intent Router node
foreach ($flow['nodes'] as $node) {
    if ($node['id'] === 'intent_router') {
        echo "Node: {$node['name']}\n";
        echo "Type: {$node['type']}\n\n";

        $instruction = $node['instruction']['text'];

        // Check for key phrases
        $checks = [
            'STUMMER ROUTER' => strpos($instruction, 'STUMMER ROUTER') !== false,
            'VERBOTEN' => strpos($instruction, 'VERBOTEN') !== false,
            'Verfügbarkeit prüfen oder raten' => strpos($instruction, 'Verfügbarkeit prüfen oder raten') !== false,
            'ERLAUBT' => strpos($instruction, 'ERLAUBT') !== false,
            'NUR silent transition' => strpos($instruction, 'NUR silent transition') !== false
        ];

        echo "Instruction Content Checks:\n";
        foreach ($checks as $phrase => $found) {
            $status = $found ? '✅' : '❌';
            echo "  {$status} Contains: '{$phrase}'\n";
        }

        echo "\n";

        // Show first 200 chars
        echo "Instruction Preview:\n";
        echo substr($instruction, 0, 300) . "...\n\n";

        // Overall verdict
        $allFound = !in_array(false, $checks, true);

        if ($allFound) {
            echo "✅ ALL CHECKS PASSED\n";
            echo "Fix was successfully saved!\n\n";
        } else {
            echo "❌ SOME CHECKS FAILED\n";
            echo "Fix may not have been saved correctly!\n\n";
        }

        break;
    }
}

echo "=== NEXT STEP ===\n\n";
echo "User should publish V{$flow['version']} in Retell Dashboard:\n";
echo "1. Go to: https://dashboard.retellai.com/\n";
echo "2. Open: Agent 'Friseur 1 Agent V51'\n";
echo "3. Find: Conversation Flow V{$flow['version']}\n";
echo "4. Click: 'Publish'\n\n";

echo "After publishing:\n";
echo "- Make VOICE CALL test (not text chat!)\n";
echo "- Agent should NOT hallucinate availability\n";
echo "- Agent should transition silently\n";
