<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$apiKey = config('services.retellai.api_key');
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

echo "=== PUBLISH AGENT ===\n\n";
echo "Agent ID: $agentId\n\n";

try {
    // Get current agent status
    echo "📥 Getting agent status...\n";
    $getResponse = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->get("{$baseUrl}/get-agent/{$agentId}");

    if (!$getResponse->successful()) {
        throw new Exception("Could not fetch agent: " . $getResponse->body());
    }

    $agent = $getResponse->json();
    echo "✅ Agent fetched\n";
    echo "   Name: " . ($agent['agent_name'] ?? 'Unknown') . "\n";
    echo "   Version: " . ($agent['version'] ?? 'unknown') . "\n";
    echo "   Is Published (before): " . ($agent['is_published'] ? 'YES' : 'NO') . "\n\n";

    // Publish agent
    echo "🚀 Publishing agent...\n";

    $publishResponse = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->post("{$baseUrl}/publish-agent/{$agentId}");

    if ($publishResponse->successful()) {
        $result = $publishResponse->json();
        echo "✅ Agent published successfully!\n";
        echo "   Version: " . ($result['version'] ?? 'unknown') . "\n";
        echo "   Is Published: " . ($result['is_published'] ? 'YES' : 'NO') . "\n";
    } else {
        echo "⚠️ Direct publish failed (" . $publishResponse->status() . "), trying PATCH...\n";

        // Try PATCH with is_published = true
        $patchPayload = [
            'is_published' => true
        ];

        $patchResponse = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->patch("{$baseUrl}/update-agent/{$agentId}", $patchPayload);

        if ($patchResponse->successful()) {
            $result = $patchResponse->json();
            echo "✅ Agent published via PATCH!\n";
            echo "   Version: " . ($result['version'] ?? 'unknown') . "\n";
            echo "   Is Published: " . ($result['is_published'] ? 'YES' : 'NO') . "\n";
        } else {
            throw new Exception("PATCH failed: " . $patchResponse->body());
        }
    }

    // Verify
    echo "\n🔍 Verifying...\n";
    $verifyResponse = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->get("{$baseUrl}/get-agent/{$agentId}");

    if ($verifyResponse->successful()) {
        $verifiedAgent = $verifyResponse->json();
        echo "   Version: " . ($verifiedAgent['version'] ?? 'unknown') . "\n";
        echo "   Is Published: " . ($verifiedAgent['is_published'] ? '✅ YES' : '❌ NO') . "\n";

        // Check conversation flow
        if (isset($verifiedAgent['response_engine']['conversation_flow_id'])) {
            $flowId = $verifiedAgent['response_engine']['conversation_flow_id'];
            echo "   Uses Flow: $flowId\n";
        }
    }

    echo "\n🎉 DONE!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
