<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$llmId = trim(file_get_contents(__DIR__ . '/retell_llm_id.txt'));

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 CHECKING LLM CUSTOM FUNCTIONS CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "LLM ID: $llmId\n\n";

$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-retell-llm/$llmId");

if (!$response->successful()) {
    echo "❌ Failed to get LLM\n";
    echo "Status: {$response->status()}\n";
    echo "Body: {$response->body()}\n";
    exit(1);
}

$llm = $response->json();

echo "✅ LLM Retrieved\n\n";

// Save full config
file_put_contents(__DIR__ . '/llm_full_config.json', json_encode($llm, JSON_PRETTY_PRINT));

// Check tools/functions
if (isset($llm['tools'])) {
    echo "📦 TOOLS CONFIGURATION:\n";
    echo "Number of tools: " . count($llm['tools']) . "\n\n";

    foreach ($llm['tools'] as $index => $tool) {
        echo "─────────────────────────────────────────\n";
        echo "Tool #" . ($index + 1) . ":\n";
        echo "  Name: " . ($tool['name'] ?? 'N/A') . "\n";
        echo "  Type: " . ($tool['type'] ?? 'N/A') . "\n";
        echo "  URL: " . ($tool['url'] ?? 'N/A') . "\n";

        if (isset($tool['description'])) {
            echo "  Description: " . substr($tool['description'], 0, 80) . "...\n";
        }

        // Check for response_data/response_variables
        if (isset($tool['response_data'])) {
            echo "  ✅ HAS response_data:\n";
            echo json_encode($tool['response_data'], JSON_PRETTY_PRINT) . "\n";
        }

        if (isset($tool['response_variables'])) {
            echo "  ✅ HAS response_variables:\n";
            echo json_encode($tool['response_variables'], JSON_PRETTY_PRINT) . "\n";
        }

        if (isset($tool['speak_during_execution'])) {
            echo "  speak_during_execution: " . ($tool['speak_during_execution'] ? 'true' : 'false') . "\n";
        }

        if (isset($tool['speak_after_execution'])) {
            echo "  speak_after_execution: " . ($tool['speak_after_execution'] ? 'true' : 'false') . "\n";
        }

        echo "\n";
    }
} else {
    echo "❌ No tools found in LLM config\n";
}

echo "\n✅ Full config saved to: llm_full_config.json\n\n";
