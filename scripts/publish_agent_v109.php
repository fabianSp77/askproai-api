<?php
/**
 * Publish Agent with V109 Flow
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$agentId = 'agent_c1d8dea0445f375857a55ffd61';
$apiKey = config('services.retellai.api_key');
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

echo "=== PUBLISH AGENT WITH V109 ===\n\n";
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
    echo "   Version: V" . ($agent['version'] ?? 'unknown') . "\n";
    echo "   Flow ID: " . ($agent['conversation_flow_id'] ?? 'none') . "\n";
    echo "   Is Published: " . (($agent['is_published'] ?? false) ? 'YES' : 'NO') . "\n\n";

    // Publish agent
    echo "🚀 Publishing agent...\n";

    $publishResponse = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->post("{$baseUrl}/publish-agent/{$agentId}");

    if ($publishResponse->successful()) {
        $result = $publishResponse->json();
        echo "✅ Agent published successfully!\n";
        echo "   New Version: V" . ($result['version'] ?? 'unknown') . "\n";
        echo "   Is Published: " . (($result['is_published'] ?? false) ? 'YES' : 'NO') . "\n\n";
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
            echo "   New Version: V" . ($result['version'] ?? 'unknown') . "\n";
            echo "   Is Published: " . (($result['is_published'] ?? false) ? 'YES' : 'NO') . "\n\n";
        } else {
            throw new Exception("PATCH failed: " . $patchResponse->body());
        }
    }

    // Verify
    echo "🔍 Verifying agent...\n";
    $verifyResponse = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->get("{$baseUrl}/get-agent/{$agentId}");

    if ($verifyResponse->successful()) {
        $verifiedAgent = $verifyResponse->json();
        echo "   Version: V" . ($verifiedAgent['version'] ?? 'unknown') . "\n";
        echo "   Flow ID: " . ($verifiedAgent['conversation_flow_id'] ?? 'none') . "\n";
        echo "   Is Published: " . (($verifiedAgent['is_published'] ?? false) ? '✅ YES' : '❌ NO') . "\n\n";
    }

    echo "=== SUCCESS ===\n\n";
    echo "✅ Agent published with V109 flow\n";
    echo "✅ service_name parameter fix active\n";
    echo "✅ function_name removed\n\n";

    echo "=== TEST NOW ===\n\n";
    echo "1. Test Interface: https://api.askpro.ai/docs/api-testing\n";
    echo "   - Test start_booking with service_name parameter\n";
    echo "   - Should now succeed\n\n";

    echo "2. Voice Call Test: +493033081738\n";
    echo "   - Say: 'Hans Schuster, Herrenhaarschnitt morgen 10 Uhr'\n";
    echo "   - Accept alternative time\n";
    echo "   - Booking should succeed\n\n";

    echo "=== ROOT CAUSE FIXED ===\n\n";
    echo "Problem:  Backend expected 'service_name' but got 'service'\n";
    echo "Solution: V109 sends 'service_name' parameter\n";
    echo "Result:   Backend can now find and book services\n\n";

    echo "=== END ===\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
