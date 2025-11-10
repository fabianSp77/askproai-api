<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$conversationFlowId = 'conversation_flow_1607b81c8f93';
$apiKey = config('services.retellai.api_key');
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

echo "=== PUBLISH CONVERSATION FLOW ===\n\n";
echo "Flow ID: $conversationFlowId\n\n";

try {
    // Try to publish the conversation flow
    echo "🚀 Publishing conversation flow...\n";

    $response = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->post("{$baseUrl}/publish-conversation-flow/{$conversationFlowId}");

    if ($response->successful()) {
        $result = $response->json();
        echo "✅ Conversation flow published successfully!\n";
        print_r($result);
    } else {
        echo "❌ Publish failed: " . $response->status() . "\n";
        echo $response->body() . "\n";

        // If publish endpoint doesn't exist, try update with is_published flag
        echo "\n🔄 Trying alternative method (PATCH with is_published)...\n";

        // First get current flow
        $getResponse = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

        if (!$getResponse->successful()) {
            throw new Exception("Could not fetch flow: " . $getResponse->body());
        }

        $flow = $getResponse->json();

        // Normalize tools
        $tools = $flow['tools'] ?? [];
        foreach ($tools as &$tool) {
            if (isset($tool['headers']) && is_array($tool['headers']) && empty($tool['headers'])) {
                $tool['headers'] = (object)[];
            }
            if (isset($tool['query_params']) && is_array($tool['query_params']) && empty($tool['query_params'])) {
                $tool['query_params'] = (object)[];
            }
            if (isset($tool['response_variables']) && is_array($tool['response_variables']) && empty($tool['response_variables'])) {
                $tool['response_variables'] = (object)[];
            }
        }

        // Update with is_published = true
        $updatePayload = [
            'global_prompt' => $flow['global_prompt'],
            'nodes' => $flow['nodes'],
            'tools' => $tools,
            'model_choice' => $flow['model_choice'] ?? ['type' => 'cascading', 'model' => 'gpt-4o-mini'],
            'model_temperature' => $flow['model_temperature'] ?? 0.3,
            'start_node_id' => $flow['start_node_id'] ?? 'func_00_initialize',
            'start_speaker' => $flow['start_speaker'] ?? 'agent',
            'begin_after_user_silence_ms' => $flow['begin_after_user_silence_ms'] ?? 800,
            'is_published' => true,  // PUBLISH IT!
        ];

        $patchResponse = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", $updatePayload);

        if ($patchResponse->successful()) {
            echo "✅ Flow published via PATCH!\n";
            $result = $patchResponse->json();
            echo "   Version: " . ($result['version'] ?? 'unknown') . "\n";
            echo "   Is Published: " . ($result['is_published'] ? 'YES' : 'NO') . "\n";
        } else {
            throw new Exception("PATCH with is_published failed: " . $patchResponse->body());
        }
    }

    // Verify
    echo "\n🔍 Verifying...\n";
    $verifyResponse = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

    if ($verifyResponse->successful()) {
        $verifiedFlow = $verifyResponse->json();
        echo "   Version: " . ($verifiedFlow['version'] ?? 'unknown') . "\n";
        echo "   Is Published: " . ($verifiedFlow['is_published'] ? '✅ YES' : '❌ NO') . "\n";
        echo "   Contains Hairdetox: " . (stripos($verifiedFlow['global_prompt'] ?? '', 'Hairdetox') !== false ? '✅' : '❌') . "\n";
    }

    echo "\n🎉 DONE!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
